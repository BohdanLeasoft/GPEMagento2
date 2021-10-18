<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Order;

use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * OrderLines order class
 */
class OrderLines
{

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * OrderLines constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->configRepository = $configRepository;
    }

    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    public function get(OrderInterface $order)
    {
        $orderLines = [];

        foreach ($order->getItems() as $item) {
            $orderLines[] = [
                'type' => 'physical',
                'url' => $this->getItemUrl($item),
                'name' => $this->getItemName($item),
                'amount' => $this->getItemAmount($item),
                'currency' => $order->getOrderCurrencyCode(),
                'quantity' => $item->getQtyOrdered() ? round($item->getQtyOrdered()) : 1,
                'image_url' => $this->getImageUrl($item),
                'vat_percentage' => $this->configRepository->getAmountInCents((float)$item->getTaxPercent()),
                'merchant_order_line_id' => $item->getItemId()
            ];
        }

        if ($order->getShippingAmount() > 0) {
            $orderLines[] = $this->getShippingOrderLine($order);
        }

        $this->configRepository->addTolog('orderLines', $orderLines);

        return $orderLines;
    }

    /**
     * @param OrderItemInterface $item
     *
     * @return mixed
     */
    private function getItemUrl(OrderItemInterface $item)
    {
        return $item->getProduct()->getProductUrl();
    }

    /**
     * @param OrderItemInterface $item
     *
     * @return null|string
     */
    private function getItemName(OrderItemInterface $item)
    {
        return preg_replace("/[^A-Za-z0-9 ]/", "", $item->getName());
    }

    /**
     * @param OrderItemInterface $item
     *
     * @return int
     */
    private function getItemAmount(OrderItemInterface $item)
    {
        return $this->configRepository->getAmountInCents((
                $item->getRowTotal()
                - $item->getDiscountAmount()
                + $item->getTaxAmount()
                + $item->getDiscountTaxCompensationAmount()
            )
            / $item->getQtyOrdered());
    }

    /**
     * @param OrderItemInterface $item
     *
     * @return string
     */
    private function getImageUrl(OrderItemInterface $item)
    {
        $imagePath = $this->configRepository->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return $imagePath . 'catalog/product' . $item->getProduct()->getImage();
    }

    /**
     * @param OrderInterface $order
     *
     * @return array
     */
    private function getShippingOrderLine(OrderInterface $order)
    {
        return [
            'type' => 'shipping_fee',
            'name' => $this->getShippingName($order),
            'amount' => $this->getShippingAmount($order),
            'currency' => $order->getOrderCurrencyCode(),
            'vat_percentage' => $this->getShippingVatPercentage($order),
            'quantity' => 1,
            'merchant_order_line_id' => 'shipping'
        ];
    }

    /**
     * @param OrderInterface $order
     *
     * @return null|string
     */
    private function getShippingName(OrderInterface $order)
    {
        return preg_replace("/[^A-Za-z0-9 ]/", "", $order->getShippingDescription());
    }

    /**
     * @param OrderInterface $order
     *
     * @return int
     */
    private function getShippingAmount(OrderInterface $order)
    {
        return $this->configRepository->getAmountInCents((float)(
            $order->getShippingAmount()
            + $order->getShippingTaxAmount()
            + $order->getShippingDiscountTaxCompensationAmount()
        ));
    }

    /**
     * @param OrderInterface $order
     *
     * @return float|int
     */
    public function getShippingVatPercentage(OrderInterface $order)
    {
        $vatPercentage = 0;
        if ($order->getShippingAmount() > 0) {
            $vatPercentage = ($order->getShippingTaxAmount() / $order->getShippingAmount()) * 100;
        }

        return $this->configRepository->getAmountInCents((float)$vatPercentage);
    }

    /**
     * @param Creditmemo $creditmemo
     * @param bool $addShipping
     *
     * @return array
     */
    public function getRefundLines($creditmemo, $addShipping = false)
    {
        $orderLines = [];
        $refundItems = $creditmemo->getAllItems();

        /** @var CreditmemoItemInterface $item */
        foreach ($refundItems as $item) {
            $orderLines[] = [
                'merchant_order_line_id' => $item->getOrderItemId(),
                'quantity' => $item->getQty()
            ];
        }

        if ($addShipping) {
            $orderLines[] = [
                'merchant_order_line_id' => 'shipping',
                'quantity' => 1
            ];
        }

        return $orderLines;
    }
}
