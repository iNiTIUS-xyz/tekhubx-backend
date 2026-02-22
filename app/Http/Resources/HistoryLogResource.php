<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoryLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $clientName = trim(($this->client->profile->first_name ?? '') . ' ' . ($this->client->profile->last_name ?? ''));
        $providerName = trim(($this->provider->profile->first_name ?? '') . ' ' . ($this->provider->profile->last_name ?? ''));
        $byUser = $clientName ?: $providerName ?: ($this->provider->username ?? $this->client->username ?? 'N/A');

        return [
            'id' => $this->id,
            'by_user' => $byUser,
            'event' => $this->description,
            'date' => $this->date_time,
        ];
    }
}
