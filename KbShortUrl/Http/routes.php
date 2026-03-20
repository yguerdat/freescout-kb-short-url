<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\KbShortUrl\Http\Controllers'], function () {

    // Admin settings.
    Route::get('/app/kbshorturl/settings', ['uses' => 'KbShortUrlController@settings', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']])->name('kbshorturl.settings');
    Route::post('/app/kbshorturl/settings', ['uses' => 'KbShortUrlController@settingsSave', 'middleware' => ['auth', 'roles'], 'roles' => ['admin']]);

    // AJAX endpoints (admin only).
    Route::post('/app/kbshorturl/test-connection', ['uses' => 'KbShortUrlController@testConnection', 'middleware' => ['auth', 'roles'], 'roles' => ['admin'], 'laroute' => true])->name('kbshorturl.test_connection');
    Route::post('/app/kbshorturl/bulk-generate', ['uses' => 'KbShortUrlController@bulkGenerate', 'middleware' => ['auth', 'roles'], 'roles' => ['admin'], 'laroute' => true])->name('kbshorturl.bulk_generate');

    // Public AJAX endpoint for the KB frontend widget.
    Route::get('/kbshorturl/article-short-url', ['uses' => 'KbShortUrlController@getArticleShortUrl', 'laroute' => true])->name('kbshorturl.article_short_url');
});
