<?php

namespace Modules\KbShortUrl\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\KbShortUrl\Services\ShlinkApiService;
use Modules\KbShortUrl\Entities\KbShortUrl;
use Modules\KbShortUrl\Observers\KbArticleObserver;
use Modules\KnowledgeBase\Entities\KbArticle;

class KbShortUrlController extends Controller
{
    /**
     * Display the settings page.
     */
    public function settings()
    {
        return view('kbshorturl::settings/section');
    }

    /**
     * Save module settings.
     */
    public function settingsSave(Request $request)
    {
        $request->validate([
            'shlink_api_url' => 'required|url',
            'shlink_domain'  => 'required|string|max:255',
            'slug_prefix'    => 'nullable|string|max:20|regex:/^[a-z0-9\-]*$/',
            'next_number'    => 'required|integer|min:1',
        ]);

        \Option::set('kbshorturl.shlink_api_url', $request->input('shlink_api_url'));
        \Option::set('kbshorturl.shlink_domain', $request->input('shlink_domain'));
        \Option::set('kbshorturl.slug_prefix', $request->input('slug_prefix', 'kb'));
        \Option::set('kbshorturl.handle_translations', $request->has('handle_translations'));

        // Only update API key if a new one was provided.
        $apiKey = $request->input('shlink_api_key');
        if ($apiKey && $apiKey !== '••••••••') {
            \Option::set('kbshorturl.shlink_api_key_encrypted', ShlinkApiService::encryptApiKey($apiKey));
        }

        // Only update next_number if it's higher than current.
        $currentNext = (int) \Option::get('kbshorturl.next_number', 1);
        $newNext = (int) $request->input('next_number');
        if ($newNext >= $currentNext) {
            \Option::set('kbshorturl.next_number', $newNext);
        }

        \Session::flash('flash_success_floating', __('Settings saved.'));

        return redirect()->route('kbshorturl.settings');
    }

    /**
     * Test the Shlink connection.
     */
    public function testConnection()
    {
        $service = new ShlinkApiService();

        if (!$service->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Please configure and save the Shlink settings first.'),
            ]);
        }

        $result = $service->testConnection();

        return response()->json($result);
    }

    /**
     * Bulk generate short URLs for all published articles that don't have one.
     */
    public function bulkGenerate()
    {
        $service = new ShlinkApiService();

        if (!$service->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('Shlink is not configured.'),
            ]);
        }

        $articles = KbArticle::where('status', KbArticle::STATUS_PUBLISHED)->get();
        $observer = new KbArticleObserver();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($articles as $article) {
            $mailbox = $article->mailbox;
            if (!$mailbox) {
                $skipped++;
                continue;
            }

            $locales = $this->getArticleLocales($article, $mailbox);

            foreach ($locales as $locale) {
                $existing = KbShortUrl::getByArticleAndLocale($article->id, $locale);
                if ($existing) {
                    $skipped++;
                    continue;
                }

                $result = $observer->createShortUrl($article, $mailbox, $locale, $service);
                if ($result) {
                    $created++;
                } else {
                    $errors++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => __(':created short URLs created, :skipped skipped, :errors errors.', [
                'created' => $created,
                'skipped' => $skipped,
                'errors'  => $errors,
            ]),
        ]);
    }

    /**
     * Get the short URL for a specific article (used by the frontend widget via AJAX).
     */
    public function getArticleShortUrl(Request $request)
    {
        $articleId = (int) $request->input('article_id');
        $locale = $request->input('locale', '');

        if (!$articleId) {
            return response()->json(['short_url' => '']);
        }

        $shortUrl = KbShortUrl::getByArticleAndLocale($articleId, $locale);

        // Fallback to default locale.
        if (!$shortUrl && $locale) {
            $shortUrl = KbShortUrl::getByArticleAndLocale($articleId, '');
        }

        return response()->json([
            'short_url' => $shortUrl ? $shortUrl->short_url : '',
        ]);
    }

    /**
     * Get locales for an article.
     */
    private function getArticleLocales(KbArticle $article, $mailbox)
    {
        if (!\Kb::isMultilingual($mailbox)) {
            return [''];
        }

        $handleTranslations = \Option::get('kbshorturl.handle_translations', false);
        if (!$handleTranslations) {
            return [''];
        }

        $locales = \Kb::getLocales($mailbox);
        $result = [];

        foreach ($locales as $locale) {
            if ($article->translatedInLocale($locale)) {
                $result[] = $locale;
            }
        }

        return $result ?: [''];
    }
}
