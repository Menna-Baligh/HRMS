<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Record a critical system action.
     */
    public function record(?User $actor, AuditAction $action, Model $entity, array $metadata = []): Audit
    {
        return Audit::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'metadata' => $metadata,
        ]);
    }

    // search
    public function search(array $filters = [])
    {
        return Audit::query()->with('actor:id,name')->when(
            ! empty($filters['actor_id']), fn ($query) => $query->where('actor_id', $filters['actor_id'])
        )
            ->when(
                ! empty($filters['action']),
                fn ($query) => $query->where(
                    'action',
                    $filters['action']
                )
            )
            ->when(
                ! empty($filters['entity_type']),
                fn ($query) => $query->where(
                    'entity_type',
                    $filters['entity_type']
                )
            )
            ->when(
                ! empty($filters['entity_id']),
                fn ($query) => $query->where(
                    'entity_id',
                    $filters['entity_id']
                )
            )
            ->when(
                ! empty($filters['date_from']),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '>=',
                    $filters['date_from']
                )
            )
            ->when(
                ! empty($filters['date_to']),
                fn ($query) => $query->whereDate(
                    'created_at',
                    '<=',
                    $filters['date_to']
                )
            )
            ->latest()
            ->get();
    }
}
