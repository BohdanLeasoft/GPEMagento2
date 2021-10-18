<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Methods;

use EMSPay\Payment\Model\Ems;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Afterpay method class
 */
class Afterpay extends Ems
{

    /** Afterpay terms */
    const TERMS_NL_URL = 'https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden';

    /** Afterpay terms */
    const TERMS_BE_URL = 'https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden';

    /** Payment Code */
    const METHOD_CODE = 'emspay_methods_afterpay';

    /** Platform Method Code */
    const PLATFORM_CODE = 'afterpay';

    /**
     * @var string
     */
    protected $_infoBlockType = \EMSPay\Payment\Block\Info\Afterpay::class;

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isAvailable(CartInterface $quote = null): bool
    {
        if ($quote == null) {
            $quote = $this->checkoutSession->getQuote();
        }

        if (!$this->configRepository->isAfterpayAllowed((int)$quote->getStoreId())) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param OrderInterface $order
     *
     * @return array
     * @throws \Exception
     * @throws LocalizedException
     */
    public function startTransaction(OrderInterface $order): array
    {
        return parent::prepareTransaction(
            $order,
            self::PLATFORM_CODE,
            self::METHOD_CODE
        );
    }

    /**
     * Assign date of birth, customer prefixm and issuer data to checkout fields
     *
     * @param DataObject $data
     *
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getAdditionalData();
        if (isset($additionalData['issuer'])) {
            $this->getInfoInstance()->setAdditionalInformation('issuer', $additionalData['issuer']);
        }
        if (isset($additionalData['prefix'])) {
            $this->getInfoInstance()->setAdditionalInformation('prefix', $additionalData['prefix']);
        }
        if (isset($additionalData['dob'])) {
            $this->getInfoInstance()->setAdditionalInformation('dob', $additionalData['dob']);
        }
        return $this;
    }

    /**
     * @param OrderInterface $order
     *
     * @return $this
     * @throws \Exception
     */
    public function captureOrder($order)
    {
        $storeId = (int)$order->getStoreId();
        $testModus = $order->getPayment()->getAdditionalInformation();
        if (array_key_exists('test_modus', $testModus)) {
            $testModus = $testModus['test_modus'];
        }
        $testApiKey = $testModus ? $this->configRepository->getAfterpayTestApiKey($storeId, true) : null;
        $client = $this->loadGingerClient($storeId, $testApiKey);

        try {
            $ingOrder = $client->getOrder($order->getEmspayTransactionId());
            $orderId = $ingOrder['id'];
            $transactionId = $ingOrder['transactions'][0]['id'];
            $client->captureOrderTransaction($orderId, $transactionId);
            $this->configRepository->addTolog(
                'success',
                'Klarna payment captured for order: ' . $order->getIncrementId()
            );
        } catch (\Exception $e) {
            $this->configRepository->addTolog('error', 'Function: captureOrder: ' . $e->getMessage());
        }

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return $this
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $payment->getCreditmemo();

        /** @var Order $order */
        $order = $payment->getOrder();

        if ($creditmemo->getAdjustmentPositive() != 0 || $creditmemo->getAdjustmentNegative() != 0) {
            throw new LocalizedException(__('Api does not accept adjustment fees for refunds using order lines'));
        }

        if ($creditmemo->getShippingAmount() > 0
            && ($creditmemo->getShippingAmount() != $creditmemo->getBaseShippingInclTax())) {
            throw new LocalizedException(__('Api does not accept adjustment fees for shipments using order lines'));
        }

        $storeId = (int)$order->getStoreId();
        $testModus = $order->getPayment()->getAdditionalInformation();
        if (array_key_exists('test_modus', $testModus)) {
            $testModus = $testModus['test_modus'];
        }
        $testApiKey = $testModus ? $this->configRepository->getAfterpayTestApiKey($storeId, true) : null;
        $transactionId = $order->getEmspayTransactionId();

        try {
            $addShipping = $creditmemo->getShippingAmount() > 0 ? 1 : 0;
            $client = $this->loadGingerClient($storeId, $testApiKey);
            $client->refundOrder(
                $transactionId,
                [
                    'order_lines' => $this->orderLines->getRefundLines($creditmemo, $addShipping)
                ]
            );
        } catch (\Exception $e) {
            $this->configRepository->addTolog('error', $e->getMessage());
            throw new LocalizedException(__('Error: not possible to create an online refund: %1', $e->getMessage()));
        }

        return $this;
    }
}
