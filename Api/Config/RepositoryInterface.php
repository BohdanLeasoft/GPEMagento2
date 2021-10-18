<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Api\Config;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Config repository interface
 */
interface RepositoryInterface
{

    const XML_PATH_MODULE_ACTIVE = 'payment/emspay_general/enabled';
    const XML_PATH_APIKEY = 'payment/emspay_general/apikey';
    const XML_PATH_VERSION = 'payment/emspay_general/version';
    const XML_PATH_DEBUG = 'payment/emspay_general/debug';
    const XML_PATH_OBSERVER = 'payment/emspay_general/observer';
    const XML_PATH_ACCOUNT_DETAILS = 'payment/emspay_methods_banktransfer/account_details';
    const XML_PATH_KLARNA_TEST_MODUS = 'payment/emspay_methods_klarna/test_modus';
    const XML_PATH_KLARNA_TEST_API_KEY = 'payment/emspay_methods_klarna/test_apikey';
    const XML_PATH_KLARNA_IP_FILTER = 'payment/emspay_methods_klarna/ip_filter';
    const XML_PATH_AFTERPAY_TEST_MODUS = 'payment/emspay_methods_afterpay/test_modus';
    const XML_PATH_AFTERPAY_TEST_API_KEY = 'payment/emspay_methods_afterpay/test_apikey';
    const XML_PATH_AFTERPAY_IP_FILTER = 'payment/emspay_methods_afterpay/ip_filter';
    const XML_PATH_STORE_NAME = 'general/store_information/name';
    const XML_PATH_IMAGES = 'payment/emspay_general/payment_images';
    const XML_PATH_COMPANY_NAME = 'general/store_information/name';
    const MODULE_CODE = 'EMSPay_Payment';
    const METHOD_PREFIX = 'emspay_methods_';

    /**
     * Availability check, on Active, API Client & API Key
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isAvailable(int $storeId): bool;

    /**
     * Returns API key
     *
     * @param int $storeId
     *
     * @return string|null
     */
    public function getApiKey(int $storeId);

    /**
     * @return bool
     */
    public function useMethodCheck(): bool;

    /**
     * @param OrderInterface $order
     *
     * @return string
     */
    public function getMethodCodeFromOrder(OrderInterface $order): string;

    /**
     * @param string $method
     * @param int $storeId
     *
     * @return string
     */
    public function getStatusProcessing(string $method, int $storeId = 0): string;

    /**
     * @param string $method
     * @param int $storeId
     *
     * @return string
     */
    public function getStatusPending(string $method, int $storeId = 0): string;

    /**
     * @param string $method
     * @param int $storeId
     *
     * @return bool
     */
    public function sendInvoice(string $method, int $storeId = 0): bool;

    /**
     * Process order transaction description
     *
     * @param OrderInterface $order
     * @param string $method
     *
     * @return string
     */
    public function getDescription(OrderInterface $order, string $method): string;

    /**
     * Return account details for Banktransfer method
     *
     * @return array
     */
    public function getAccountDetails(): array;

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getCompanyName(int $storeId): string;

    /**
     * @param int $storeId
     *
     * @return bool
     */
    public function isKlarnaAllowed(int $storeId = 0): bool;

    /**
     * @param int $storeId
     *
     * @return bool
     */
    public function isAfterpayAllowed(int $storeId = 0): bool;

    /**
     * @param string $method
     * @param int $storeId
     * @param string $testFlag
     *
     * @return string
     */
    public function getTestKey(string $method, int $storeId, string $testFlag = ''): string;

    /**
     * @param int $storeId
     * @param bool $force
     *
     * @return string|null
     */
    public function getKlarnaTestApiKey(int $storeId, bool $force = false);

    /**
     * @param int $storeId
     * @param bool $force
     *
     * @return string|null
     */
    public function getAfterpayTestApiKey(int $storeId, bool $force = false);

    /**
     * @return bool
     */
    public function isDebugEnabled(): bool;

    /**
     * @return bool
     */
    public function displayPaymentImages(): bool;

    /**
     * Write to log
     *
     * @param string $type
     * @param mixed $data
     *
     * @return void
     */
    public function addTolog(string $type, $data);

    /**
     * Get extension version for API
     *
     * @return string
     */
    public function getPluginVersion(): string;

    /**
     * Returns current version of the extension for admin display
     *
     * @return string
     */
    public function getExtensionVersion(): string;

    /**
     * Find error in transaction
     *
     * @param array $transaction
     *
     * @return bool|string
     */
    public function getError(array $transaction);

    /**
     * @param float $amount
     *
     * @return int
     */
    public function getAmountInCents(float $amount): int;

    /**
     * @param float $price
     *
     * @return float|string
     */
    public function formatPrice(float $price);

    /**
     * @return int
     */
    public function getCurrentStoreId(): int;

    /**
     * @param string $type
     *
     * @return string
     */
    public function getBaseUrl(string $type): string;

    /**
     * @param string $code
     *
     * @return bool|string
     */
    public function getPaymentLogo(string $code);

    /**
     * Get current store
     *
     * @return StoreInterface
     */
    public function getStore(): StoreInterface;
}
