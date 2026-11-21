<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class CloudflareImage
{
    protected readonly string $token;

    protected readonly string $apiUrl;

    public readonly Client $client;

    /**
     * @param array{token?: string, account?: string, apiUrl?: string} $config Overrides for the configured values
     * @param \GuzzleHttp\Client|null $client HTTP client, defaults to `clientFactory()`
     * @throws \InvalidArgumentException When token, account or apiUrl is missing
     */
    public function __construct(array $config = [], ?Client $client = null)
    {
        $token = $config['token'] ?? null ?: Configure::read('RemoteFiles.Cloudflare.Images.Auth.token');
        $account = $config['account'] ?? null ?: Configure::read('RemoteFiles.Cloudflare.Images.Auth.account');
        $apiUrl = $config['apiUrl'] ?? null ?: Configure::read('RemoteFiles.Cloudflare.Images.apiUrl');

        if (!is_string($token) || !is_string($account) || !is_string($apiUrl) || !$token || !$account || !$apiUrl) {
            throw new InvalidArgumentException('Cloudflare Images token, account and apiUrl are required');
        }

        $this->token = $token;
        $this->apiUrl = sprintf($apiUrl, $account);
        $this->client = $client ?? $this->clientFactory();
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function clientFactory(): Client
    {
        return new Client();
    }

    /**
     * Upload an image to Cloudflare by URL. An image that already exists counts as success.
     *
     * @param string $url The URL to fetch
     * @param string $id The ID to use for the image
     * @return bool
     */
    public function uploadUrl(string $url, string $id): bool
    {
        try {
            $response = $this->request('POST', $this->apiUrl, [
                'multipart' => [
                    ['name' => 'url', 'contents' => $url],
                    ['name' => 'id', 'contents' => $id],
                ],
            ]);
        } catch (BadResponseException $e) {
            if ($e->getResponse()->getStatusCode() === 409) {
                return true;
            }
            Log::error(__METHOD__ . ": Error Id: {$id}: " . $e->getMessage());

            return false;
        } catch (GuzzleException $e) {
            Log::error(__METHOD__ . ": Error Id: {$id}: " . $e->getMessage());

            return false;
        }

        return $this->parseResponse($response, $id, __METHOD__);
    }

    /**
     * Delete an image on Cloudflare by ID.
     *
     * @param string $id The image ID to delete
     * @return bool
     */
    public function delete(string $id): bool
    {
        try {
            $response = $this->request('DELETE', "{$this->apiUrl}/{$id}");
        } catch (GuzzleException $e) {
            Log::error(__METHOD__ . ": Error Deleting Id: {$id}: " . $e->getMessage());

            return false;
        }

        return $this->parseResponse($response, $id, __METHOD__);
    }

    /**
     * Get an image's details from Cloudflare by ID.
     *
     * @param string $id The image ID to get
     * @return string|null The JSON response body, or null on failure
     */
    public function get(string $id): ?string
    {
        try {
            $response = $this->request('GET', "{$this->apiUrl}/{$id}");
        } catch (GuzzleException $e) {
            Log::error(__METHOD__ . ": Error Getting Id: {$id}: " . $e->getMessage());

            return null;
        }

        return $response->getStatusCode() === 200 ? (string)$response->getBody() : null;
    }

    /**
     * Whether a Cloudflare API response reports success.
     *
     * @param \Psr\Http\Message\ResponseInterface $response The API response
     * @param string $id The image ID, for logging
     * @param string $method The calling method, for logging
     * @return bool
     */
    public function parseResponse(ResponseInterface $response, string $id, string $method): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $body = json_decode((string)$response->getBody(), true);
        if (is_array($body) && ($body['success'] ?? false) === true) {
            return true;
        }
        Log::error(__METHOD__ . ": Error Id: {$id} Method: {$method}");

        return false;
    }

    /**
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array<string, mixed> $options Guzzle request options
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $options['headers']['Authorization'] = 'Bearer ' . $this->token;

        return $this->client->request($method, $uri, $options);
    }
}
