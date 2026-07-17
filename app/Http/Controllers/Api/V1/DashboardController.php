<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DashboardSummaryResource;
use App\Services\Api\V1\DashboardApiService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin(Request $request, DashboardApiService $dashboardApiService): JsonResponse
    {
        return $this->respond($request, $dashboardApiService, 'admin');
    }

    public function teacher(Request $request, DashboardApiService $dashboardApiService): JsonResponse
    {
        return $this->respond($request, $dashboardApiService, 'teacher');
    }

    public function parent(Request $request, DashboardApiService $dashboardApiService): JsonResponse
    {
        return $this->respond($request, $dashboardApiService, 'parent');
    }

    public function student(Request $request, DashboardApiService $dashboardApiService): JsonResponse
    {
        return $this->respond($request, $dashboardApiService, 'student');
    }

    private function respond(
        Request $request,
        DashboardApiService $dashboardApiService,
        string $dashboard
    ): JsonResponse {
        $user = $request->user()->loadMissing('role');

        if (! $dashboardApiService->canAccessDashboard($user, $dashboard)) {
            return ApiResponse::error(
                message: 'Anda tidak memiliki akses ke dashboard ini.',
                status: 403
            );
        }

        $summary = $dashboardApiService->build($user, $dashboard);

        return ApiResponse::success(
            data: [
                'dashboard' => (new DashboardSummaryResource($summary))->resolve($request),
            ],
            message: 'Ringkasan dashboard berhasil diambil.'
        );
    }
}
