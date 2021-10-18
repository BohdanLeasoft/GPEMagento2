<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use EMSPay\Payment\Model\Ems as EmsModel;
use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use Magento\Payment\Model\MethodInterface;

/**
 * EmsConfigProvider model class
 */
class EmsConfigProvider implements ConfigProviderInterface
{

    /**
     * @var array
     */
    private $methodCodes = [
        Methods\Bancontact::METHOD_CODE,
        Methods\Banktransfer::METHOD_CODE,
        Methods\Creditcard::METHOD_CODE,
        Methods\ApplePay::METHOD_CODE,
        Methods\Ideal::METHOD_CODE,
        Methods\KlarnaDirect::METHOD_CODE,
        Methods\Klarna::METHOD_CODE,
        Methods\Paypal::METHOD_CODE,
        Methods\Payconiq::METHOD_CODE,
        Methods\Afterpay::METHOD_CODE,
        Methods\Amex::METHOD_CODE,
        Methods\Tikkie::METHOD_CODE
    ];

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var EmsModel
     */
    private $emsModel;

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * EmsConfigProvider constructor.
     *
     * @param Ems              $emsModel
     * @param ConfigRepository $configRepository
     * @param PaymentHelper    $paymentHelper
     * @param Escaper          $escaper
     */
    public function __construct(
        EmsModel $emsModel,
        ConfigRepository $configRepository,
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->emsModel = $emsModel;
        $this->configRepository = $configRepository;
        $this->escaper = $escaper;
        $this->paymentHelper = $paymentHelper;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->getMethodInstance($code);
        }
    }

    /**
     * @param string $code
     *
     * @return MethodInterface|false
     */
    public function getMethodInstance(string $code)
    {
        try {
            return $this->paymentHelper->getMethodInstance($code);
        } catch (\Exception $e) {
            $this->configRepository->addTolog('error', 'Function: getMethodInstance: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Config Data for checkout
     *
     * @return array
     * @throws \Exception
     */
    public function getConfig(): array
    {
        $config = [];

        if (!$client = $this->emsModel->loadGingerClient()) {
            $activeMethods = [];
        } else {
            $activeMethods = $this->getActiveMethods();
        }

        foreach ($this->methodCodes as $code) {
            if (!empty($this->methods[$code]) && $this->methods[$code]->isAvailable()) {
                $config['payment'][$code]['instructions'] = $this->getInstructions($code);
                if ($code == Methods\Ideal::METHOD_CODE && $client) {
                    $config['payment'][$code]['issuers'] = $this->getIssuers($client);
                }
                if ($code == Methods\Banktransfer::METHOD_CODE) {
                    $config['payment'][$code]['mailingAddress'] = $this->getMailingAddress($code);
                }
                if ($code == Methods\Klarna::METHOD_CODE) {
                    $config['payment'][$code]['prefix'] = $this->getCustomerPrefixes();
                }
                if ($code == Methods\Afterpay::METHOD_CODE) {
                    $config['payment'][$code]['prefix'] = $this->getCustomerPrefixes();
                    $config['payment'][$code]['conditionsLinkNl'] = Methods\Afterpay::TERMS_NL_URL;
                    $config['payment'][$code]['conditionsLinkBe'] = Methods\Afterpay::TERMS_BE_URL;
                }
                if (in_array($code, $activeMethods)) {
                    $config['payment'][$code]['isActive'] = true;
                } else {
                    $config['payment'][$code]['isActive'] = false;
                }
                $config['payment'][$code]['logo'] = $this->configRepository->getPaymentLogo($code);
            } else {
                $config['payment'][$code]['isActive'] = false;
            }
        }

        return $config;
    }

    /**
     * @return array
     */
    public function getActiveMethods()
    {
        return $this->methodCodes;
    }

    /**
     * Instruction data
     *
     * @param string $code
     *
     * @return string
     */
    protected function getInstructions(string $code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    /**
     * @param \Ginger\ApiClient $client
     *
     * @return array|bool
     */
    public function getIssuers($client)
    {
        if ($issuers = $this->emsModel->getIssuers($client)) {
            return $issuers;
        }
        return false;
    }

    /**
     * @param string $code
     *
     * @return string
     */
    protected function getMailingAddress(string $code): string
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getMailingAddress()));
    }

    /**
     * @return array
     */
    public function getCustomerPrefixes(): array
    {
        return [
            ['id' => 'male', 'name' => 'Mr.'],
            ['id' => 'female', 'name' => 'Ms.']
        ];
    }
}
