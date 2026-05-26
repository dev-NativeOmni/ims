<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardSummaryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'dashboard' => data_get($this->resource, 'dashboard'),
            'role' => data_get($this->resource, 'role'),
            'generated_at' => data_get($this->resource, 'generated_at'),

            'summary' => data_get($this->resource, 'summary', []),
            'progress' => data_get($this->resource, 'progress', []),
            'today' => data_get($this->resource, 'today', []),
            'recent' => data_get($this->resource, 'recent', []),
            'alerts' => data_get($this->resource, 'alerts', []),
        ];
    }
}