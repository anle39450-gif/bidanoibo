<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting by key.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Remove a setting by key.
     */
    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
    }

    /**
     * Get the URL for the application logo.
     */
    public static function getLogoUrl(): ?string
    {
        $customLogo = static::get('app_logo');

        if ($customLogo && Storage::disk('public')->exists($customLogo)) {
            return asset('storage/'.$customLogo);
        }

        // Check if there is a manual logo placed in public/images
        if (file_exists(public_path('images/logo.png'))) {
            return asset('images/logo.png');
        }

        if (file_exists(public_path('images/logo.svg'))) {
            return asset('images/logo.svg');
        }

        if (file_exists(public_path('logo.png'))) {
            return asset('logo.png');
        }

        return null;
    }
}
