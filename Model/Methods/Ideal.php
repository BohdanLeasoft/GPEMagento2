<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Methods;

use EMSPay\Payment\Model\Ems;
use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Ideal method class
 */
class Ideal extends Ems
{

    /** Payment Code */
    const METHOD_CODE = 'emspay_methods_ideal';

    /** Platform Method Code */
    const PLATFORM_CODE = 'ideal';

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

    /**
     * Assign issuer data to checkout fields
     *
     * @param DataObject $data
     *
     * @return $this
     * @throws LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getAdditionalData();
        if (isset($additionalData['selected_issuer'])) {
            $this->getInfoInstance()->setAdditionalInformation('issuer', $additionalData['selected_issuer']);
        }
        if (isset($additionalData['issuer'])) {
            $this->getInfoInstance()->setAdditionalInformation('issuer', $additionalData['issuer']);
        }
        return $this;
    }
}
