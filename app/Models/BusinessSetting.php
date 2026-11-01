<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public static function put(string $key, mixed $value, string $type = 'string'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : ($value === null ? null : (string) $value),
                'type' => $type,
            ]
        );
    }
}
