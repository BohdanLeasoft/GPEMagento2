<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Transaction\Process;

use EMSPay\Payment\Model\Methods\Banktransfer;
use EMSPay\Payment\Service\Transaction\AbstractTransaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Process process class
 */
class Processing extends AbstractTransaction
{

    /**
     * @var string
     */
    private $status = 'processing';

    /**
     * Execute "processing" return status
     *
     * @param array $transaction
     * @param OrderInterface $order
     * @param string $type
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(
        array $transaction,
        OrderInterface $order,
        string $type
    ): array {
        $method = $this->getMethodFromOrder($order);
        if ($method != Banktransfer::METHOD_CODE) {
            return [
                'success' => false,
                'status' => $this->status,
                'order_id' => $order->getEntityId(),
                'type' => $type
            ];
        }

        if ($type == 'webhook') {
            $order = $this->updateOrderTransaction($order, $transaction, Transaction::TYPE_AUTH);
            $this->sendOrderEmail->execute($order);
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
