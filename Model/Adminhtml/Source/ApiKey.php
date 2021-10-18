<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * ApiKey source class
 */
class ApiKey implements ArrayInterface
{

    /**
     * Live/Test Key Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'live', 'label' => __('Live')],
            ['value' => 'test', 'label' => __('Test')]
        ];
    }
}
