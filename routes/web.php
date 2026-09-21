<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['success' => true]);
});

Route::get('sitemap.xml', function () {
    return \Illuminate\Support\Facades\Redirect::to('sitemap.xml');
});

Route::get('/db', function () {
    abort_unless(app()->environment('local'), 404);

    Artisan::call('route:cache');

    return 'Success';
});
