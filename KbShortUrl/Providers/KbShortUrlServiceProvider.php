<?php

namespace Modules\KbShortUrl\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

define('KBSHORTURL_MODULE', 'kbshorturl');

class KbShortUrlServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');
        $this->hooks();

        // Register the observer on KB articles.
        if (\Module::isActive('knowledgebase')) {
            \Modules\KnowledgeBase\Entities\KbArticle::observe(
                \Modules\KbShortUrl\Observers\KbArticleObserver::class
            );
        }
    }

    public function register()
    {
        //
    }

    public function hooks()
    {
        // Add module CSS.
        \Eventy::addFilter('stylesheets', function ($styles) {
            $styles[] = \Module::getPublicPath(KBSHORTURL_MODULE) . '/css/module.css';
            return $styles;
        });

        // Add module JS.
        \Eventy::addFilter('javascripts', function ($javascripts) {
            $javascripts[] = \Module::getPublicPath(KBSHORTURL_MODULE) . '/js/module.js';
            return $javascripts;
        });

        // JS translations.
        \Eventy::addAction('js.lang.messages', function () {
            ?>
            "kbshorturl.copy": "<?php echo __('Copy') ?>",
            "kbshorturl.copied": "<?php echo __('Copied!') ?>",
            "kbshorturl.share": "<?php echo __('Share') ?>",
            "kbshorturl.share_via_whatsapp": "<?php echo __('WhatsApp') ?>",
            "kbshorturl.share_via_telegram": "<?php echo __('Telegram') ?>",
            "kbshorturl.share_via_email": "<?php echo __('Email') ?>",
            "kbshorturl.short_link": "<?php echo __('Short link') ?>",
            <?php
        });

        // Add settings link in the admin menu under "Manage".
        \Eventy::addAction('menu.manage.append', function () {
            if (auth()->user() && auth()->user()->isAdmin()) {
                echo \View::make('kbshorturl::partials/menu_manage')->render();
            }
        });

        // Settings page view.
        \Eventy::addFilter('settings.view', function ($view, $section) {
            if ($section !== 'kbshorturl') {
                return $view;
            }
            return 'kbshorturl::settings/section';
        }, 20, 2);

        // Inject the share widget JS + CSS into KB frontend pages.
        \Eventy::addFilter('kb.javascripts', function ($scripts) {
            $scripts[] = \Module::getPublicPath(KBSHORTURL_MODULE) . '/js/kb-widget.js';
            return $scripts;
        });

        // Inject the short URL data into KB article pages via layout.body_bottom.
        // This fires on all pages, but the widget JS only activates on article pages.
        \Eventy::addAction('layout.body_bottom', function () {
            if (!preg_match('#/hc/[^/]+/(\d+)#', \Request::getRequestUri(), $m)) {
                return;
            }
            $articleId = (int) $m[1];
            if (!$articleId) {
                return;
            }

            // Determine the current locale from the URL.
            $locale = '';
            if (preg_match('#^/([a-z]{2}(?:-[a-z]{2})?)/hc/#i', \Request::getRequestUri(), $lm)) {
                $locale = $lm[1];
            }

            $shortUrl = \Modules\KbShortUrl\Entities\KbShortUrl::getByArticleAndLocale($articleId, $locale);
            if (!$shortUrl && $locale) {
                $shortUrl = \Modules\KbShortUrl\Entities\KbShortUrl::getByArticleAndLocale($articleId, '');
            }
            if ($shortUrl) {
                echo '<div id="kb-short-url-data" data-short-url="' . htmlspecialchars($shortUrl->short_url, ENT_QUOTES) . '" style="display:none;"></div>';
            }
        });
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('kbshorturl.php'),
        ], 'config');
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'kbshorturl');
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/kbshorturl');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([$sourcePath => $viewPath], 'views');
        $this->loadViewsFrom(array_merge(
            array_map(function ($path) {
                return $path . '/modules/kbshorturl';
            }, \Config::get('view.paths')),
            [$sourcePath]
        ), 'kbshorturl');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/kbshorturl');
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'kbshorturl');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'kbshorturl');
        }
        $this->loadJsonTranslationsFrom(__DIR__ . '/../Resources/lang');
    }

    public function provides()
    {
        return [];
    }
}
