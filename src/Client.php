<?php

namespace HenryEjemuta\Peyflex;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;

class Client
{
    /**
     * The base URL for the Peyflex API.
     */
    private const BASE_URL = 'https://client.peyflex.com.ng/api/';

    /**
     * The API Token.
     *
     * @var string
     */
    private $token;

    /**
     * The Guzzle HTTP Client instance.
     *
     * @var GuzzleClient
     */
    private $httpClient;

    /**
     * Client constructor.
     *
     * @param string $token The API Token.
     * @param array $config Configuration options (base_url, timeout, etc.).
     */
    public function __construct(string $token, array $config = [])
    {
        $this->token = $token;

        $baseUrl = $config['base_url'] ?? self::BASE_URL;
        // Ensure base URL ends with a slash
        if (substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }

        $timeout = $config['timeout'] ?? 30;
        $retries = $config['retries'] ?? 3;

        $handlerStack = $config['handler_stack'] ?? \GuzzleHttp\HandlerStack::create();

        if (! isset($config['handler_stack'])) {
            // Only add retry middleware if we are using the default stack
            // OR we can add it regardless, but we need to be careful about duplication if the passed stack has it.
            // Let's append it regardless, assuming the test usage knows what it's doing.
        }

        $handlerStack->push(\GuzzleHttp\Middleware::retry(
            function ($retriesCount, $request, $response = null, $exception = null) use ($retries) {
                // Retry on connection exceptions
                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }

                if ($exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
                    $response = $exception->getResponse();
                }

                // Retry on server errors (5xx)
                if ($response && $response->getStatusCode() >= 500) {
                    // Check retries count before deciding to retry
                    if ($retriesCount >= $retries) {
                        return false;
                    }

                    return true;
                }

                return false;
            },
            function ($retriesCount) {
                // Exponential backoff
                return pow(2, $retriesCount - 1) * 1000;
            }
        ));

        $guzzleConfig = [
            'base_uri' => $baseUrl,
            'timeout' => $timeout,
            'handler' => $handlerStack,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$this->token,
                'Content-Type' => 'application/json',
            ],
        ];

        $this->httpClient = new GuzzleClient($guzzleConfig);
    }

    /**
     * Make a request to the API.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return array
     * @throws PeyflexException
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $uri, $options);
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new PeyflexException('Failed to decode JSON response: '.json_last_error_msg());
            }

            return $data;
        } catch (GuzzleException $e) {
            // Attempt to get error message from response body if available
            $message = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($responseBody, true);
                if (isset($errorData['message'])) {
                    $message = $errorData['message'];
                } elseif (isset($errorData['error'])) {
                    $message = $errorData['error'];
                }
            }
            throw new PeyflexException('API Request Failed: '.$message, $e->getCode(), $e);
        }
    }

    /**
     * Get authenticated user profile.
     *
     * @return array
     * @throws PeyflexException
     */
    public function getProfile(): array
    {
        return $this->request('GET', 'user/profile');
    }

    /**
     * Get wallet balance.
     *
     * @return array
     * @throws PeyflexException
     */
    public function getBalance(): array
    {
        return $this->request('GET', 'user/balance');
    }

    /**
     * Get airtime networks.
     *
     * @return array
     * @throws PeyflexException
     */
    public function getAirtimeNetworks(): array
    {
        return $this->request('GET', 'airtime/networks');
    }

    /**
     * Purchase airtime.
     *
     * @param string $network The network ID (e.g., 'mtn', 'glo').
     * @param string $phone The phone number.
     * @param float $amount The amount to top up.
     * @return array
     * @throws PeyflexException
     */
    public function purchaseAirtime(string $network, string $phone, float $amount): array
    {
        return $this->request('POST', 'airtime/purchase', [
            'json' => [
                'network' => $network,
                'phone' => $phone,
                'amount' => $amount,
            ],
        ]);
    }

    /**
     * Get data networks.
     *
     * @return array
     * @throws PeyflexException
     */
    public function getDataNetworks(): array
    {
        return $this->request('GET', 'data/networks');
    }

    /**
     * Get data plans for a network.
     *
     * @param string $networkId The network identifier (e.g., 'mtn_sme_data').
     * @return array
     * @throws PeyflexException
     */
    public function getDataPlans(string $networkId): array
    {
        return $this->request('GET', 'data/plans', [
            'query' => ['network' => $networkId],
        ]);
    }

    /**
     * Purchase data.
     *
     * @param string $networkId The network identifier.
     * @param string $phone The phone number.
     * @param string $planId The plan identifier (from getDataPlans).
     * @return array
     * @throws PeyflexException
     */
    public function purchaseData(string $networkId, string $phone, string $planId): array
    {
        return $this->request('POST', 'data/purchase', [
            'json' => [
                'network' => $networkId,
                'phone' => $phone,
                'plan' => $planId,
            ],
        ]);
    }

    /**
     * Get cable providers.
     *
     * @return array
     * @throws PeyflexException
     */
    public function getCableProviders(): array
    {
        return $this->request('GET', 'cable/providers');
    }

    /**
     * Verify cable IUC number.
     *
     * @param string $providerId The provider identifier (e.g., 'dstv').
     * @param string $iucNumber The IUC/Smartcard number.
     * @return array
     * @throws PeyflexException
     */
    public function verifyCable(string $providerId, string $iucNumber): array
    {
        return $this->request('POST', 'cable/verify', [
            'json' => [
                'provider' => $providerId,
                'iuc_number' => $iucNumber,
            ],
        ]);
    }

    /**
     * Purchase cable subscription.
     *
     * @param string $providerId The provider identifier.
     * @param string $iucNumber The IUC number.
     * @param string $planId The plan identifier.
     * @return array
     * @throws PeyflexException
     */
    public function purchaseCable(string $providerId, string $iucNumber, string $planId): array
    {
        return $this->request('POST', 'cable/purchase', [
            'json' => [
                'provider' => $providerId,
                'iuc_number' => $iucNumber,
                'plan' => $planId,
            ],
        ]);
    }

    /**
     * Get electricity plans/providers.
     *
     * @return array
     * @throws PeyflexException
     */
    public function getElectricityPlans(): array
    {
        return $this->request('GET', 'electricity/plans', [
            'query' => ['identifier' => 'electricity'],
        ]);
    }

    /**
     * Verify electricity meter.
     *
     * @param string $providerId The provider ID (e.g., 'ikeja_electric').
     * @param string $meterNumber The meter number.
     * @param string $type The meter type ('prepaid' or 'postpaid').
     * @return array
     * @throws PeyflexException
     */
    public function verifyMeter(string $providerId, string $meterNumber, string $type = 'prepaid'): array
    {
        return $this->request('POST', 'electricity/verify', [
            'json' => [
                'identifier' => 'electricity',
                'provider' => $providerId,
                'meter_number' => $meterNumber,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Purchase electricity token.
     *
     * @param string $providerId The provider ID.
     * @param string $meterNumber The meter number.
     * @param float $amount The amount to purchase.
     * @param string $type The meter type ('prepaid' or 'postpaid').
     * @return array
     * @throws PeyflexException
     */
    public function purchaseElectricity(string $providerId, string $meterNumber, float $amount, string $type = 'prepaid'): array
    {
        return $this->request('POST', 'electricity/purchase', [
            'json' => [
                'provider' => $providerId,
                'meter_number' => $meterNumber,
                'amount' => $amount,
                'type' => $type,
            ],
        ]);
    }
}
