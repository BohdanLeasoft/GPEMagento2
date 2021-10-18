<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Transaction;

use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use EMSPay\Payment\Model\Api\UrlProvider;
use EMSPay\Payment\Model\Methods\Banktransfer;
use EMSPay\Payment\Service\Order\Cancel as CancelOrder;
use EMSPay\Payment\Service\Order\SendInvoiceEmail;
use EMSPay\Payment\Service\Order\SendOrderEmail;
use EMSPay\Payment\Service\Order\UpdateStatus;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;

/**
 * Transaction Abstract class
 */
class AbstractTransaction
{


    /**
     * @var ConfigRepository
     */
    public $configRepository;

    /**
     * @var SendOrderEmail
     */
    public $sendOrderEmail;

    /**
     * @var SendInvoiceEmail
     */
    public $sendInvoiceEmail;

    /**
     * @var OrderRepository
     */
    public $orderRepository;

    /**
     * @var CancelOrder
     */
    public $cancelOrder;

    /**
     * @var UpdateStatus
     */
    public $updateStatus;

    /**
     * @var UrlProvider
     */
    public $urlProvider;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * AbstractTransaction constructor.
     * @param ConfigRepository $configRepository
     * @param OrderRepository $orderRepository
     * @param CancelOrder $cancelOrder
     * @param SendOrderEmail $sendOrderEmail
     * @param SendInvoiceEmail $sendInvoiceEmail
     * @param UpdateStatus $updateStatus
     * @param UrlProvider $urlProvider
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        ConfigRepository $configRepository,
        OrderRepository $orderRepository,
        CancelOrder $cancelOrder,
        SendOrderEmail $sendOrderEmail,
        SendInvoiceEmail $sendInvoiceEmail,
        UpdateStatus $updateStatus,
        UrlProvider $urlProvider,
        CheckoutSession $checkoutSession
    ) {
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->cancelOrder = $cancelOrder;
        $this->sendOrderEmail = $sendOrderEmail;
        $this->sendInvoiceEmail = $sendInvoiceEmail;
        $this->updateStatus = $updateStatus;
        $this->urlProvider = $urlProvider;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param OrderInterface $order
     * @param array $transaction
     * @param string $type
     *
     * @return OrderInterface
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function updateOrderTransaction(
        OrderInterface $order,
        array $transaction,
        string $type
    ): OrderInterface {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        $payment->setTransactionId($transaction['id']);
        $payment->isSameCurrency();
        $payment->setIsTransactionClosed(false);
        $payment->addTransaction($type);

        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * @param OrderInterface $order
     *
     * @return mixed
     * @throws LocalizedException
     */
    public function getMethodFromOrder(OrderInterface $order)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        return $payment->getMethodInstance()->getCode();
    }

    /**
     * @param OrderInterface $order
     * @param string $method
     * @param array $transaction
     *
     * @return void
     * @throws LocalizedException
     */
    public function updateMailingAddress(OrderInterface $order, $method, $transaction)
    {
        if ($method !== Banktransfer::METHOD_CODE) {
            return;
        }

        /** @var Payment $payment */
        $payment = $order->getPayment();

        /** @var Banktransfer $methodInstance */
        $methodInstance = $payment->getMethodInstance();
        $mailingAddress = $methodInstance->getMailingAddress();
        $grandTotal = $this->configRepository->formatPrice($transaction['amount'] / 100);
        $reference = $transaction['transactions'][0]['payment_method_details']['reference'];
        $mailingAddress = str_replace('%AMOUNT%', $grandTotal, $mailingAddress);
        $mailingAddress = str_replace('%REFERENCE%', $reference, $mailingAddress);
        $mailingAddress = str_replace('\n', PHP_EOL, $mailingAddress);
        $payment->setAdditionalInformation('mailing_address', $mailingAddress);
    }

    /**
     * @param OrderInterface $order
     * @param array $transaction
     *
     * @return OrderInterface
     *
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function captureOrderTransaction(
        OrderInterface $order,
        array $transaction
    ): OrderInterface {

        /** @var Payment $payment */
        $payment = $order->getPayment();
        if ($order->hasInvoices() || $payment->getAmountPaid()) {
            $errorMsg = __('Order %1 already invoiced/paid, no need for capture', $order->getIncrementId());
            $this->configRepository->addTolog('error', $errorMsg);
            return $order;
        }

        $payment->setTransactionId($transaction['id']);
        $payment->isSameCurrency();
        $payment->setIsTransactionClosed(true);

        $amount = $transaction['amount'] / 100;
        $payment->registerCaptureNotification($amount, true);

        if ($order->getIsVirtual()) {
            $order->setState(Order::STATE_COMPLETE);
        } else {
            $order->setState(Order::STATE_PROCESSING);
        }

        $this->orderRepository->save($order);

        return $order;
    }
}
