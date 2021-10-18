<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Methods;

use EMSPay\Payment\Model\Ems;

/**
 * General method class
 */
class General extends Ems
{

    /** Payment Code */
    const METHOD_CODE = 'emspay_methods_general';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;
}
