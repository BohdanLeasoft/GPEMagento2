<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use Magento\Sales\Model\Order\Payment;

/**
 * Send invoice email service class
 */
class SendInvoiceEmail
{

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    /**
     * SendInvoiceEmail constructor.
     *
     * @param InvoiceSender $invoiceSender
     * @param OrderCommentHistory $orderCommentHistory
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        InvoiceSender $invoiceSender,
        OrderCommentHistory $orderCommentHistory,
        ConfigRepository $configRepository
    ) {
        $this->invoiceSender = $invoiceSender;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->configRepository = $configRepository;
    }

    /**
     * @param OrderInterface $order
     *
     * @throws LocalizedException
     */
    public function execute(OrderInterface $order)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance()->getCode();

        $invoice = $payment->getCreatedInvoice();
        $sendInvoice = $this->configRepository->sendInvoice($method, (int)$order->getStoreId());

        if ($invoice && $sendInvoice && !$invoice->getEmailSent()) {
            $this->invoiceSender->send($invoice);
            $msg = __('Invoice email sent to %1', $order->getCustomerEmail());
            $this->orderCommentHistory->add($order, $msg, true);
        }
    }
}
