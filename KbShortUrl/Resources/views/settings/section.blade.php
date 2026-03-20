@extends('layouts.app')

@section('title_full', 'KB Short URL - ' . __('Settings'))

@section('body_attrs')@parent data-page="kbshorturl-settings"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    <div class="sidebar-title">{{ __('Manage') }}</div>
    <ul class="sidebar-menu">
        @include('partials/sidebar_menu_item', ['url' => route('kbshorturl.settings'), 'title' => 'KB Short URL', 'icon' => 'link'])
    </ul>
@endsection

@section('content')
<div class="section-heading">KB Short URL</div>
<div class="col-xs-12">
    <form method="POST" action="{{ route('kbshorturl.settings') }}" class="form-horizontal margin-top">
        {{ csrf_field() }}

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Shlink API URL') }}</label>
            <div class="col-sm-7">
                <input type="url" class="form-control" name="shlink_api_url"
                    value="{{ \Option::get('kbshorturl.shlink_api_url', '') }}"
                    placeholder="https://shlink.example.com" required>
                <p class="help-block">{{ __('The base URL of your Shlink instance (without /rest/v3/).') }}</p>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Shlink API Key') }}</label>
            <div class="col-sm-7">
                <input type="password" class="form-control" name="shlink_api_key"
                    value="{{ \Option::get('kbshorturl.shlink_api_key_encrypted') ? '••••••••' : '' }}"
                    placeholder="{{ __('Enter API key') }}">
                <p class="help-block">{{ __('Generate an API key via: shlink api-key:generate') }}</p>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Short URL Domain') }}</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="shlink_domain"
                    value="{{ \Option::get('kbshorturl.shlink_domain', '') }}"
                    placeholder="es.ink" required>
                <p class="help-block">{{ __('The domain configured in Shlink for short URLs.') }}</p>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Slug Prefix') }}</label>
            <div class="col-sm-7">
                <input type="text" class="form-control" name="slug_prefix"
                    value="{{ \Option::get('kbshorturl.slug_prefix', 'kb') }}"
                    placeholder="kb" pattern="[a-z0-9\-]*">
                <p class="help-block">{{ __('Prefix for short URL slugs. Example: "kb" produces es.ink/kb1, es.ink/kb2...') }}</p>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Next Number') }}</label>
            <div class="col-sm-7">
                <input type="number" class="form-control" name="next_number"
                    value="{{ \Option::get('kbshorturl.next_number', 1) }}"
                    min="1" required>
                <p class="help-block">{{ __('The next number to use for auto-generated slugs. Can only be increased.') }}</p>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Handle Translations') }}</label>
            <div class="col-sm-7">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="handle_translations" value="1"
                            {{ \Option::get('kbshorturl.handle_translations', false) ? 'checked' : '' }}>
                        {{ __('Create separate short URLs per language (e.g., kb42, kb42-en, kb42-de).') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group margin-top">
            <div class="col-sm-7 col-sm-offset-3">
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>

                <button type="button" class="btn btn-default margin-left" id="kbshorturl-test-btn">
                    <i class="glyphicon glyphicon-refresh"></i> {{ __('Test Connection') }}
                </button>
                <span id="kbshorturl-test-result" class="margin-left"></span>
            </div>
        </div>
    </form>

    <hr>

    <div class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Bulk Generate') }}</label>
            <div class="col-sm-7">
                <button type="button" class="btn btn-warning" id="kbshorturl-bulk-btn">
                    <i class="glyphicon glyphicon-flash"></i> {{ __('Generate missing short URLs') }}
                </button>
                <span id="kbshorturl-bulk-result" class="margin-left"></span>
                <p class="help-block">{{ __('Creates short URLs for all published KB articles that do not have one yet.') }}</p>
            </div>
        </div>

        @php
            $shortUrls = \Modules\KbShortUrl\Entities\KbShortUrl::orderBy('short_number')->get();
        @endphp

        @if($shortUrls->count())
        <div class="form-group">
            <label class="col-sm-3 control-label">{{ __('Existing Short URLs') }}</label>
            <div class="col-sm-9">
                <table class="table table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Short Code') }}</th>
                            <th>{{ __('Short URL') }}</th>
                            <th>{{ __('Article') }}</th>
                            <th>{{ __('Locale') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shortUrls as $su)
                        <tr>
                            <td>{{ $su->short_number }}</td>
                            <td><code>{{ $su->short_code }}</code></td>
                            <td><a href="{{ $su->short_url }}" target="_blank">{{ $su->short_url }}</a></td>
                            <td>#{{ $su->article_id }}</td>
                            <td>{{ $su->locale ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test connection.
    document.getElementById('kbshorturl-test-btn').addEventListener('click', function() {
        var btn = this;
        var result = document.getElementById('kbshorturl-test-result');
        btn.disabled = true;
        result.innerHTML = '<i class="glyphicon glyphicon-hourglass"></i>';

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('{{ route("kbshorturl.test_connection") }}', {
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

    // Bulk generate.
    document.getElementById('kbshorturl-bulk-btn').addEventListener('click', function() {
        var btn = this;
        var result = document.getElementById('kbshorturl-bulk-result');
        btn.disabled = true;
        result.innerHTML = '<i class="glyphicon glyphicon-hourglass"></i> {{ __("Generating...") }}';

        var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        fetch('{{ route("kbshorturl.bulk_generate") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            if (data.success) {
                result.innerHTML = '<span class="text-success"><i class="glyphicon glyphicon-ok"></i> ' + data.message + '</span>';
                if (data.message.indexOf('0 errors') === -1 || data.message.indexOf('0 skipped') === -1) {
                    setTimeout(function() { location.reload(); }, 2000);
                }
            } else {
                result.innerHTML = '<span class="text-danger"><i class="glyphicon glyphicon-remove"></i> ' + data.message + '</span>';
            }
        })
        .catch(function() {
            btn.disabled = false;
            result.innerHTML = '<span class="text-danger">Error</span>';
        });
    });
});
</script>
@endsection
