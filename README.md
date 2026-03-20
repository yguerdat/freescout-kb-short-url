# KB Short URL — FreeScout Module

Generate short URLs for your [FreeScout](https://freescout.net/) Knowledge Base articles using [Shlink](https://shlink.io/), a self-hosted URL shortener.

Share KB articles easily via WhatsApp, Telegram, SMS, or email with clean, short links like `es.ink/kb1`, `es.ink/kb2`, etc.

![License](https://img.shields.io/badge/license-AGPL--3.0-blue)
![FreeScout](https://img.shields.io/badge/FreeScout-1.8.58+-green)

## Features

- **Automatic short URL creation** — A short URL is generated via Shlink every time a KB article is published
- **Automatic cleanup** — Short URLs are deleted when articles are unpublished or deleted
- **Numeric slugs with prefix** — Produces `es.ink/kb1`, `es.ink/kb2`... (prefix is configurable)
- **Multi-language support** — Optionally creates per-locale URLs (`kb42`, `kb42-en`, `kb42-de`)
- **Share widget** — A floating share button appears on KB article pages with:
  - Copy to clipboard
  - Share via WhatsApp
  - Share via Telegram
  - Share via Email
- **Bulk generation** — One-click button to generate short URLs for all existing published articles
- **Conflict-safe** — If a slug is already taken in Shlink (used for other purposes), the module automatically tries the next number
- **Secure** — API key is encrypted at rest, all admin routes are protected, no client-side API exposure
- **Configurable** — Domain, prefix, counter start, and translation handling are all configurable
- **Bilingual** — Admin interface available in English and French

## Requirements

- [FreeScout](https://github.com/freescout-help-desk/freescout) >= 1.8.58
- [Knowledge Base module](https://freescout.net/module/knowledge-base/) >= 1.0.19
- A running [Shlink](https://shlink.io/) instance (v3+ recommended) with API access

## Installation

1. Download or clone this repository
2. Copy the `KbShortUrl` folder to your FreeScout `Modules/` directory:
   ```bash
   cp -r KbShortUrl /path/to/freescout/Modules/
   ```
3. Run the database migration:
   ```bash
   cd /path/to/freescout
   php artisan migrate
   ```
4. Rebuild the module assets:
   ```bash
   php artisan freescout:module-build
   ```
5. Go to **Manage → Modules** in FreeScout and activate **KB Short URL**

## Configuration

After activation, go to **Manage → KB Short URL** in the admin panel:

| Setting | Description | Example |
|---|---|---|
| **Shlink API URL** | Base URL of your Shlink instance | `https://shlink.example.com` |
| **Shlink API Key** | API key generated via `shlink api-key:generate` | `abc123...` |
| **Short URL Domain** | The domain configured in Shlink | `es.ink` |
| **Slug Prefix** | Prefix before the number | `kb` → produces `es.ink/kb1` |
| **Next Number** | Starting counter (auto-increments) | `1` |
| **Handle Translations** | Create separate short URLs per language | Checkbox |

### Getting a Shlink API Key

```bash
# If using Docker:
docker exec -it shlink shlink api-key:generate

# If installed directly:
php bin/cli api-key:generate
```

## How It Works

### Automatic Flow

```
Article published  → Observer creates short URL via Shlink API → Stored locally
Article unpublished → Observer deletes short URL from Shlink → Removed locally
Article deleted    → Observer deletes short URL from Shlink → Removed locally
```

### Multi-Language (Optional)

When enabled, translations get their own short URLs with a locale suffix:

| Locale | Short URL |
|---|---|
| French (default) | `es.ink/kb42` |
| English | `es.ink/kb42-en` |
| German | `es.ink/kb42-de` |

### Share Widget

On every KB article page with a short URL, a floating share button appears in the bottom-right corner. Clicking it reveals:

1. The short URL with a **Copy** button
2. **WhatsApp**, **Telegram**, and **Email** share buttons

### Bulk Generation

For existing articles, click **"Generate missing short URLs"** on the settings page. This creates short URLs for all published articles that don't already have one.

## File Structure

```
KbShortUrl/
├── Config/config.php                    # Module configuration
├── Database/Migrations/                 # Database schema
├── Entities/KbShortUrl.php             # Eloquent model
├── Http/
│   ├── Controllers/KbShortUrlController.php
│   └── routes.php
├── Observers/KbArticleObserver.php      # Hooks into KB article lifecycle
├── Providers/KbShortUrlServiceProvider.php
├── Services/ShlinkApiService.php        # Shlink API client
├── Resources/
│   ├── views/                           # Blade templates
│   └── lang/                            # EN + FR translations
├── Public/
│   ├── css/module.css                   # Share widget styles
│   └── js/
│       ├── module.js                    # Admin JS
│       └── kb-widget.js                # Frontend share widget
├── module.json
└── start.php
```

## Global Counter

The counter is global (not per-mailbox) because Shlink may be shared with other applications. If a slug is already taken in Shlink (e.g., someone manually created `kb5`), the module automatically increments and retries up to 10 times.

## Security

- Shlink API key is **encrypted** using Laravel's `encrypt()` before storage
- All admin routes require **authentication + admin role**
- The API key is **never exposed** to frontend JavaScript
- All admin forms are protected with **CSRF tokens**
- The public short URL lookup endpoint only returns the short URL string, no sensitive data

## License

[AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.en.html) — Same as FreeScout.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## Credits

- [FreeScout](https://freescout.net/) — Free open-source help desk
- [Shlink](https://shlink.io/) — Self-hosted URL shortener
