/**
 * KB Short URL - Admin Settings JS
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var page = document.getElementById('kbshorturl-settings-page');
        if (!page) {
            return;
        }

        var testUrl = page.getAttribute('data-test-url');
        var bulkUrl = page.getAttribute('data-bulk-url');
        var generatingText = page.getAttribute('data-generating-text');

        // Test connection.
        var testBtn = document.getElementById('kbshorturl-test-btn');
        if (testBtn) {
            testBtn.addEventListener('click', function() {
                var btn = this;
                var result = document.getElementById('kbshorturl-test-result');
                btn.disabled = true;
                result.innerHTML = '<i class="glyphicon glyphicon-hourglass"></i>';

                var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch(testUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    if (data.success) {
                        result.innerHTML = '<span class="text-success"><i class="glyphicon glyphicon-ok"></i> ' + data.message + '</span>';
                    } else {
                        result.innerHTML = '<span class="text-danger"><i class="glyphicon glyphicon-remove"></i> ' + data.message + '</span>';
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    result.innerHTML = '<span class="text-danger">Error</span>';
                });
            });
        }

        // Bulk generate.
        var bulkBtn = document.getElementById('kbshorturl-bulk-btn');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function() {
                var btn = this;
                var result = document.getElementById('kbshorturl-bulk-result');
                btn.disabled = true;
                result.innerHTML = '<i class="glyphicon glyphicon-hourglass"></i> ' + generatingText;

                var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch(bulkUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    if (data.success) {
                        result.innerHTML = '<span class="text-success"><i class="glyphicon glyphicon-ok"></i> ' + data.message + '</span>';
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        result.innerHTML = '<span class="text-danger"><i class="glyphicon glyphicon-remove"></i> ' + data.message + '</span>';
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    result.innerHTML = '<span class="text-danger">Error</span>';
                });
            });
        }
    });
})();
