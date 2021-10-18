<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Methods;

use EMSPay\Payment\Model\Ems;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Banktransfer method class
 */
class Banktransfer extends Ems
{

    /** Payment Code */
    const METHOD_CODE = 'emspay_methods_banktransfer';

    /** Platform Method Code */
    const PLATFORM_CODE = 'bank-transfer';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = \EMSPay\Payment\Block\Info\Banktransfer::class;

    /**
     * @param OrderInterface $order
     *
     * @return array
     * @throws \Exception
     * @throws LocalizedException
     */
    public function startTransaction(OrderInterface $order): array
    {
        return parent::prepareTransaction(
            $order,
            self::PLATFORM_CODE,
            self::METHOD_CODE
        );
    }

    /**
     * @return string
     */
    public function getMailingAddress(): string
    {
        if ($accountDetails = $this->configRepository->getAccountDetails()) {
            return implode(
                PHP_EOL,
                [
                    __('Amount: %1', '%AMOUNT%'),
                    __('Reference: %1', '%REFERENCE%'),
                    __('IBAN: %1', $accountDetails['iban']),
                    __('BIC: %1', $accountDetails['bic']),
                    __('Account holder: %1', $accountDetails['holder']),
                    __('City: %1', $accountDetails['city']),
                ]
            );
        }
        return '';
    }
}
