<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'provider_penalty' => $this->provider_penalty,
            'notification_enabled' => $this->notification_enabled,
            'auto_dispatch' => $this->auto_dispatch,
            'other' => $this->other,
            'default_client_id' => $this->default_client_id,
            'default_client' => $this->default_client?->client_title,
            'project_manager_id' => $this->project_manager_id,
            'project_manager' => $this->project_manager?->name,
            'bank_account_id' => $this->bank_account_id,
            'bank_account' => $this->bank_account?->account_number,
            'secondary_account_owner_id' => $this->secondary_acc,
        ];
    }
}
