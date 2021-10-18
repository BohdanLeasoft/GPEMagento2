<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model;

use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use EMSPay\Payment\Model\Api\GingerClient;
use EMSPay\Payment\Model\Api\UrlProvider;
use EMSPay\Payment\Service\Order\CustomerData;
use EMSPay\Payment\Service\Order\GetOrderByTransaction;
use EMSPay\Payment\Service\Order\OrderLines;
use EMSPay\Payment\Service\Transaction\ProcessRequest as ProcessTransactionRequest;
use EMSPay\Payment\Service\Transaction\ProcessUpdate as ProcessTransactionUpdate;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Ems payment class
 */
class Ems extends AbstractMethod
{

    /**
     * @var ConfigRepository
     */
    public $configRepository;
    /**
     * @var Session
     */
    public $checkoutSession;
    /**
     * @var string
     */
    public $webhookUrl = null;
    /**
     * @var string
     */
    public $returnUrl = null;
    /**
     * @var CustomerData
     */
    public $customerData;
    /**
     * @var OrderLines
     */
    public $orderLines;
    /**
     * @var ManagerInterface
     */
    public $messageManager;
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_isOffline = false;
    /**
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * @var bool
     */
    protected $_canUseInternal = false;
    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * @var \Ginger\ApiClient
     */
    private $client = null;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var GetOrderByTransaction
     */
    private $getOrderByTransaction;
    /**
     * @var GingerClient
     */
    private $gingerClient;
    /**
     * @var ProcessTransactionRequest
     */
    private $processTransactionRequest;
    /**
     * @var ProcessTransactionUpdate
     */
    private $processTransactionUpdate;
    /**
     * @var UrlProvider
     */
    private $urlProvider;

