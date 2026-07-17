<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentProgressResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $student = data_get($this->resource, 'student');
        $latestHafalan = data_get($this->resource, 'hafalan.latest');
        $latestMurajaah = data_get($this->resource, 'murajaah.latest');
        $latestTarget = data_get($this->resource, 'targets.latest');

        return [
            'student' => $student
                ? (new StudentResource($student))->resolve($request)
                : null,

            'quran' => data_get($this->resource, 'quran', []),

            'hafalan' => [
                'total_records' => data_get($this->resource, 'hafalan.total_records', 0),
                'passed_records' => data_get($this->resource, 'hafalan.passed_records', 0),
                'repeat_records' => data_get($this->resource, 'hafalan.repeat_records', 0),
                'average_score' => data_get($this->resource, 'hafalan.average_score', 0),
                'latest' => $this->hafalanSummary($latestHafalan),
            ],

            'murajaah' => [
                'total_records' => data_get($this->resource, 'murajaah.total_records', 0),
                'passed_records' => data_get($this->resource, 'murajaah.passed_records', 0),
                'repeat_records' => data_get($this->resource, 'murajaah.repeat_records', 0),
                'average_score' => data_get($this->resource, 'murajaah.average_score', 0),
                'latest' => $this->murajaahSummary($latestMurajaah),
            ],

            'targets' => [
                'total_targets' => data_get($this->resource, 'targets.total_targets', 0),
                'active_targets' => data_get($this->resource, 'targets.active_targets', 0),
                'completed_targets' => data_get($this->resource, 'targets.completed_targets', 0),
                'missed_targets' => data_get($this->resource, 'targets.missed_targets', 0),
                'latest' => $this->targetSummary($latestTarget),
            ],
        ];
    }

    private function hafalanSummary($record): ?array
    {
        if (! $record) {
            return null;
        }

        return [
            'id' => $record->id,
            'surah' => $record->surah ? [
                'id' => $record->surah->id,
                'number' => $record->surah->number,
                'name_latin' => $record->surah->name_latin,
                'total_ayah' => $record->surah->total_ayah,
            ] : null,
            'ayah_start' => $record->ayah_start,
            'ayah_end' => $record->ayah_end,
            'submission_type' => $record->submission_type,
            'score' => $record->score,
            'status' => $record->status,
            'submitted_at' => optional($record->submitted_at)->format('Y-m-d'),
            'teacher_name' => $record->teacher?->user?->name,
        ];
    }

    private function murajaahSummary($record): ?array
    {
        if (! $record) {
            return null;
        }

        return [
            'id' => $record->id,
            'surah' => $record->surah ? [
                'id' => $record->surah->id,
                'number' => $record->surah->number,
                'name_latin' => $record->surah->name_latin,
                'total_ayah' => $record->surah->total_ayah,
            ] : null,
            'ayah_start' => $record->ayah_start,
            'ayah_end' => $record->ayah_end,
            'fluency_score' => $record->fluency_score,
            'tajwid_score' => $record->tajwid_score,
            'makhraj_score' => $record->makhraj_score,
            'overall_score' => $record->overall_score,
            'status' => $record->status,
            'reviewed_at' => optional($record->reviewed_at)->format('Y-m-d'),
            'teacher_name' => $record->teacher?->user?->name,
        ];
    }

    private function targetSummary($target): ?array
    {
        if (! $target) {
            return null;
        }

        return [
            'id' => $target->id,
            'surah' => $target->surah ? [
                'id' => $target->surah->id,
                'number' => $target->surah->number,
                'name_latin' => $target->surah->name_latin,
                'total_ayah' => $target->surah->total_ayah,
            ] : null,
            'ayah_start' => $target->ayah_start,
            'ayah_end' => $target->ayah_end,
            'target_date' => optional($target->target_date)->format('Y-m-d'),
            'status' => $target->status,
            'completed_at' => optional($target->completed_at)->toISOString(),
            'teacher_name' => $target->teacher?->user?->name,
        ];
    }
}
