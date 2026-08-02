<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PublicServiceService;

class PublicServiceController extends Controller
{
    public function __construct(
        protected PublicServiceService $publicServiceService
    ) {}

    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Services retrieved successfully',
            'data' => $this->publicServiceService->getAll($request),
        ]);
    }

    public function featured()
    {
        return response()->json([
            'success' => true,
            'message' => 'Featured services retrieved successfully',
            'data' => $this->publicServiceService->featured(),
        ]);
    }

    public function show(Service $service)
    {
        return response()->json([
            'success' => true,
            'message' => 'Service retrieved successfully',
            'data' => $service->load([
                'windows:id,name,title,city_title,subcity_title,woreda_title,administrative_level,availability',
                'criteria' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ]),
        ]);
    }

    public function windowServices(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Window services retrieved successfully',
            'data' => $this->publicServiceService->groupByWindow($request),
        ]);
    }

    /**
     * Windows available at the public feedback kiosk. Unlike the admin
     * /windows endpoint, this requires no login and no administrative
     * level — a customer standing at a window just needs to see it here.
     */
    public function feedbackWindows()
    {
        $windows = \App\Models\Window::query()
            ->whereHas('services', fn ($query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'title',
                'city_title',
                'subcity_title',
                'woreda_title',
                'city_id',
                'subcity_id',
                'woreda_id',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Windows retrieved successfully',
            'data' => $windows,
        ]);
    }
}
