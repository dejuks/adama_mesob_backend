<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\ServiceApplication;
use App\Models\ApplicationQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerServiceApplicationController extends Controller
{
    private const STATUS_GROUPS = [
        'pending' => [
            'draft',
            'submitted',
            'resubmitted',
            'accepted',
            'front_officer_review',
            'under_review',
            'forwarded_to_back_officer',
            'back_officer_review',
            'under_back_review',
            'shared',
            'shared_to_front_officer',
            'shared_to_back_officer',
            'returned_from_share',
            'escalated',
            'escalated_to_manager',
            'manager_review',
            'manager_assigned',
            'manager_forwarded',
            'manager_returned',
            'returned_to_front_officer',
            'returned_to_back_officer',
        ],

        'rejected' => [
            'rejected',
            'returned',
            'returned_to_customer',
            'back_officer_rejected',
            'cancelled',
        ],

        'approved' => [
            'approved',
            'back_officer_approved',
            'manager_resolved',
        ],

        'appointed' => [
            'appointment_scheduled',
        ],

        'completed' => [
            'completed',
            'closed',
        ],
    ];

  public function index(Request $request): JsonResponse
{
    $baseQuery = ServiceApplication::query()
        ->where('customer_id', $request->user()->id);

    // Search filter
    $this->applySearch($baseQuery, $request);

    // Status counts (before filtering)
    $statusCounts = $this->statusCounts(clone $baseQuery);

    // Main query with relations INCLUDING QUEUE
    $applicationsQuery = (clone $baseQuery)
        ->with([
            'service',
            'city',
            'subcity',
            'woreda',
            'appointments',
            'queue',
        ]);

    // Apply status filter
    $this->applyStatusFilter($applicationsQuery, $request->input('status'));

    // Pagination
    $applications = $applicationsQuery
        ->latest('submitted_at')
        ->paginate(
            min((int) $request->input('per_page', 10), 100)
        );

    // =========================================================
    // ✅ FIXED QUEUE LOGIC (REAL-TIME ACCURATE)
    // =========================================================
    $data = collect($applications->items())->map(function ($app) {

        $queue = $app->queue;

        $applicationsAhead = null;
        $isNext = false;

        if ($queue) {

            // ✅ CORRECT LOGIC:
            // count ONLY waiting applications in SAME service
            // ordered by created_at (NOT id, NOT service_id in queue table)

            $applicationsAhead = ApplicationQueue::query()
                ->where('status', 'waiting')
                ->whereHas('application', function ($q) use ($app) {
                    $q->where('service_id', $app->service_id);
                })
                ->where('created_at', '<', $queue->created_at)
                ->count();

            $isNext = $applicationsAhead === 0 && $queue->status === 'waiting';
        }

        return [
            ...$app->toArray(),

            'queue_info' => $queue ? [
                'queue_number' => $queue->queue_number,
                'status' => $queue->status,
                'position' => $queue->position,

                // ✅ REAL DYNAMIC VALUE (always correct)
                'applications_ahead' => $applicationsAhead,

                // 🔥 next in line flag
                'is_next' => $isNext,
            ] : null,
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Customer applications retrieved successfully',

        'data' => $data,

        'meta' => [
            'current_page' => $applications->currentPage(),
            'per_page' => $applications->perPage(),
            'total' => $applications->total(),
            'last_page' => $applications->lastPage(),

            'status_counts' => $statusCounts,
        ],
    ]);
}
 public function show(Request $request, ServiceApplication $application): JsonResponse
{
    abort_if(
        (int) $application->customer_id !== (int) $request->user()->id,
        403
    );

    $application->load([
        'service',
        'customer',
        'city',
        'subcity',
        'woreda',
        'currentWindow',
        'currentOfficer',
        'assignee',
        'data',
        'files.uploader',
        'appointments.scheduler',
        'workflows.window',
        'workflows.officer',
        'histories.actor',
        'histories.sender',
        'histories.receiver',
        'histories.fromWindow',
        'histories.toWindow',
        'shares.sharedFromOfficer',
        'shares.sharedToOfficer',
        'shares.fromWindow',
        'shares.toWindow',
        'queue',
    ]);

    $queue = $application->queue;

    $queueInfo = null;

    if ($queue) {

        $applicationsAhead = \App\Models\ApplicationQueue::query()
            ->where('status', 'waiting')
            ->where('position', '<', $queue->position)
            ->whereHas('application', function ($query) use ($application) {
                $query->where('service_id', $application->service_id);
            })
            ->count();

        $totalWaiting = \App\Models\ApplicationQueue::query()
            ->where('status', 'waiting')
            ->whereHas('application', function ($query) use ($application) {
                $query->where('service_id', $application->service_id);
            })
            ->count();

        $queueInfo = [
            'queue_number' => $queue->queue_number,
            'status' => $queue->status,
            'position' => $queue->position,
            'applications_ahead' => $applicationsAhead,
            'total_waiting' => $totalWaiting,
            'message' => $applicationsAhead > 0
                ? "{$applicationsAhead} applications are ahead of you."
                : "Your application is next in queue.",
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Customer application retrieved successfully',
        'data' => $application,
        'queue_info' => $queueInfo,
    ]);
}


    private function customerStatusGroup(string $status): array
    {
        return self::STATUS_GROUPS[$status] ?? [$status];
    }

    private function applySearch(Builder $query, Request $request): void
    {
        $query->when($request->filled('search'), function (Builder $query) use ($request) {
            $search = $request->input('search');

            $query->where(function (Builder $query) use ($search) {
                $query->where('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('service', function (Builder $serviceQuery) use ($search) {
                        $serviceQuery->where('name', 'like', "%{$search}%");
                    });
            });
        });
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (!$status || $status === 'all') {
            return;
        }

        $query->whereIn('status', self::STATUS_GROUPS[$status] ?? [$status]);
    }

    private function statusCounts(Builder $query): array
    {
        $counts = [
            'total' => (clone $query)->count(),
        ];

        foreach (self::STATUS_GROUPS as $key => $statuses) {
            $counts[$key] = (clone $query)->whereIn('status', $statuses)->count();
        }

        return $counts;
    }
}
