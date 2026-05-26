<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentProgressResource;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Student;
use App\Services\Api\V1\StudentApiService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentController extends Controller
{
    public function index(Request $request, StudentApiService $studentApiService): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $search = trim((string) $request->query('search', ''));

        $students = $studentApiService
            ->visibleStudentQuery($request->user())
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
                'parents.user',
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), function (Builder $query) use ($request) {
                $query->where('status', $request->query('status'));
            })
            ->when($request->filled('class_room_id'), function (Builder $query) use ($request) {
                $query->where('class_room_id', (int) $request->query('class_room_id'));
            })
            ->when($request->filled('teacher_id'), function (Builder $query) use ($request) {
                $query->where('teacher_id', (int) $request->query('teacher_id'));
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::success(
            data: [
                'students' => StudentResource::collection($students->getCollection())->resolve($request),
            ],
            message: 'Data santri berhasil diambil.',
            meta: [
                'pagination' => $this->paginationMeta($students),
                'filters' => [
                    'search' => $search,
                    'status' => $request->query('status'),
                    'class_room_id' => $request->query('class_room_id'),
                    'teacher_id' => $request->query('teacher_id'),
                ],
            ]
        );
    }

    public function show(
        Request $request,
        StudentApiService $studentApiService,
        string $student
    ): JsonResponse {
        $studentModel = $this->findVisibleStudent(
            request: $request,
            studentApiService: $studentApiService,
            studentId: $student
        );

        if (! $studentModel) {
            return ApiResponse::error(
                message: 'Data santri tidak ditemukan.',
                status: 404
            );
        }

        return ApiResponse::success(
            data: [
                'student' => (new StudentResource($studentModel))->resolve($request),
            ],
            message: 'Detail santri berhasil diambil.'
        );
    }

    public function progress(
        Request $request,
        StudentApiService $studentApiService,
        string $student
    ): JsonResponse {
        $studentModel = $this->findVisibleStudent(
            request: $request,
            studentApiService: $studentApiService,
            studentId: $student
        );

        if (! $studentModel) {
            return ApiResponse::error(
                message: 'Data santri tidak ditemukan.',
                status: 404
            );
        }

        $progress = $studentApiService->calculateProgress($studentModel);

        return ApiResponse::success(
            data: [
                'progress' => (new StudentProgressResource($progress))->resolve($request),
            ],
            message: 'Progress santri berhasil diambil.'
        );
    }

    private function findVisibleStudent(
        Request $request,
        StudentApiService $studentApiService,
        string $studentId
    ): ?Student {
        if (! ctype_digit($studentId)) {
            return null;
        }

        return $studentApiService
            ->visibleStudentQuery($request->user())
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
                'parents.user',
            ])
            ->whereKey((int) $studentId)
            ->first();
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }
}