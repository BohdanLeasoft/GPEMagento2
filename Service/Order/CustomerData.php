<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Order;

use EMSPay\Payment\Api\Config\RepositoryInterface as ConfigRepository;
use EMSPay\Payment\Model\Methods\Afterpay;
use EMSPay\Payment\Model\Methods\Klarna;
use EMSPay\Payment\Model\Methods\KlarnaDirect;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Locale\Resolver;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Order Customer Data class
 */
class CustomerData
{

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * CustomerData constructor.
     *
     * @param Resolver $resolver
     * @param Header $httpHeader
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        Resolver $resolver,
        Header $httpHeader,
        ConfigRepository $configRepository
    ) {
        $this->resolver = $resolver;
        $this->httpHeader = $httpHeader;
        $this->configRepository = $configRepository;
    }

    /**
     * @param OrderInterface $order
     * @param string $method
     *
     * @return array
     */
    public function get(OrderInterface $order, string $method): array
    {
        $customer = $order->getBillingAddress();
        $additionalData = $order->getPayment()->getAdditionalInformation();
        $street = implode(' ', $customer->getStreet());
        list($address, $houseNumber) = $this->parseAddress($street);

        $postCode = $customer->getPostcode();
        if (strlen($postCode) == 6) {
            $postCode = wordwrap($postCode, 4, ' ', true);
        }

        $customerData = [
            'merchant_customer_id' => $customer->getEntityId(),
            'email_address' => $customer->getEmail(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            'address_type' => $customer->getAddressType(),
            'address' => $street,
            'postal_code' => $postCode,
            'housenumber' => $houseNumber,
            'country' => $customer->getCountryId(),
            'phone_numbers' => [$customer->getTelephone()],
            'user_agent' => $this->getUserAgent(),
            'ip_address' => $order->getRemoteIp(),
            'forwarded_ip' => $order->getXForwardedFor(),
            'locale' => $this->resolver->getLocale()
        ];

        if (isset($additionalData['prefix'])) {
            $customerData['gender'] = $additionalData['prefix'];
        }

        if (isset($additionalData['dob'])) {
            $customerData['birthdate'] = date('Y-m-d', strtotime($additionalData['dob']));
        }

        if ($method == Klarna::METHOD_CODE || $method == Afterpay::METHOD_CODE) {
            $customerData['address'] = implode(' ', [trim($street), $postCode, trim($customer->getCity())]);
        }

        if ($method == KlarnaDirect::METHOD_CODE) {
            $customerData['address'] = implode(' ', [trim($customer->getCity()), trim($address)]);
        }

        $this->configRepository->addTolog('customer', $customerData);

        return $customerData;
    }

    /**
     * @param string $streetAddress
     *
     * @return array
     */
    private function parseAddress(string $streetAddress): array
    {
        $address = $streetAddress;
        $houseNumber = '';

        $offset = strlen($streetAddress);

        while (($offset = $this->rstrpos($streetAddress, ' ', $offset)) !== false) {
            if ($offset < strlen($streetAddress) - 1 && is_numeric($streetAddress[$offset + 1])) {
                $address = trim(substr($streetAddress, 0, $offset));
                $houseNumber = trim(substr($streetAddress, $offset + 1));
                break;
            }
        }

        if (empty($houseNumber) && strlen($streetAddress) > 0 && is_numeric($streetAddress[0])) {
            $pos = strpos($streetAddress, ' ');

            if ($pos !== false) {
                $houseNumber = trim(substr($streetAddress, 0, $pos), ", \t\n\r\0\x0B");
                $address = trim(substr($streetAddress, $pos + 1));
            }
        }

        return [$address, $houseNumber];
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @param null|int $offset
     *
     * @return int
     */
    private function rstrpos($haystack, $needle, $offset = null)
    {
        $size = strlen($haystack);

        if (null === $offset) {
            $offset = $size;
        }

        $pos = strpos(strrev($haystack), strrev($needle), $size - $offset);

        if ($pos === false) {
            return 0;
        }

        return $size - $pos - strlen($needle);
    }

    /**
     * Customer user agent for API
     *
     * @return mixed
     */
    private function getUserAgent()
    {
        return $this->httpHeader->getHttpUserAgent();
    }
}
