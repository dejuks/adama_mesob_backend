<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{


    /**
     * Display feedback list (Admin)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $feedbacks = Feedback::with([
            'service.windows',
            'city',
            'subcity',
            'woreda',
            'submittedBy',
        ])

            /*
            |--------------------------------------------------------------------------
            | Filter by User Location
            |--------------------------------------------------------------------------
            | Super admins (no location assigned to their account) see everything.
            | Everyone else is automatically scoped to their own city/subcity/woreda,
            | most specific level wins (woreda > subcity > city).
            */
            ->forUserLocation($user)

            /*
            |--------------------------------------------------------------------------
            | Optional Filters
            |--------------------------------------------------------------------------
            */
            ->when($request->filled('city_id'), function ($query) use ($request) {
                $query->where('city_id', $request->city_id);
            })

            ->when($request->filled('subcity_id'), function ($query) use ($request) {
                $query->where('subcity_id', $request->subcity_id);
            })

            ->when($request->filled('woreda_id'), function ($query) use ($request) {
                $query->where('woreda_id', $request->woreda_id);
            })

            ->when($request->filled('service_id'), function ($query) use ($request) {
                $query->where('service_id', $request->service_id);
            })

            ->when($request->filled('rating'), function ($query) use ($request) {
                $query->where('overall_rating', $request->rating);
            })

            ->when($request->filled('satisfaction'), function ($query) use ($request) {
                $query->where('satisfaction', $request->satisfaction);
            })

            ->when($request->filled('date'), function ($query) use ($request) {
                $query->whereDate('created_at', $request->date);
            })

            ->latest()
            ->paginate(20);

        return FeedbackResource::collection($feedbacks);
    }





    /**
     * Store public feedback
     */
    /**
     * Store feedback
     */
    public function store(Request $request)
    {
        // Logged-in user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please login first.'
            ], 401);
        }

        $validated = $request->validate([

            'service_id' => [
                'required',
                'exists:services,id'
            ],

            'overall_rating' => [
                'required',
                'integer',
                'between:1,5'
            ],

            'staff_behavior' => [
                'nullable',
                'integer',
                'between:1,5'
            ],

            'waiting_time' => [
                'nullable',
                'integer',
                'between:1,5'
            ],

            'service_quality' => [
                'nullable',
                'integer',
                'between:1,5'
            ],

            'cleanliness' => [
                'nullable',
                'integer',
                'between:1,5'
            ],

            'satisfaction' => [
                'required',
                Rule::in([
                    'highly_satisfied',
                    'satisfied',
                    'not_satisfied'
                ])
            ],

            'comment' => [
                'nullable',
                'string',
                'max:1000'
            ],

            'gender' => [
                'nullable',
                Rule::in([
                    'male',
                    'female'
                ])
            ],

            'age' => [
                'nullable',
                'integer',
                'between:1,120'
            ]

        ]);

        /*
        |--------------------------------------------------------------------------
        | Check Active Service
        |--------------------------------------------------------------------------
        */

        $service = Service::where('id', $validated['service_id'])
            ->where('status', 'active')
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Create Feedback
        |--------------------------------------------------------------------------
        */

        // Location is NEVER taken from the request — it is always derived from
        // the logged-in user's own assigned location (city / subcity / woreda).
        $feedback = Feedback::create([

            'submitted_by' => $user->id,

            'city_id' => $user->city_id,
            'subcity_id' => $user->subcity_id,
            'woreda_id' => $user->woreda_id,

            'service_id' => $service->id,

            'overall_rating' => $validated['overall_rating'],
            'staff_behavior' => $validated['staff_behavior'] ?? null,
            'waiting_time' => $validated['waiting_time'] ?? null,
            'service_quality' => $validated['service_quality'] ?? null,
            'cleanliness' => $validated['cleanliness'] ?? null,
            'satisfaction' => $validated['satisfaction'],
            'comment' => $validated['comment'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'age' => $validated['age'] ?? null,

            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $this->detectDevice($request->userAgent()),

        ]);

        return (new FeedbackResource(
            $feedback->load(['service.windows', 'city', 'subcity', 'woreda', 'submittedBy'])
        ))
            ->additional([
                'success' => true,
                'message' => 'Thank you for your feedback.'
            ])
            ->response()
            ->setStatusCode(201);
    }






    /**
     * Display single feedback
     */
    public function show(Feedback $feedback)
    {

        return new FeedbackResource(

            $feedback->load([
                'service.windows',
                'city',
                'subcity',
                'woreda',
                'submittedBy',
            ])

        );

    }








    /**
     * Delete feedback
     */
    public function destroy(Feedback $feedback)
    {

        $feedback->delete();


        return response()->json([

            'success'=>true,

            'message'=>
                'Feedback deleted successfully.'

        ]);

    }








    /**
     * Detect device
     */
    private function detectDevice(?string $userAgent): string
    {

        $agent = strtolower(
            $userAgent ?? ''
        );


        if(
            str_contains($agent,'android')
            ||
            str_contains($agent,'iphone')
            ||
            str_contains($agent,'mobile')
        ){

            return 'mobile';

        }



        if(
            str_contains($agent,'ipad')
            ||
            str_contains($agent,'tablet')
        ){
            return 'tablet';
        }
        return 'desktop';
    }

    public function report(Request $request)
    {
        $user = $request->user();

        $report = Feedback::query()
            ->join('services', 'feedback.service_id', '=', 'services.id')
            ->forUserLocation($user)

            ->when($request->filled('city_id'), fn($q) =>
            $q->where('feedback.city_id', $request->city_id))

            ->when($request->filled('subcity_id'), fn($q) =>
            $q->where('feedback.subcity_id', $request->subcity_id))

            ->when($request->filled('woreda_id'), fn($q) =>
            $q->where('feedback.woreda_id', $request->woreda_id))

            ->when($request->filled('from_date'), fn($q) =>
            $q->whereDate('feedback.created_at', '>=', $request->from_date))

            ->when($request->filled('to_date'), fn($q) =>
            $q->whereDate('feedback.created_at', '<=', $request->to_date))

            ->select(
                'services.id',
                'services.name as service_name',

                DB::raw("
                SUM(CASE WHEN satisfaction='highly_satisfied' THEN 1 ELSE 0 END)
                as highly_satisfied
            "),

                DB::raw("
                SUM(CASE WHEN satisfaction='satisfied' THEN 1 ELSE 0 END)
                as satisfied
            "),

                DB::raw("
                SUM(CASE WHEN satisfaction='not_satisfied' THEN 1 ELSE 0 END)
                as not_satisfied
            "),

                DB::raw("COUNT(*) as total")
            )

            ->groupBy(
                'services.id',
                'services.name'
            )

            ->orderBy('services.name')
            ->get();

        return response()->json($report);
    }

}
