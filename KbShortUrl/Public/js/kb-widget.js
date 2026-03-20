/**
 * KB Short URL - Share Widget
 * Injected into Knowledge Base frontend article pages.
 */
(function() {
    'use strict';

    // Wait for the page to be ready.
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        var dataEl = document.getElementById('kb-short-url-data');
        if (!dataEl) {
            return; // No short URL available for this article.
        }

        var shortUrl = dataEl.getAttribute('data-short-url');
        if (!shortUrl) {
            return;
        }

        createWidget(shortUrl);
    }

    function createWidget(shortUrl) {
        var widget = document.createElement('div');
        widget.className = 'kbsu-widget';
        widget.innerHTML = buildPanel(shortUrl) + buildToggleButton();
        document.body.appendChild(widget);

        // Toggle panel.
        var toggleBtn = widget.querySelector('.kbsu-toggle-btn');
        var panel = widget.querySelector('.kbsu-panel');

        toggleBtn.addEventListener('click', function() {
            panel.classList.toggle('kbsu-open');
        });

        // Close on outside click.
        document.addEventListener('click', function(e) {
            if (!widget.contains(e.target)) {
                panel.classList.remove('kbsu-open');
            }
        });

        // Copy button.
        var copyBtn = widget.querySelector('.kbsu-copy-btn');
        var urlInput = widget.querySelector('.kbsu-url-input');

        copyBtn.addEventListener('click', function() {
            urlInput.select();
            urlInput.setSelectionRange(0, 99999);

            if (navigator.clipboard) {
                navigator.clipboard.writeText(shortUrl).then(function() {
                    showCopied(copyBtn);
                });
            } else {
                document.execCommand('copy');
                showCopied(copyBtn);
            }
        });
    }

    function showCopied(btn) {
        var origText = btn.textContent;
        btn.textContent = getLang('kbshorturl.copied', 'Copied!');
        btn.classList.add('kbsu-copied');
        setTimeout(function() {
            btn.textContent = origText;
            btn.classList.remove('kbsu-copied');
        }, 2000);
    }

    function buildPanel(shortUrl) {
        var pageTitle = document.title || '';
        var encodedUrl = encodeURIComponent(shortUrl);
        var encodedText = encodeURIComponent(pageTitle + ' ' + shortUrl);

        return '<div class="kbsu-panel">' +
            '<div class="kbsu-panel-title">' + getLang('kbshorturl.short_link', 'Short link') + '</div>' +
            '<div class="kbsu-url-row">' +
                '<input type="text" class="kbsu-url-input" value="' + escapeAttr(shortUrl) + '" readonly>' +
                '<button class="kbsu-copy-btn">' + getLang('kbshorturl.copy', 'Copy') + '</button>' +
            '</div>' +
            '<div class="kbsu-share-buttons">' +
                '<a class="kbsu-share-btn kbsu-share-whatsapp" href="https://wa.me/?text=' + encodedText + '" target="_blank" rel="noopener">' +
                    svgWhatsApp() + '<span>' + getLang('kbshorturl.share_via_whatsapp', 'WhatsApp') + '</span>' +
                '</a>' +
                '<a class="kbsu-share-btn kbsu-share-telegram" href="https://t.me/share/url?url=' + encodedUrl + '&text=' + encodeURIComponent(pageTitle) + '" target="_blank" rel="noopener">' +
                    svgTelegram() + '<span>' + getLang('kbshorturl.share_via_telegram', 'Telegram') + '</span>' +
                '</a>' +
                '<a class="kbsu-share-btn kbsu-share-email" href="mailto:?subject=' + encodeURIComponent(pageTitle) + '&body=' + encodedText + '">' +
                    svgEmail() + '<span>' + getLang('kbshorturl.share_via_email', 'Email') + '</span>' +
                '</a>' +
            '</div>' +
        '</div>';
    }

    function buildToggleButton() {
        return '<button class="kbsu-toggle-btn" title="' + getLang('kbshorturl.share', 'Share') + '">' +
            '<svg viewBox="0 0 24 24"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>' +
        '</button>';
    }

    function getLang(key, fallback) {
        if (typeof Lang !== 'undefined' && Lang.get) {
            var val = Lang.get('messages.' + key);
            if (val && val !== 'messages.' + key) {
                return val;
            }
        }
        return fallback;
    }

    function escapeAttr(s) {
        return s.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function svgWhatsApp() {
        return '<svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>';
    }

    function svgTelegram() {
        return '<svg viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0h-.056zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 01.171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>';
    }

    function svgEmail() {
        return '<svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
    }
})();
