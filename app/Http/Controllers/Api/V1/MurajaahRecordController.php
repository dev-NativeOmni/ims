<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MurajaahRecordResource;
use App\Models\MurajaahRecord;
use App\Services\Api\V1\StudentApiService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class MurajaahRecordController extends Controller
{
    public function index(Request $request, StudentApiService $studentApiService): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => [
                'nullable',
                'integer',
            ],
            'teacher_id' => [
                'nullable',
                'integer',
            ],
            'surah_id' => [
                'nullable',
                'integer',
            ],
            'status' => [
                'nullable',
                Rule::in(['passed', 'repeat', 'needs_improvement']),
            ],
            'from' => [
                'nullable',
                'date',
            ],
            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
            ],
        ]);

        $perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 50);
        $search = trim((string) ($validated['search'] ?? ''));

        $visibleStudentIdsQuery = $studentApiService
            ->visibleStudentQuery($request->user())
            ->select('students.id');

        $records = MurajaahRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIdsQuery)
            ->when(isset($validated['student_id']), function (Builder $query) use ($validated) {
                $query->where('student_id', (int) $validated['student_id']);
            })
            ->when(isset($validated['teacher_id']), function (Builder $query) use ($validated) {
                $query->where('teacher_id', (int) $validated['teacher_id']);
            })
            ->when(isset($validated['surah_id']), function (Builder $query) use ($validated) {
                $query->where('surah_id', (int) $validated['surah_id']);
            })
            ->when(isset($validated['status']), function (Builder $query) use ($validated) {
                $query->where('status', $validated['status']);
            })
            ->when(isset($validated['from']), function (Builder $query) use ($validated) {
                $query->whereDate('reviewed_at', '>=', $validated['from']);
            })
            ->when(isset($validated['to']), function (Builder $query) use ($validated) {
                $query->whereDate('reviewed_at', '<=', $validated['to']);
            })
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('notes', 'like', "%{$search}%")
                        ->orWhereHas('student', function (Builder $studentQuery) use ($search) {
                            $studentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('student_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('surah', function (Builder $surahQuery) use ($search) {
                            $surahQuery->where('name_latin', 'like', "%{$search}%")
                                ->orWhere('name_ar', 'like', "%{$search}%");
                        })
                        ->orWhereHas('teacher.user', function (Builder $teacherUserQuery) use ($search) {
                            $teacherUserQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('reviewed_at')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return ApiResponse::success(
            data: [
                'murajaah_records' => MurajaahRecordResource::collection($records->getCollection())->resolve($request),
            ],
            message: 'Data murajaah berhasil diambil.',
            meta: [
                'pagination' => $this->paginationMeta($records),
                'filters' => [
                    'student_id' => $validated['student_id'] ?? null,
                    'teacher_id' => $validated['teacher_id'] ?? null,
                    'surah_id' => $validated['surah_id'] ?? null,
                    'status' => $validated['status'] ?? null,
                    'from' => $validated['from'] ?? null,
                    'to' => $validated['to'] ?? null,
                    'search' => $search,
                ],
            ]
        );
    }

    public function show(
        Request $request,
        StudentApiService $studentApiService,
        string $murajaahRecord
    ): JsonResponse {
        if (! ctype_digit($murajaahRecord)) {
            return ApiResponse::error(
                message: 'Data murajaah tidak ditemukan.',
                status: 404
            );
        }

        $visibleStudentIdsQuery = $studentApiService
            ->visibleStudentQuery($request->user())
            ->select('students.id');

        $record = MurajaahRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIdsQuery)
            ->whereKey((int) $murajaahRecord)
            ->first();

        if (! $record) {
            return ApiResponse::error(
                message: 'Data murajaah tidak ditemukan.',
                status: 404
            );
        }

        return ApiResponse::success(
            data: [
                'murajaah_record' => (new MurajaahRecordResource($record))->resolve($request),
            ],
            message: 'Detail murajaah berhasil diambil.'
        );
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
