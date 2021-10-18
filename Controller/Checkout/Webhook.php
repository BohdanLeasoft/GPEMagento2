<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Controller\Checkout;

use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use EMSPay\Payment\Model\Ems as EmsModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem\Driver\File as FilesystemDriver;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Helper\Data as PaymentHelper;

/**
 * Webhook controller class
 */
class Webhook extends Action
{

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

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
     * @var Json
     */
    private $json;

    /**
     * @var FilesystemDriver
     */
    private $filesystemDriver;

    /**
     * Webhook constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param EmsModel $emsModel
     * @param ConfigRepository $configRepository
     * @param Json $json
     * @param FilesystemDriver $filesystemDriver
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        EmsModel $emsModel,
        ConfigRepository $configRepository,
        Json $json,
        FilesystemDriver $filesystemDriver
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->resultFactory = $context->getResultFactory();
        $this->emsModel = $emsModel;
        $this->configRepository = $configRepository;
        $this->resultFactory = $context->getResultFactory();
        $this->json = $json;
        $this->filesystemDriver = $filesystemDriver;
        parent::__construct($context);
    }

    /**
     * EMS Webhook Controller
     *
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        try {
            $input = $this->json->unserialize(
                $this->filesystemDriver->fileGetContents("php://input")
            );
            $this->configRepository->addTolog('webhook', $input);
        } catch (\Exception $e) {
            $input = null;
            $this->configRepository->addTolog('error', 'Webhook exception: ' . $e->getMessage());
        }

        if (!$input) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $result->setHttpResponseCode(503);
            return $result;
        }

        if (isset($input['order_id'])) {
            try {
                $this->emsModel->processTransaction($input['order_id'], 'webhook');
            } catch (\Exception $e) {
                $this->configRepository->addTolog('error', $e->getMessage());
                $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $result->setHttpResponseCode(503);
                return $result;
            }
        }
    }
}
