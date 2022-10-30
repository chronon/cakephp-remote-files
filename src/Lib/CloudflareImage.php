<?php
declare(strict_types=1);

namespace RemoteFiles\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class: CloudflareImage
 *
 * @package RemoteFiles\Lib
 */
class CloudflareImage
{
    /**
     * token
     *
     * @var string
     */
    protected $token;

    /**
     * account
     *
     * @var string
     */
    protected $account;

    /**
     * apiUrl
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * client
     *
     * @var \GuzzleHttp\Client
     */
    public $client;

    /**
     * Constructor
     *
     * @param array $config Optional config options
     */
    public function __construct(array $config = [])
    {
        $this->token = !empty($config['token']) ? $config['token'] : Configure::read(
            'RemoteFiles.Cloudflare.Images.Auth.token'
        );
        $this->account = !empty($config['account']) ? $config['account'] : Configure::read(
            'RemoteFiles.Cloudflare.Images.Auth.account'
        );
        $this->apiUrl = !empty($config['apiUrl']) ? $config['apiUrl'] : Configure::read(
            'RemoteFiles.Cloudflare.Images.apiUrl'
        );

        if (!$this->token || !$this->account || !$this->apiUrl) {
            throw new \InvalidArgumentException('Cloudflare Images token, account and apiUrl are required');
        }

        $this->apiUrl = sprintf($this->apiUrl, $this->account);
        $this->client = $this->clientFactory();
    }

    /**
     * Guzzle client factory method
     *
     * @return \GuzzleHttp\Client
     */
    public function clientFactory(): Client
    {
        $client = new Client();

        return $client;
    }

    /**
     * Upload an image to Cloudflare by URL
     *
     * @param string $url The URL to fetch
     * @param string $id The ID to use for the image
     * @return bool True on success, false on failure
     */
    public function uploadUrl(string $url, string $id): bool
    {
        $response = null;
        try {
            $response = $this->client->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'multipart' => [
                    [
                        'name' => 'url',
                        'contents' => $url,
                    ],
                    [
                        'name' => 'id',
                        'contents' => $id,
                    ],
                ],
            ]);
        } catch (RequestException $e) {
            if ($e->getCode() === 409) {
                $response = 'exists';
            } else {
                Log::error(__METHOD__ . ": Error Id: {$id}: " . $e->getMessage());
            }
        }

        return $this->parseResponse($response, $id, __METHOD__);
    }

    /**
     * Parse and process the response
     *
     * @param mixed $response \Psr\Http\Message\ResponseInterface or null
     * @param string $id The ID to use for the image
     * @param string $method The method that was performed
     * @return bool True on success, false on failure
     */
    public function parseResponse($response, string $id, string $method): bool
    {
        $result = false;
        if ($response && is_object($response) && $response->getStatusCode() === 200) {
            $body = json_decode($response->getBody()->getContents(), true);
            if ($body && $body['success']) {
                $result = true;
            }
            if (!$body || ($body && !$body['success'])) {
                Log::error(__METHOD__ . ": Error Id: {$id} Method: {$method}");
            }
        } elseif ($response === 'exists') {
            $result = true;
        }

        return $result;
    }

    /**
     * Delete an image on Cloudflare by ID
     *
     * @param string $id The image ID to delete
     * @return bool True on success, false on failure
     */
    public function delete(string $id): bool
    {
        $response = null;
        try {
            $response = $this->client->request('DELETE', $this->apiUrl . "/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
            ]);
        } catch (RequestException $e) {
            Log::error(__METHOD__ . ": Error Deleting Id: {$id}: " . $e->getMessage());
        }

        return $this->parseResponse($response, $id, __METHOD__);
    }

    /**
     * Get an image on Cloudflare by ID
     *
     * @param string $id The image ID to get
     * @return mixed The image data or null
     */
    public function get(string $id)
    {
        $result = null;
        try {
            $response = $this->client->request('GET', $this->apiUrl . "/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
            ]);

            if ($response && is_object($response) && $response->getStatusCode() === 200) {
                $result = $response->getBody()->getContents();
            }
        } catch (RequestException $e) {
            Log::error(__METHOD__ . ": Error Getting Id: {$id}: " . $e->getMessage());
        }

        return $result;
    }
}
