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
use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Checkout redirect class
 */
class Redirect extends Action
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
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        ConfigRepository $configRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->configRepository = $configRepository;
        parent::__construct($context);
    }

    /**
     * EMS Redirect Controller
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        try {
            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->paymentHelper->getMethodInstance($method);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage('Unknown Error');
            $this->configRepository->addTolog('error', 'Unknown Error');
            $this->checkoutSession->restoreQuote();
            return $this->_redirect('checkout/cart');
        }

        if ($methodInstance instanceof \EMSPay\Payment\Model\Ems) {
            try {
                $result = $methodInstance->startTransaction($order);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Could not start transaction, please select other payment method.')
                );
                $this->configRepository->addTolog('error', $e->getMessage());
                $this->checkoutSession->restoreQuote();
                return $this->_redirect('checkout/cart');
            }

            if (!empty($result['error'])) {
                $this->messageManager->addErrorMessage($result['error']);
                $this->configRepository->addTolog('error', $result['error']);
                $this->checkoutSession->restoreQuote();
                return $this->_redirect('checkout/cart');
            }

            return $this->getResponse()->setRedirect($result['redirect']);
        }

        $this->messageManager->addErrorMessage('Unknown Error');
        $this->configRepository->addTolog('error', 'Unknown Error');
        $this->checkoutSession->restoreQuote();
        return $this->_redirect('checkout/cart');
    }
}
