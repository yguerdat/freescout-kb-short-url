<?php

namespace Modules\KbShortUrl\Services;

class ShlinkApiService
{
    private $apiUrl;
    private $apiKey;
    private $domain;

    public function __construct()
    {
        $this->apiUrl = rtrim(\Option::get('kbshorturl.shlink_api_url', ''), '/');
        $this->apiKey = self::decryptApiKey(\Option::get('kbshorturl.shlink_api_key_encrypted', ''));
        $this->domain = \Option::get('kbshorturl.shlink_domain', '');
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured()
    {
        return !empty($this->apiUrl) && !empty($this->apiKey) && !empty($this->domain);
    }

    /**
     * Test the connection to Shlink.
     */
    public function testConnection()
    {
        $response = $this->request('GET', '/rest/v3/short-urls?itemsPerPage=1');

        if ($response['status'] === 200) {
            return ['success' => true, 'message' => __('Connection successful.')];
        }

        return ['success' => false, 'message' => $response['error'] ?? __('Connection failed.')];
    }

    /**
     * Create a short URL in Shlink.
     *
     * @param string $longUrl   The destination URL.
     * @param string $customSlug The custom slug (e.g., "kb42" or "kb42-en").
     * @param string $title     Optional title for the short URL.
     * @return array             ['success' => bool, 'short_url' => string, 'error' => string]
     */
    public function createShortUrl($longUrl, $customSlug, $title = '')
    {
        $payload = [
            'longUrl'    => $longUrl,
            'customSlug' => $customSlug,
            'domain'     => $this->domain,
            'title'      => $title,
            'crawlable'  => true,
            'forwardQuery' => true,
        ];

        $response = $this->request('POST', '/rest/v3/short-urls', $payload);

        if (in_array($response['status'], [200, 201])) {
            return [
                'success'   => true,
                'short_url' => $response['data']['shortUrl'] ?? '',
                'short_code' => $response['data']['shortCode'] ?? $customSlug,
            ];
        }

        // Slug already in use.
        if ($response['status'] === 409) {
            return [
                'success' => false,
                'error'   => 'slug_taken',
                'message' => __('Short URL slug ":slug" is already in use.', ['slug' => $customSlug]),
            ];
        }

        return [
            'success' => false,
            'error'   => 'api_error',
            'message' => $response['error'] ?? __('Failed to create short URL.'),
        ];
    }

    /**
     * Update the long URL of an existing short URL.
     */
    public function updateShortUrl($shortCode, $longUrl, $title = '')
    {
        $payload = ['longUrl' => $longUrl];
        if ($title) {
            $payload['title'] = $title;
        }

        $endpoint = '/rest/v3/short-urls/' . urlencode($shortCode) . '?domain=' . urlencode($this->domain);
        $response = $this->request('PATCH', $endpoint, $payload);

        return in_array($response['status'], [200, 204]);
    }

    /**
     * Delete a short URL from Shlink.
     */
    public function deleteShortUrl($shortCode)
    {
        $endpoint = '/rest/v3/short-urls/' . urlencode($shortCode) . '?domain=' . urlencode($this->domain);
        $response = $this->request('DELETE', $endpoint);

        return in_array($response['status'], [200, 204]);
    }

    /**
     * Retrieve a short URL by its code.
     */
    public function getShortUrl($shortCode)
    {
        $endpoint = '/rest/v3/short-urls/' . urlencode($shortCode) . '?domain=' . urlencode($this->domain);
        $response = $this->request('GET', $endpoint);

        if ($response['status'] === 200) {
            return $response['data'];
        }

        return null;
    }

    /**
     * Encrypt the API key for storage.
     */
    public static function encryptApiKey($plainKey)
    {
        if (empty($plainKey)) {
            return '';
        }
        return encrypt($plainKey);
    }

    /**
     * Decrypt the stored API key.
     */
    public static function decryptApiKey($encryptedKey)
    {
        if (empty($encryptedKey)) {
            return '';
        }
        try {
            return decrypt($encryptedKey);
        } catch (\Exception $e) {
            \Log::error('KbShortUrl: Failed to decrypt API key: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Execute an HTTP request to the Shlink API.
     */
    private function request($method, $endpoint, $payload = null)
    {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            \Log::error('KbShortUrl: cURL error: ' . $curlError);
            return ['status' => 0, 'error' => $curlError, 'data' => null];
        }

        $data = json_decode($responseBody, true);

        return [
            'status' => $httpCode,
            'data'   => $data,
            'error'  => $data['detail'] ?? $data['title'] ?? null,
        ];
    }
}
