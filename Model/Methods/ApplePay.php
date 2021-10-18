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
 * ApplePay method class
 */
class ApplePay extends Ems
{

    /** Payment Code */
    const METHOD_CODE = 'emspay_methods_applepay';

    /** Platform Method Code */
    const PLATFORM_CODE = 'apple-pay';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

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
}
