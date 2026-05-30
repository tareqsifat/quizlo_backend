<?php

namespace App\Modules\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'token_type' => $this['token_type'] ?? 'Bearer',
            'expires_in' => $this['expires_in'] ?? null,
            'access_token' => $this['access_token'] ?? null,
            'refresh_token' => $this['refresh_token'] ?? null,
        ];
    }
}
