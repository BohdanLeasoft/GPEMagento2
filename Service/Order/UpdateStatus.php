<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Order;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;

/**
 * Update status service class
 */
class UpdateStatus
{

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * UpdateStatus constructor.
     *
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        OrderRepository $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param OrderInterface $order
     * @param string $status
     * @return OrderInterface
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute(OrderInterface $order, string $status) : OrderInterface
    {
        if ($order->getStatus() !== $status) {
            $msg = __('Status updated from %1 to %2', $order->getStatus(), $status);
            $order->addStatusToHistory($status, $msg, false);
            $this->orderRepository->save($order);
        }

        return $order;
    }
}
