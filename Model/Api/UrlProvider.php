<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Api;

use Magento\Framework\UrlInterface;

/**
 * UrlProvider API class
 */
class UrlProvider
{

    /**
     * EMS Endpoint
     */
    const ENDPOINT_EMS = 'https://api.online.emspay.eu/';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * UrlProvider constructor.
     *
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Return Url Builder
     *
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->urlBuilder->getUrl('emspay/checkout/process');
    }

    /**
     * Webhook Url Builder
     *
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->urlBuilder->getUrl('emspay/checkout/webhook/');
    }

    /**
     * Process Url Builder
     *
     * @param string $transactionId
     *
     * @return string
     */
    public function getSuccessProcessUrl(string $transactionId) : string
    {
        return $this->urlBuilder->getUrl('emspay/checkout/process', ['order_id' => $transactionId]);
    }

    /**
     * Checkout Webhook Url Builder
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->urlBuilder->getUrl('checkout/onepage/success?utm_nooverride=1');
    }

    /**
     * @return string
     */
    public function getEndPoint()
    {
        return self::ENDPOINT_EMS;
    }
}
