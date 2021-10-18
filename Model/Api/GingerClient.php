<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Api;

use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * GingerClient API class
 */
class GingerClient
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var UrlProvider
     */
    private $urlProvider;

    /**
     * @var \Ginger\ApiClient
     */
    private $client = null;

    /**
     * @var string
     */
    private $apiKey = null;

    /**
     * @var string
     */
    private $endpoint = null;

    /**
     * GingerClient constructor.
     *
     * @param ConfigRepository $configRepository
     * @param UrlProvider $urlProvider
     */
    public function __construct(
        ConfigRepository $configRepository,
        UrlProvider $urlProvider
    ) {
        $this->configRepository = $configRepository;
        $this->urlProvider = $urlProvider;
    }

    /**
     * @param int $storeId
     * @param string $testApiKey
     *
     * @return bool|\Ginger\ApiClient
     * @throws \Exception
     */
    public function get(int $storeId = null, string $testApiKey = null)
    {
        if ($this->client !== null && $testApiKey === null) {
            return $this->client;
        }

        if (empty($storeId)) {
            $storeId = $this->configRepository->getCurrentStoreId();
        }

        if ($testApiKey !== null) {
            $this->apiKey = $testApiKey;
        }

        if ($this->apiKey === null) {
            $this->apiKey = $this->configRepository->getApiKey((int)$storeId);
        }

        if ($this->endpoint === null) {
            $this->endpoint = $this->urlProvider->getEndPoint();
        }

        if (!$this->apiKey || !$this->endpoint) {
            $this->configRepository->addTolog('error', 'Missing Api Key / Api Endpoint');
            return false;
        }

        $gingerClient = new \Ginger\Ginger;
        $this->client = $gingerClient->createClient($this->endpoint, $this->apiKey);
        return $this->client;
    }
}
