<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuid {
    /**
     * Generates a uuid when a model instance is created
     *
     * @return void
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
        if (new self() instanceof Pivot) {
            Pivot::creating(function ($pivot) {
                $pivot->{$model->getKeyName()} = (string) Str::uuid();
            });
        }
    }

    /**
     * Returns false to indicate auto incrementation is disabled
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Returns primary key type
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
