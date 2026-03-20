<?php

namespace Modules\KbShortUrl\Observers;

use Modules\KbShortUrl\Services\ShlinkApiService;
use Modules\KbShortUrl\Entities\KbShortUrl;
use Modules\KnowledgeBase\Entities\KbArticle;

class KbArticleObserver
{
    /**
     * Handle the "saved" event (fires on both create and update).
     */
    public function saved(KbArticle $article)
    {
        $shlinkService = new ShlinkApiService();
        if (!$shlinkService->isConfigured()) {
            return;
        }

        if ($article->isPublished()) {
            $this->createOrUpdateShortUrls($article, $shlinkService);
        } else {
            // Article unpublished: remove short URLs.
            $this->deleteShortUrls($article, $shlinkService);
        }
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(KbArticle $article)
    {
        $shlinkService = new ShlinkApiService();
        if (!$shlinkService->isConfigured()) {
            return;
        }

        $this->deleteShortUrls($article, $shlinkService);
    }

    /**
     * Create or update short URLs for a published article.
     */
    private function createOrUpdateShortUrls(KbArticle $article, ShlinkApiService $shlinkService)
    {
        $mailbox = $article->mailbox;
        if (!$mailbox) {
            return;
        }

        $locales = $this->getArticleLocales($article, $mailbox);

        foreach ($locales as $locale) {
            $existing = KbShortUrl::getByArticleAndLocale($article->id, $locale);

            if ($existing) {
                // Update the long URL if it changed.
                $longUrl = $this->buildArticleLongUrl($article, $mailbox, $locale);
                if ($existing->long_url !== $longUrl) {
                    $shlinkService->updateShortUrl($existing->short_code, $longUrl, $this->getArticleTitle($article, $locale));
                    $existing->update(['long_url' => $longUrl]);
                }
            } else {
                $this->createShortUrl($article, $mailbox, $locale, $shlinkService);
            }
        }
    }

    /**
     * Create a new short URL for an article/locale combination.
     */
    public function createShortUrl(KbArticle $article, $mailbox, $locale, ShlinkApiService $shlinkService)
    {
        $prefix = \Option::get('kbshorturl.slug_prefix', 'kb');
        $longUrl = $this->buildArticleLongUrl($article, $mailbox, $locale);
        $title = $this->getArticleTitle($article, $locale);

        // Get next available number.
        $number = (int) \Option::get('kbshorturl.next_number', 1);
        $maxRetries = 10;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $shortCode = $prefix . $number;
            if ($locale !== '' && $locale !== \Kb::defaultLocale($mailbox)) {
                $shortCode .= '-' . $locale;
            }

            $result = $shlinkService->createShortUrl($longUrl, $shortCode, $title);

            if ($result['success']) {
                // Save locally.
                KbShortUrl::create([
                    'article_id'   => $article->id,
                    'locale'       => $locale,
                    'short_number' => $number,
                    'short_code'   => $shortCode,
                    'short_url'    => $result['short_url'],
                    'long_url'     => $longUrl,
                ]);

                // Increment the global counter (only for the base locale).
                if ($locale === '' || $locale === \Kb::defaultLocale($mailbox)) {
                    \Option::set('kbshorturl.next_number', $number + 1);
                }

                \Log::info('KbShortUrl: Created short URL ' . $result['short_url'] . ' for article #' . $article->id . ($locale ? ' [' . $locale . ']' : ''));
                return true;
            }

            if ($result['error'] === 'slug_taken') {
                // Slug conflict, try next number.
                $number++;
                \Option::set('kbshorturl.next_number', $number + 1);
                \Log::info('KbShortUrl: Slug "' . $shortCode . '" already taken, trying next number.');
                continue;
            }

            // Other API error: stop trying.
            \Log::error('KbShortUrl: Failed to create short URL for article #' . $article->id . ': ' . ($result['message'] ?? 'Unknown error'));
            return false;
        }

        \Log::error('KbShortUrl: Exhausted retries creating short URL for article #' . $article->id);
        return false;
    }

    /**
     * Delete all short URLs for an article.
     */
    private function deleteShortUrls(KbArticle $article, ShlinkApiService $shlinkService)
    {
        $shortUrls = KbShortUrl::getByArticleId($article->id);

        foreach ($shortUrls as $shortUrl) {
            $shlinkService->deleteShortUrl($shortUrl->short_code);
            $shortUrl->delete();
            \Log::info('KbShortUrl: Deleted short URL ' . $shortUrl->short_code . ' for article #' . $article->id);
        }
    }

    /**
     * Get the list of locales to create short URLs for.
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

    /**
     * Build the full public URL for a KB article.
     */
    private function buildArticleLongUrl(KbArticle $article, $mailbox, $locale = '')
    {
        if ($locale && $locale !== \Kb::defaultLocale($mailbox)) {
            $article->setLocale($locale);
        }

        $url = $article->urlFrontend($mailbox, null, $locale ?: null);

        // Reset locale.
        $article->setLocale('');

        return $url;
    }

    /**
     * Get the article title for a given locale.
     */
    private function getArticleTitle(KbArticle $article, $locale = '')
    {
        if ($locale) {
            return $article->getAttributeInLocale('title', $locale);
        }
        return $article->title;
    }
}
