<?php

namespace Modules\KbShortUrl\Entities;

use Illuminate\Database\Eloquent\Model;

class KbShortUrl extends Model
{
    protected $table = 'kb_short_urls';

    protected $fillable = [
        'article_id',
        'locale',
        'short_number',
        'short_code',
        'short_url',
        'long_url',
    ];

    public function article()
    {
        return $this->belongsTo('Modules\KnowledgeBase\Entities\KbArticle', 'article_id');
    }

    /**
     * Get all short URLs for a given article.
     */
    public static function getByArticleId($articleId)
    {
        return self::where('article_id', $articleId)->get();
    }

    /**
     * Get the short URL for a specific article and locale.
     */
    public static function getByArticleAndLocale($articleId, $locale = '')
    {
        return self::where('article_id', $articleId)
            ->where('locale', $locale)
            ->first();
    }
}