    /**
     * Ems constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param ConfigRepository $configRepository
     * @param GingerClient $gingerClient
     * @param ProcessTransactionRequest $processTransactionRequest
     * @param ProcessTransactionUpdate $processTransactionUpdate
     * @param OrderLines $orderLines
     * @param CustomerData $customerData
     * @param Session $checkoutSession
     * @param Order $order
     * @param GetOrderByTransaction $getOrderByTransaction
     * @param UrlProvider $urlProvider
     * @param ManagerInterface $messageManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ConfigRepository $configRepository,
        GingerClient $gingerClient,
        ProcessTransactionRequest $processTransactionRequest,
        ProcessTransactionUpdate $processTransactionUpdate,
        OrderLines $orderLines,
        CustomerData $customerData,
        Session $checkoutSession,
        Order $order,
        GetOrderByTransaction $getOrderByTransaction,
        UrlProvider $urlProvider,
        ManagerInterface $messageManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->configRepository = $configRepository;
        $this->gingerClient = $gingerClient;
        $this->processTransactionRequest = $processTransactionRequest;
        $this->processTransactionUpdate = $processTransactionUpdate;
        $this->customerData = $customerData;
        $this->orderLines = $orderLines;
        $this->checkoutSession = $checkoutSession;
        $this->order = $order;
        $this->getOrderByTransaction = $getOrderByTransaction;
        $this->urlProvider = $urlProvider;
        $this->messageManager = $messageManager;
    }

    /**
     * Extra checks for method availability
     *
     * @param CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if ($quote == null) {
            $quote = $this->checkoutSession->getQuote();
        }

        if (!$this->configRepository->isAvailable((int)$quote->getStoreId())) {
            return false;
        }

        if ($quote->getQuoteCurrencyCode() != 'EUR') {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this|void
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();

        /** @var Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $order->setIsNotified(false);

        $status = $this->configRepository->getStatusPending((string)$this->_code, (int)$order->getId());
        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);
    }

    /**
     * @param string $transactionId
     * @param string $type
     *
     * @return array
     * @throws \Exception
     */
    public function processTransaction(string $transactionId, string $type): array
    {
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('OrderId not set')];
            $this->configRepository->addTolog('error', $msg);
            return $msg;
        }

        $order = $this->getOrderByTransaction->execute($transactionId);
        if (!$order) {
            $msg = ['error' => true, 'msg' => __('Order not found for transaction id: %1', $transactionId)];
            if ($type != 'webhook') {
                $this->configRepository->addTolog('error', $msg);
            }
            return $msg;
        }

        $storeId = (int)$order->getStoreId();
        $method = $order->getPayment()->getMethodInstance()->getCode();
        $testModus = $order->getPayment()->getAdditionalInformation();
        if (array_key_exists('test_modus', $testModus)) {
            $testModus = $testModus['test_modus'];
        }
        $testApiKey = $this->configRepository->getTestKey((string)$method, (int)$storeId, (string)$testModus);

        $client = $this->loadGingerClient($storeId, $testApiKey);
        if (!$client) {
            $msg = ['error' => true, 'msg' => __('Could not load Client')];
            $this->configRepository->addTolog('error', $msg);
            return $msg;
        }

        $transaction = $client->getOrder($transactionId);
        $this->configRepository->addTolog('process', $transaction);

        if (empty($transaction['id'])) {
            $msg = ['error' => true, 'msg' => __('Transaction not found')];
            $this->configRepository->addTolog('error', $msg);
            return $msg;
        }

        return $this->processTransactionUpdate->execute($transaction, $order, $type);
    }

    /**
     * @param int $storeId
     * @param string $testApiKey
     *
     * @return bool|\Ginger\ApiClient
     * @throws \Exception
     */
    public function loadGingerClient(int $storeId = null, string $testApiKey = null)
    {
        if (!$this->client || $testApiKey !== null) {
            $this->client = $this->gingerClient->get($storeId, $testApiKey);
        }

        return $this->client;
    }

    /**
     * @param \Ginger\ApiClient $client
     *
     * @return array
     */
    public function getIssuers($client)
    {
        return $client->getIdealIssuers();
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return $this
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();
        $storeId = (int)$order->getStoreId();
        $transactionId = $order->getEmspayTransactionId();

        $method = $order->getPayment()->getMethodInstance()->getCode();
        $testApiKey = $this->configRepository->getTestKey((string)$method, (int)$storeId);

        try {
            $client = $this->loadGingerClient($storeId, $testApiKey);
            $emsOrder = $client->refundOrder(
                $transactionId,
                [
                    'amount' => $this->configRepository->getAmountInCents((float)$amount),
                    'currency' => $order->getOrderCurrencyCode()
                ]
            );
        } catch (\Exception $e) {
            $errorMsg = __('Error: not possible to create an online refund: %1', $e->getMessage());
            $this->configRepository->addTolog('error', $errorMsg);
            throw new LocalizedException($errorMsg);
        }

        if (in_array($emsOrder['status'], ['error', 'cancelled', 'expired'])) {
            $reason = current($emsOrder['transactions'])['reason'] ?? 'Refund order is not completed';
            $errorMsg = __('Error: not possible to create an online refund: %1', $reason);
            $this->configRepository->addTolog('error', $errorMsg);
            throw new LocalizedException($errorMsg);
        }

        return $this;
    }

    /**
     * @param OrderInterface $order
     * @param string $platformCode
     * @param string $methodCode
     *
     * @return array
     * @throws \Exception
     * @throws LocalizedException
     */
    public function prepareTransaction(OrderInterface $order, $platformCode, $methodCode): array
    {
        $orderData = [
            'amount' => $this->configRepository->getAmountInCents((float)$order->getBaseGrandTotal()),
            'currency' => $order->getOrderCurrencyCode(),
            'description' => $this->configRepository->getDescription($order, $methodCode),
            'merchant_order_id' => $order->getIncrementId(),
            'return_url' => $this->getReturnUrl(),
            'webhook_url' => $this->getWebhookUrl(),
            'transactions' => [['payment_method' => $platformCode]],
            'extra' => ['plugin' => $this->configRepository->getPluginVersion()]
        ];

        $testModus = false;
        $testApiKey = null;
        switch ($platformCode) {
            case 'afterpay':
                $orderData += [
                    'order_lines' => $this->orderLines->get($order),
                    'customer' => $this->customerData->get($order, $methodCode)
                ];
                $testApiKey = $this->configRepository->getAfterpayTestApiKey((int)$order->getStoreId());
                $testModus = $testApiKey ? 'afterpay' : false;
                break;
            case 'klarna-pay-later':
                $orderData += [
                    'order_lines' => $this->orderLines->get($order),
                    'customer' => $this->customerData->get($order, $methodCode)
                ];
                $testApiKey = $this->configRepository->getKlarnaTestApiKey((int)$order->getStoreId());
                $testModus = $testApiKey ? 'klarna' : false;
                break;
            case 'klarna-pay-now':
            case 'tikkie-payment-request':
            case 'payconiq':
            case 'amex':
                $orderData['customer'] = $this->customerData->get($order, $methodCode);
                break;
            case 'ideal':
                $issuer = null;
                $additionalData = $order->getPayment()->getAdditionalInformation();

                if (isset($additionalData['issuer'])) {
                    $issuer = $additionalData['issuer'];
                }
                $orderData['transactions'] = [
                    [
                        'payment_method' => $platformCode,
                        'payment_method_details' => ['issuer_id' => $issuer]
                    ]
                ];
                break;
        }

        $client = $this->loadGingerClient((int)$order->getStoreId(), $testApiKey);
        $transaction = $client->createOrder($orderData);
        return $this->processRequest($order, $transaction, $testModus);
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        if ($this->returnUrl === null) {
            $this->returnUrl = $this->urlProvider->getReturnUrl();
        }

        return $this->returnUrl;
    }

    /**
     * @return string
     */
    public function getWebhookUrl()
    {
        if ($this->webhookUrl === null) {
            $this->webhookUrl = $this->urlProvider->getWebhookUrl();
        }

        return $this->webhookUrl;
    }

    /**
     * @param OrderInterface $order
     * @param null $transaction
     * @param string $testModus
     *
     * @return array
     * @throws LocalizedException
     */
    public function processRequest(OrderInterface $order, $transaction = null, $testModus = '')
    {
        return $this->processTransactionRequest->execute($order, $transaction, $testModus);
    }
}
