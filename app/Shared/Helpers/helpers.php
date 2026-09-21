<?php

use Illuminate\Support\Facades\File;

if (!function_exists('fileUploader')) {
    function fileUploader($file, $folder)
    {

        $extention = $file->extension();
        $file_name = uniqid() . '_' . strtotime("now") . '.' . $extention;

        return $file->storeAs('uploads/' . $folder, $file_name, 'public');
    }
}

if (!function_exists('getFilePath')) {
    function getFilePath($path)
    {
        return asset('storage/' . $path);
    }
}

if (!function_exists('deleteFile')) {
    function deleteFile($path)
    {
        if (File::exists(public_path('storage/' . $path))) {
            File::delete(public_path('storage/' . $path));
        }
    }
}

if (!function_exists('localeSession')) {
    function localeSession($lang)
    {
        session()->put('my_locale', $lang);
    }
}

if (!function_exists('getTransAttribute')) {

    function getTransAttribute($model, $key = '')
    {
        $lang = app()->getLocale();
        $another_lang = $lang == 'ar' ? 'en' : "ar";

        return empty(@$model["{$key}_{$lang}"]) ? @$model["{$key}_{$another_lang}"] : @$model["{$key}_{$lang}"];
    }
}

if (! function_exists('website_image')) {
    /**
     * Resolve a website image SEO record by stable key.
     */
    function website_image(string $key): ?\App\Models\WebsiteImage
    {
        try {
            return \App\Models\WebsiteImage::findByKey($key);
        } catch (\Throwable) {
            return null;
        }
    }
}

if (! function_exists('website_image_url')) {
    function website_image_url(string $key, ?string $fallback = null): ?string
    {
        $image = website_image($key);

        return $image?->url ?: $fallback;
    }
}

if (! function_exists('website_image_alt')) {
    function website_image_alt(string $key, string $fallback = ''): string
    {
        $image = website_image($key);
        $alt = $image?->alt();

        return filled($alt) ? (string) $alt : $fallback;
    }
}

if (! function_exists('website_image_title')) {
    function website_image_title(string $key, string $fallback = ''): string
    {
        $image = website_image($key);
        $title = $image?->metaTitle();

        return filled($title) ? (string) $title : $fallback;
    }
}
