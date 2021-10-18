<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Block\Info;

use Magento\Payment\Block\Info;
use Magento\Framework\Exception\LocalizedException;

/**
 * Afterpay info class
 */
class Afterpay extends Info
{

    /**
     * @var null|string|bool
     */
    private $testModus = null;

    /**
     * @var string
     */
    protected $_template = 'EMSPay_Payment::info/afterpay.phtml';

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getTestModus()
    {
        if ($this->testModus === null) {
            $testModusFlag = $this->getInfo()->getAdditionalInformation('test_modus');
            $this->testModus = $testModusFlag == 'afterpay';
        }
        return $this->testModus;
    }
}
