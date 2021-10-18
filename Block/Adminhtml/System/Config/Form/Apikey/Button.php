<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Block\Adminhtml\System\Config\Form\Apikey;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Api Button class
 */
class Button extends Field
{

    /**
     * @var string
     */
    protected $_template = 'EMSPay_Payment::system/config/button/apikey.phtml';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Checker constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->request = $context->getRequest();
        parent::__construct($context, $data);
    }

    /**
     * Removes use Default Checkbox
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Prepare html output
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Ajax Url getter
     *
     * @return string
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('emspay/action/apikey');
    }

    /**
     * Prepare button output
     *
     * @return string
     */
    public function getButtonHtml(): string
    {
        try {
            $buttonData = ['id' => 'apikey_button', 'label' => __('Test API Key')];
            $button = $this->getLayout()->createBlock(WidgetButton::class)->setData($buttonData);
            return $button->toHtml();
        } catch (\Exception $e) {
            return '';
        }
    }
}
