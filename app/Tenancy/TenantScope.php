<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** @implements Scope<Model> */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound(CurrentTenant::class)) {
            return;
        }

        $context = app(CurrentTenant::class);

        if ($context->hasTenant()) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->tenant()->getKey());
        }
    }
}
