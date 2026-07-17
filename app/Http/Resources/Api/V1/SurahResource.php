<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurahResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name_ar' => $this->name_ar,
            'name_latin' => $this->name_latin,
            'total_ayah' => $this->total_ayah,
            'juz_start' => $this->juz_start,
            'juz_end' => $this->juz_end,

            'ayahs_count' => $this->whenCounted('ayahs'),

            'ayahs' => $this->whenLoaded('ayahs', function () use ($request) {
                return AyahResource::collection($this->ayahs)->resolve($request);
            }),

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
