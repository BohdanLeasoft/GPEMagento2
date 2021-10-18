<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use EMSPay\Payment\Model\Methods\Klarna;
use EMSPay\Payment\Model\Methods\Afterpay;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * SalesOrderShipmentAfter observer class
 */
class SalesOrderShipmentAfter implements ObserverInterface
{

    /**
     * @var Klarna
     */
    private $klarnaModel;

    /**
     * @var Afterpay
     */
    private $afterpayModel;

    /**
     * SalesOrderShipmentAfter constructor.
     *
     * @param Klarna $klarnaModel
     * @param Afterpay $afterpayModel
     */
    public function __construct(
        Klarna $klarnaModel,
        Afterpay $afterpayModel
    ) {
        $this->klarnaModel = $klarnaModel;
        $this->afterpayModel = $afterpayModel;
    }

    /**
     * @param Observer $observer
     *
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getEvent()->getShipment()->getOrder();

        switch ($order->getPayment()->getMethod()) {
            case Klarna::METHOD_CODE:
                $this->klarnaModel->captureOrder($order);
                break;
            case Afterpay::METHOD_CODE:
                $this->afterpayModel->captureOrder($order);
                break;
        }
    }
}
