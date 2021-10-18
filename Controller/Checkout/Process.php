<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use EMSPay\Payment\Model\Ems as EmsModel;
use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Checkout process controller class
 */
class Process extends Action
{

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var EmsModel
     */
    private $emsModel;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Success constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param EmsModel $emsModel
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        EmsModel $emsModel,
        ConfigRepository $configRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->emsModel = $emsModel;
        $this->configRepository = $configRepository;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id', null);

        if ($orderId === null) {
            $this->configRepository->addTolog('error', __('Invalid return, missing order id.'));
            $this->messageManager->addNoticeMessage(__('Invalid return from EMS.'));
            return $this->_redirect('checkout/cart');
        }

        try {
            $status = $this->emsModel->processTransaction($orderId, 'success');
            if (!empty($status['success'])) {
                $this->checkoutSession->start();
                return $this->_redirect('checkout/onepage/success?utm_nooverride=1');
            } else {
                $this->checkoutSession->restoreQuote();
                if (!empty($status['cart_msg'])) {
                    $this->messageManager->addNoticeMessage($status['cart_msg']);
                } else {
                    $this->messageManager->addNoticeMessage(__('Something went wrong.'));
                }
            }
        } catch (\Exception $e) {
            $this->configRepository->addTolog('error', $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status.'));
        }

        return $this->_redirect('checkout/cart');
    }
}
