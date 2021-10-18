<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Transaction\Process;

use Magento\Sales\Api\Data\OrderInterface;
use EMSPay\Payment\Service\Transaction\AbstractTransaction;

/**
 * Unknown process class
 */
class Unknown extends AbstractTransaction
{

    /**
     * Execute unkown return status
     *
     * @param OrderInterface $order
     * @param string $type
     * @param string $status
     *
     * @return array
     */
    public function execute(
        OrderInterface $order,
        string $type,
        string $status
    ): array {
        $result = [
            'success' => false,
            'status' => $status,
            'order_id' => $order->getEntityId(),
            'type' => $type
        ];

        $this->configRepository->addTolog('success', $result);
        return $result;
    }
}
