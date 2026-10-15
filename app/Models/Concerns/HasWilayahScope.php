<?php

namespace App\Models\Concerns;

use App\Services\WilayahAccess;
use Illuminate\Database\Eloquent\Builder;

trait HasWilayahScope
{
    public static function bootHasWilayahScope(): void
    {
        static::addGlobalScope('wilayah', fn (Builder $query) => app(WilayahAccess::class)->apply($query));
        static::saving(fn ($model) => app(WilayahAccess::class)->validateWrite($model));
        static::deleting(fn ($model) => app(WilayahAccess::class)->validateWrite($model));
    }
}
