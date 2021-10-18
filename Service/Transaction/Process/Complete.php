<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Transaction\Process;

use EMSPay\Payment\Service\Transaction\AbstractTransaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * Complete process class
 */
class Complete extends AbstractTransaction
{

    /**
     * @var string
     */
    private $status = 'complete';

    /**
     * Execute "complete" return status
     *
     * @param array $transaction
     * @param OrderInterface $order
     * @param string $type
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $transaction, OrderInterface $order, string $type): array
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        if (!$payment->getIsTransactionClosed() && $type == 'webhook') {
            $order = $this->captureOrderTransaction($order, $transaction);
            $this->sendOrderEmail->execute($order);
            $this->sendInvoiceEmail->execute($order);

            $method = $this->getMethodFromOrder($order);
            $status = $this->configRepository->getStatusProcessing($method, (int)$order->getStoreId());

            $this->updateStatus->execute($order, $status);
        }

        if ($type == 'success') {
            $this->checkoutSession->setLastQuoteId($order->getQuoteId())
                ->setLastSuccessQuoteId($order->getQuoteId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastOrderId($order->getEntityId());
        }

        $result = [
            'success' => true,
            'status' => $this->status,
            'order_id' => $order->getEntityId(),
            'type' => $type
        ];

        $this->configRepository->addTolog('success', $result);
        return $result;
    }
}
