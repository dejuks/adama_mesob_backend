<?php

namespace App\Http\Controllers\Api;

use App\Models\Window;
use App\Services\WindowService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWindowRequest;
use App\Http\Requests\UpdateWindowRequest;
use Illuminate\Http\Request;
use App\Support\AppRoles;
class WindowController extends Controller
{
    public function __construct(
        protected WindowService $windowService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Window::class);

        $windows = $this->windowService->getAll(
            $request->user(),
            $request->only([
                'level',
                'administrative_level',
                'city_id',
                'subcity_id',
                'woreda_id',
                'search',
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Windows retrieved successfully',
            'data' => $windows,
        ]);
    }

    public function store(StoreWindowRequest $request)
    {
        $this->authorize('create', Window::class);

        $window = $this->windowService->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Window created successfully',
            'data' => $window,
        ], 201);
    }

    public function update(UpdateWindowRequest $request, Window $window)
    {
        $this->authorize('update', $window);

        $updatedWindow = $this->windowService->update(
            $window,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Window updated successfully',
            'data' => $updatedWindow,
        ]);
    }

    public function destroy(Window $window)
    {
        $this->authorize('delete', $window);

        $this->windowService->delete($window);

        return response()->json([
            'success' => true,
            'message' => 'Window deleted successfully',
        ]);
    }

    public function services(Request $request, Window $window)
    {
        $level = $request->user()
            ? (AppRoles::userLevel($request->user())
                ?: $window->administrative_level)
            : $window->administrative_level;

        $services = $window->services()
            ->where('services.status', 'active')
            ->wherePivot('assignment_level', $level)
            ->orderBy('services.name')
            ->get()
            ->unique('id')
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Services retrieved successfully',
            'data' => $services,
        ]);
    }
}
