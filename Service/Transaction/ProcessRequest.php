<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Transaction;

use EMSPay\Payment\Model\Methods\Afterpay;
use EMSPay\Payment\Model\Methods\Banktransfer;
use EMSPay\Payment\Model\Methods\Klarna;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * ProcessRequest transaction class
 */
class ProcessRequest extends AbstractTransaction
{

    /**
     * @param OrderInterface $order
     * @param null|array $transaction
     * @param null|string $testModus
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(OrderInterface $order, $transaction = null, $testModus = null): array
    {
        $method = $order->getPayment()->getMethod();
        $this->updateMailingAddress($order, $method, $transaction);
        $this->configRepository->addTolog('transaction', $transaction);
        $transactionId = !empty($transaction['id']) ? $transaction['id'] : null;

        if ($transactionId && !$this->configRepository->getError($transaction)) {
            $method = $this->getMethodFromOrder($order);
            $message = __('EMS Order ID: %1', $transactionId);
            $status = $this->configRepository->getStatusPending($method, (int)$order->getStoreId());
            $order->addStatusToHistory($status, $message, false);
            $order->setEmspayTransactionId($transactionId);

            if ($testModus !== null) {
                /** @var Payment $payment */
                $payment = $order->getPayment();
                $payment->setAdditionalInformation('test_modus', $testModus);
            }

            $this->orderRepository->save($order);
        }

        if ($error = $this->configRepository->getError($transaction)) {
            return ['error' => $error];
        }

        if (in_array($method, [Banktransfer::METHOD_CODE, Afterpay::METHOD_CODE])) {
            return ['redirect' => $this->urlProvider->getSuccessProcessUrl((string)$transactionId)];
        }

        if ($transaction !== null && !empty($transaction['transactions'][0]['payment_url'])) {
            return ['redirect' => $transaction['transactions'][0]['payment_url']];
        }

        return ['error' => __('Error, could not fetch redirect url')];
    }
}
