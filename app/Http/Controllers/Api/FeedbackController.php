<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{


    /**
     * Display feedback list (Admin)
     */
    public function index(Request $request)
    {

        $feedbacks = Feedback::with([
            'service.windows'
        ])

            ->when(
                $request->service_id,
                function ($query) use ($request) {

                    $query->where(
                        'service_id',
                        $request->service_id
                    );

                }
            )


            ->when(
                $request->rating,
                function ($query) use ($request) {

                    $query->where(
                        'overall_rating',
                        $request->rating
                    );

                }
            )


            ->when(
                $request->satisfaction,
                function ($query) use ($request) {

                    $query->where(
                        'satisfaction',
                        $request->satisfaction
                    );

                }
            )


            ->when(
                $request->date,
                function ($query) use ($request) {

                    $query->whereDate(
                        'created_at',
                        $request->date
                    );

                }
            )


            ->latest()
            ->paginate(20);



        return FeedbackResource::collection(
            $feedbacks
        );

    }






    /**
     * Store public feedback
     */
    public function store(Request $request)
    {

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


        $service = Service::where(
            'id',
            $validated['service_id']
        )

            ->where(
                'status',
                'active'
            )

            ->firstOrFail();







        /*
        |--------------------------------------------------------------------------
        | Create Feedback
        |--------------------------------------------------------------------------
        */


        $feedback = Feedback::create([


            'service_id' => $service->id,



            'satisfaction'
            => $validated['satisfaction'],


            'comment'
            => $validated['comment'] ?? null,


            'gender'
            => $validated['gender'] ?? null,



            'ip_address'
            => $request->ip(),


            'user_agent'
            => $request->userAgent(),


            'device'
            => $this->detectDevice(
                $request->userAgent()
            ),

        ]);
        return (new FeedbackResource(

            $feedback->load([
                'service.windows'
            ])

        ))

            ->additional([

                'success' => true,

                'message' =>
                    'Thank you for your feedback.'

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
                'service.windows'
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

}
