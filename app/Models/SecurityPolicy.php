<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SecurityPolicy extends Model
{
    protected $table = 'security_policies';

    protected $fillable = [
        'key',
        'name',
        'value',
        'type',
        'description',
        'updated_by',
    ];

    public static function get(string $key, $default = null)
    {
        return Cache::remember("sec_policy_{$key}", 300, function () use ($key, $default) {
            try {
                $policy = self::where('key', $key)->first();
                if (!$policy) {
                    return $default;
                }
                return match ($policy->type) {
                    'integer' => (int) $policy->value,
                    'boolean' => in_array(strtolower($policy->value), ['1', 'true', 'yes', 'on']),
                    'json' => json_decode($policy->value, true) ?: $default,
                    default => $policy->value,
                };
            } catch (\Throwable $e) {
                return $default;
            }
        });
    }

    public static function set(string $key, $value, ?int $updatedBy = null): void
    {
        $policy = self::where('key', $key)->first();
        if ($policy) {
            $policy->value = is_array($value) ? json_encode($value) : (string) $value;
            $policy->updated_by = $updatedBy;
            $policy->save();
        }
        Cache::forget("sec_policy_{$key}");
    }
}
