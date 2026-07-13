<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceApplication;
use App\Models\User;
use App\Models\Window;
use App\Support\AppRoles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReportingDashboardService
{
    protected array $approvedStatuses = [
        'approved',
        'completed',
        'sent_to_customer',
        'closed_successfully',
        'back_officer_approved',
        'manager_resolved',
    ];

    protected array $rejectedStatuses = [
        'rejected',
    ];

    public function dashboard(Request $request, User $user): array
    {
        abort_if($this->hasRole($user, AppRoles::CUSTOMER), 403, 'Customer dashboard uses the customer application page.');

        $baseQuery = $this->scopedApplicationQuery($user);
        $filters = $this->filters($request, $user);

        if (! $this->hasRequiredSelection($request)) {
            return [
                'summary' => $this->emptySummary(),
                'report' => [],
                'feedback' => [],
                'filters' => $filters,
                'scope' => $this->scope($user),
                'requires_filter' => true,
            ];
        }

        $filtered = $this->applyFilters(clone $baseQuery, $request);

        return [
            'summary' => $this->summary(clone $filtered),
            'report' => $this->applicationReport(clone $filtered),
            'feedback' => $this->feedbackReport($request, $user),
            'filters' => $filters,
            'scope' => $this->scope($user),
            'requires_filter' => false,
        ];
    }

    protected function hasRequiredSelection(Request $request): bool
    {
        return $request->filled('administrative_level')
            || $request->filled('window_id')
            || $request->filled('service_id')
            || $request->filled('officer_id')
            || $request->filled('status')
            || $request->filled('time')
            || $request->filled('from_date')
            || $request->filled('to_date');
    }

    protected function emptySummary(): array
    {
        return [
            'total_applications' => 0,
            'approved_applications' => 0,
            'rejected_applications' => 0,
            'in_progress_applications' => 0,
        ];
    }

    protected function scopedApplicationQuery(User $user): Builder
    {
        $query = ServiceApplication::query()
            ->with([
                'service:id,name',
                'customer:id,name',
                'city:id,name',
                'subcity:id,name',
                'woreda:id,name',
                'currentWindow:id,name',
                'currentOfficer:id,name',
                'assignee:id,name',
            ]);

        if ($this->hasRole($user, AppRoles::SUPER_ADMIN)) {
            return $query;
        }

        if ($this->hasRole($user, AppRoles::FRONT_OFFICER) || $this->hasRole($user, AppRoles::BACK_OFFICER)) {
            return $query->where(function ($q) use ($user) {
                $q->where('current_officer_id', $user->id)
                    ->orWhere('assigned_to', $user->id)
                    ->orWhereHas('histories', function ($history) use ($user) {
                        $history->where('actor_id', $user->id)
                            ->orWhere('sender_id', $user->id)
                            ->orWhere('receiver_id', $user->id);
                    })
                    ->orWhereHas('shares', function ($share) use ($user) {
                        $share->where('shared_from_officer_id', $user->id)
                            ->orWhere('shared_to_officer_id', $user->id);
                    });
            });
        }

        if ($this->hasRole($user, AppRoles::ADMIN) || $this->hasRole($user, AppRoles::MANAGER)) {
            $level = AppRoles::userLevel($user);

            if ($level === AppRoles::LEVEL_CITY && $user->city_id) {
                return $query->where('city_id', $user->city_id);
            }

            if ($level === AppRoles::LEVEL_SUBCITY && $user->subcity_id) {
                return $query
                    ->where('city_id', $user->city_id)
                    ->where('subcity_id', $user->subcity_id);
            }

            if ($level === AppRoles::LEVEL_WOREDA && $user->woreda_id) {
                return $query
                    ->where('city_id', $user->city_id)
                    ->where('subcity_id', $user->subcity_id)
                    ->where('woreda_id', $user->woreda_id);
            }
        }

        return $query->whereRaw('1 = 0');
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('administrative_level'), function ($q) use ($request) {
                $level = (string) $request->string('administrative_level');

                if ($level !== 'all') {
                    $q->where('administrative_level', $level);
                }
            })
            ->when($request->filled('window_id'), fn ($q) => $q->where('current_window_id', $request->integer('window_id')))
            ->when($request->filled('service_id'), fn ($q) => $q->where('service_id', $request->integer('service_id')))
            ->when($request->filled('officer_id'), function ($q) use ($request) {
                $officerId = $request->integer('officer_id');

                $q->where(function ($officerQuery) use ($officerId) {
                    $officerQuery->where('current_officer_id', $officerId)
                        ->orWhere('assigned_to', $officerId)
                        ->orWhereHas('histories', function ($history) use ($officerId) {
                            $history->where('actor_id', $officerId)
                                ->orWhere('sender_id', $officerId)
                                ->orWhere('receiver_id', $officerId);
                        });
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('time'), fn ($q) => $this->applyTimeFilter($q, (string) $request->string('time'), $request))
            ->latest('submitted_at');
    }

    protected function applyTimeFilter(Builder $query, string $time, Request $request): Builder
    {
        return match ($time) {
            'today' => $query->whereDate('submitted_at', now()->toDateString()),
            'week', 'this_week' => $query->whereBetween('submitted_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month', 'this_month' => $query->whereYear('submitted_at', now()->year)->whereMonth('submitted_at', now()->month),
            'year', 'this_year' => $query->whereYear('submitted_at', now()->year),
            'custom' => $query
                ->when($request->filled('from_date'), fn ($q) => $q->whereDate('submitted_at', '>=', $request->date('from_date')))
                ->when($request->filled('to_date'), fn ($q) => $q->whereDate('submitted_at', '<=', $request->date('to_date'))),
            default => $query,
        };
    }

    protected function summary(Builder $query): array
    {
        $all = (clone $query)->get(['id', 'status']);

        $approved = $all->whereIn('status', $this->approvedStatuses)->count();
        $rejected = $all->whereIn('status', $this->rejectedStatuses)->count();

        return [
            'total_applications' => $all->count(),
            'approved_applications' => $approved,
            'rejected_applications' => $rejected,
            'in_progress_applications' => max($all->count() - $approved - $rejected, 0),
        ];
    }

    protected function applicationReport(Builder $query): array
    {
        return $query->limit(5000)
            ->get()
            ->groupBy(function ($application) {
                return implode('|', [
                    $application->currentWindow?->name ?: 'Unassigned Window',
                    $application->service?->name ?: 'Unassigned Service',
                    $application->currentOfficer?->name ?: $application->assignee?->name ?: 'Unassigned Officer',
                ]);
            })
            ->map(function ($items) {
                $first = $items->first();
                $approvedRejected = $items->whereIn('status', array_merge($this->approvedStatuses, $this->rejectedStatuses))->count();
                $total = $items->count();

                return [
                    'window' => $first->currentWindow?->name ?: 'Unassigned Window',
                    'service' => $first->service?->name ?: 'Unassigned Service',
                    'officer' => $first->currentOfficer?->name ?: $first->assignee?->name ?: 'Unassigned Officer',
                    'total' => $total,
                    'approved_rejected' => $approvedRejected,
                    'on_progress' => max($total - $approvedRejected, 0),
                    'percent' => $total > 0 ? round(($approvedRejected / $total) * 100, 2) : 0,
                ];
            })
            ->sortBy('window')
            ->values()
            ->all();
    }

    protected function feedbackReport(Request $request, User $user): array
    {
        if (! Schema::hasTable('service_application_feedbacks')) {
            return [];
        }

        $applicationIds = $this->applyFilters($this->scopedApplicationQuery($user), $request)->pluck('id');

        if ($applicationIds->isEmpty()) {
            return [];
        }

        return \App\Models\ServiceApplicationFeedback::query()
            ->with(['application.service:id,name', 'application.currentWindow:id,name'])
            ->whereIn('application_id', $applicationIds)
            ->get()
            ->groupBy(function ($feedback) {
                return implode('|', [
                    $feedback->application?->currentWindow?->name ?: 'Unassigned Window',
                    $feedback->application?->service?->name ?: 'Unassigned Service',
                ]);
            })
            ->map(function ($items) {
                $first = $items->first();
                $total = $items->count();
                $high = $items->where('satisfaction_scale', 'highly_satisfied')->count();
                $satisfied = $items->where('satisfaction_scale', 'satisfied')->count();
                $notSatisfied = $items->where('satisfaction_scale', 'not_satisfied')->count();

                return [
                    'window' => $first->application?->currentWindow?->name ?: 'Unassigned Window',
                    'service' => $first->application?->service?->name ?: 'Unassigned Service',
                    'highly_satisfied' => $high,
                    'satisfied' => $satisfied,
                    'not_satisfied' => $notSatisfied,
                    'total' => $total,
                    'percent' => $total > 0 ? round((($high + $satisfied) / $total) * 100, 2) : 0,
                ];
            })
            ->sortBy('window')
            ->values()
            ->all();
    }

    protected function filters(Request $request, User $user): array
    {
        $scoped = $this->scopedApplicationQuery($user);

        $level = $request->filled('administrative_level') && $request->string('administrative_level') !== 'all'
            ? (string) $request->string('administrative_level')
            : null;

        $levelScoped = (clone $scoped)
            ->when($level, fn ($q) => $q->where('administrative_level', $level));

        $windowIds = (clone $levelScoped)
            ->pluck('current_window_id')
            ->filter()
            ->unique()
            ->values();

        $selectedWindowId = $request->integer('window_id') ?: null;
        $selectedServiceId = $request->integer('service_id') ?: null;

        $serviceIds = (clone $levelScoped)
            ->when($selectedWindowId, fn ($q) => $q->where('current_window_id', $selectedWindowId))
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->values();

        $officerIds = (clone $levelScoped)
            ->when($selectedWindowId, fn ($q) => $q->where('current_window_id', $selectedWindowId))
            ->when($selectedServiceId, fn ($q) => $q->where('service_id', $selectedServiceId))
            ->get(['current_officer_id', 'assigned_to'])
            ->flatMap(fn ($item) => [$item->current_officer_id, $item->assigned_to])
            ->filter()
            ->unique()
            ->values();

        return [
            'levels' => [
                ['label' => 'All', 'value' => 'all'],
                ['label' => 'City', 'value' => AppRoles::LEVEL_CITY],
                ['label' => 'Subcity', 'value' => AppRoles::LEVEL_SUBCITY],
                ['label' => 'Woreda', 'value' => AppRoles::LEVEL_WOREDA],
            ],
            'times' => [
                ['label' => 'All', 'value' => ''],
                ['label' => 'Today', 'value' => 'today'],
                ['label' => 'This week', 'value' => 'week'],
                ['label' => 'This Month', 'value' => 'month'],
                ['label' => 'This Year', 'value' => 'year'],
                ['label' => 'Custom', 'value' => 'custom'],
            ],
            'windows' => Window::query()
                ->whereIn('id', $windowIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'services' => Service::query()
                ->whereIn('id', $serviceIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'officers' => User::query()
                ->whereIn('id', $officerIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses' => ServiceApplication::query()
                ->select('status')
                ->distinct()
                ->orderBy('status')
                ->pluck('status')
                ->values(),
        ];
    }

    protected function scope(User $user): array
    {
        return [
            'role' => $this->primaryRole($user),
            'level' => AppRoles::userLevel($user),
            'city_id' => $user->city_id,
            'subcity_id' => $user->subcity_id,
            'woreda_id' => $user->woreda_id,
            'label' => $this->scopeLabel($user),
        ];
    }

    protected function hasRole(User $user, string $role): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
            return true;
        }

        return AppRoles::normalize($this->primaryRole($user)) === $role;
    }

    protected function primaryRole(User $user): string
    {
        if (method_exists($user, 'getRoleNames')) {
            return (string) ($user->getRoleNames()->first() ?: $user->role ?: '');
        }

        return (string) ($user->role ?: '');
    }

    protected function scopeLabel(User $user): string
    {
        if ($this->hasRole($user, AppRoles::SUPER_ADMIN)) {
            return 'All city, sub-city and woreda records';
        }

        $level = AppRoles::userLevel($user);

        return match ($level) {
            AppRoles::LEVEL_CITY => 'Assigned city and all records under it',
            AppRoles::LEVEL_SUBCITY => 'Assigned sub-city and all woredas under it',
            AppRoles::LEVEL_WOREDA => 'Assigned woreda only',
            default => Str::headline($this->primaryRole($user)) . ' personal scope',
        };
    }
}
