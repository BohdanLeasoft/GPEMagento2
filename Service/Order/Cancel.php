<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Cancel order service class
 */
class Cancel
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Cancel constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @param OrderInterface $order
     *
     * @return bool
     */
    public function execute(OrderInterface $order): bool
    {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment = __("The order was canceled");
            $this->configRepository->addTolog('info', $order->getIncrementId() . ' ' . $comment);
            $order->registerCancellation($comment)->save();

            return true;
        }

        return false;
    }
}
