<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Config;

use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use EMSPay\Payment\Logger\DebugLogger;
use EMSPay\Payment\Logger\ErrorLogger;
use EMSPay\Payment\Model\Methods\Afterpay;
use EMSPay\Payment\Model\Methods\Klarna;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

/**
 * Config repository class
 */
class Repository implements ConfigRepositoryInterface
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @var PricingHelper
     */
    private $pricingHelper;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ErrorLogger
     */
    private $errorLogger;

    /**
     * @var DebugLogger
     */
    private $debugLogger;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param RemoteAddress $remoteAddress
     * @param StoreManager $storeManager
     * @param PricingHelper $pricingHelper
     * @param AssetRepository $assetRepository
     * @param ModuleListInterface $moduleList
     * @param ErrorLogger $errorLogger
     * @param DebugLogger $debugLogger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RemoteAddress $remoteAddress,
        StoreManager $storeManager,
        PricingHelper $pricingHelper,
        AssetRepository $assetRepository,
        ModuleListInterface $moduleList,
        ErrorLogger $errorLogger,
        DebugLogger $debugLogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->remoteAddress = $remoteAddress;
        $this->storeManager = $storeManager;
        $this->pricingHelper = $pricingHelper;
        $this->assetRepository = $assetRepository;
        $this->moduleList = $moduleList;
        $this->errorLogger = $errorLogger;
        $this->debugLogger = $debugLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function isAvailable(int $storeId): bool
    {
        $active = $this->getStoreConfig(self::XML_PATH_MODULE_ACTIVE);
        if (!$active) {
            return false;
        }

        $apiKey = $this->getApiKey($storeId);
        if (!$apiKey) {
            return false;
        }

        return true;
    }

    /**
     * Get config value
     *
     * @param string $path
     * @param int $storeId
     *
     * @return string|array
     */
    private function getStoreConfig(string $path, int $storeId = 0)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getApiKey(int $storeId): string
    {
        return $this->getStoreConfig(self::XML_PATH_APIKEY, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function useMethodCheck(): bool
    {
        return (bool)$this->getFlag(self::XML_PATH_OBSERVER);
    }

    /**
     * Get config flag
     *
     * @param string $path
     * @param int $storeId
     *
     * @return bool
     */
    private function getFlag(string $path, int $storeId = 0): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, (int)$storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodCodeFromOrder(OrderInterface $order): string
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        return str_replace('emspay_methods_', '', $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusProcessing(string $method, int $storeId = 0): string
    {
        $path = 'payment/' . $method . '/order_status_processing';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusPending(string $method, int $storeId = 0): string
    {
        $path = 'payment/' . $method . '/order_status_pending';
        return $this->getStoreConfig($path, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function sendInvoice(string $method, int $storeId = 0): bool
    {
        $path = 'payment/' . $method . '/invoice_notify';
        return (bool)$this->getFlag($path, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(OrderInterface $order, string $method): string
    {
        $storeId = (int)$order->getStoreId();

        $description = __($this->getStoreConfig($path = 'payment/' . $method . '/description', $storeId));
        $description = str_replace('%id%', $order->getIncrementId(), $description);

        $storeName = $this->getStoreConfig(self::XML_PATH_STORE_NAME, $storeId);
        $description = str_replace('%name%', $storeName, $description);

        return $description;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccountDetails(): array
    {
        return $this->getStoreConfig(self::XML_PATH_ACCOUNT_DETAILS);
    }

    /**
     * {@inheritDoc}
     */
    public function getCompanyName(int $storeId): string
    {
        return (string)$this->getStoreConfig(self::XML_PATH_COMPANY_NAME, $storeId);
    }

    /**
     * {@inheritDoc}
     */
    public function isKlarnaAllowed(int $storeId = 0): bool
    {
        $testModus = $this->getStoreConfig(self::XML_PATH_KLARNA_TEST_MODUS, $storeId);
        if (!$testModus) {
            return true;
        }

        $ipFilterList = (string)$this->getStoreConfig(self::XML_PATH_KLARNA_IP_FILTER, $storeId);
        if (strlen($ipFilterList) > 0) {
            $ipWhitelist = array_map('trim', explode(",", $ipFilterList));
            $remoteAddress = $this->remoteAddress->getRemoteAddress();
            if (!in_array($remoteAddress, $ipWhitelist)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isAfterpayAllowed(int $storeId = 0): bool
    {
        $testModus = $this->getStoreConfig(self::XML_PATH_AFTERPAY_TEST_MODUS, $storeId);
        if (!$testModus) {
            return true;
        }

        $ipFilterList = $this->getStoreConfig(self::XML_PATH_AFTERPAY_IP_FILTER, $storeId);
        if (strlen($ipFilterList) > 0) {
            $ipWhitelist = array_map('trim', explode(",", $ipFilterList));
            $remoteAddress = $this->remoteAddress->getRemoteAddress();
            if (!in_array($remoteAddress, $ipWhitelist)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getTestKey(string $method, int $storeId, string $testFlag = ''): string
    {
        if ($method == Klarna::METHOD_CODE && $testFlag == 'klarna') {
            return $this->getKlarnaTestApiKey($storeId, true);
        } elseif ($method == Afterpay::METHOD_CODE && $testFlag == 'afterpay') {
            return $this->getAfterpayTestApiKey($storeId, true);
        } else {
            return $this->getApiKey($storeId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getKlarnaTestApiKey(int $storeId, bool $force = false)
    {
        $testModus = $this->getStoreConfig(self::XML_PATH_KLARNA_TEST_MODUS, $storeId);
        $testApiKey = $this->getStoreConfig(self::XML_PATH_KLARNA_TEST_API_KEY, $storeId);

        if ((!$testModus && !$force) || empty($testApiKey)) {
            return null;
        }

        return $testApiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getAfterpayTestApiKey(int $storeId, bool $force = false)
    {
        $testModus = $this->getStoreConfig(self::XML_PATH_AFTERPAY_TEST_MODUS, $storeId);
        $testApiKey = $this->getStoreConfig(self::XML_PATH_AFTERPAY_TEST_API_KEY, $storeId);

        if ((!$testModus && !$force) || empty($testApiKey)) {
            return null;
        }

        return $testApiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function addTolog(string $type, $data)
    {
        if ($this->isDebugEnabled()) {
            if ($type == 'error') {
                $this->errorLogger->addLog($type, $data);
            } elseif ($this->isDebugEnabled()) {
                $this->debugLogger->addLog($type, $data);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isDebugEnabled(): bool
    {
        return (bool)$this->getFlag(self::XML_PATH_DEBUG);
    }

    /**
     * {@inheritDoc}
     */
    public function getPluginVersion(): string
    {
        return 'Magento2-' . $this->getExtensionVersion();
    }

    /**
     * {@inheritDoc}
     */
    public function getExtensionVersion(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_VERSION);
    }

    /**
     * {@inheritDoc}
     */
    public function getError(array $transaction)
    {
        if ($transaction['status'] == 'error' && !empty($transaction['transactions'][0]['reason'])) {
            return $transaction['transactions'][0]['reason'];
        } elseif ($transaction['status'] == 'cancelled') {
            $method = $transaction['transactions'][0]['payment_method'];
            if ($method == $this->getShortMethodCode(Afterpay::METHOD_CODE)) {
                return (string)__('Unfortunately, we can not currently accept
                your purchase with Afterpay. Please choose another payment
                option to complete your order. We apologize for the inconvenience.');
            }
            if ($method == $this->getShortMethodCode(Klarna::METHOD_CODE)) {
                return (string)__('Unfortunately, we can not currently
                accept your purchase with Klarna. Please choose another payment
                option to complete your order. We apologize for the inconvenience.');
            }
        }

        return false;
    }

    /**
     * Returns method code without prefix
     *
     * @param string $method
     * @return string
     */
    private function getShortMethodCode($method): string
    {
        return str_replace(self::METHOD_PREFIX, '', $method);
    }

    /**
     * {@inheritDoc}
     */
    public function getAmountInCents(float $amount): int
    {
        return (int)round($amount * 100);
    }

    /**
     * {@inheritDoc}
     */
    public function formatPrice(float $price)
    {
        return $this->pricingHelper->currency((float)$price, true, false);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentStoreId(): int
    {
        return (int)$this->getStore()->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function getStore(): StoreInterface
    {
        try {
            return $this->storeManager->getStore();
        } catch (\Exception $e) {
            if ($store = $this->storeManager->getDefaultStoreView()) {
                return $store;
            }
        }

        $stores = $this->storeManager->getStores();
        return reset($stores);
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseUrl(string $type): string
    {
        return (string)$this->getStore()->getBaseUrl($type);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentLogo(string $code)
    {
        if (!$this->displayPaymentImages()) {
            return false;
        }

        $logo = sprintf('%s::images/%s.png', self::MODULE_CODE, $this->getShortMethodCode($code));
        return $this->assetRepository->getUrl($logo);
    }

    /**
     * {@inheritDoc}
     */
    public function displayPaymentImages(): bool
    {
        return (bool)$this->getFlag(self::XML_PATH_IMAGES);
    }
}
