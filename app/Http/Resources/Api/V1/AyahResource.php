<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AyahResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'surah_id' => $this->surah_id,
            'ayah_number' => $this->ayah_number,
            'juz' => $this->juz,
            'text_ar' => $this->text_ar,
            'translation_id' => $this->translation_id,

            'surah' => $this->whenLoaded('surah', function () {
                return $this->surah ? [
                    'id' => $this->surah->id,
                    'number' => $this->surah->number,
                    'name_ar' => $this->surah->name_ar,
                    'name_latin' => $this->surah->name_latin,
                    'total_ayah' => $this->surah->total_ayah,
                    'juz_start' => $this->surah->juz_start,
                    'juz_end' => $this->surah->juz_end,
                ] : null;
            }),
        ];
    }
}
