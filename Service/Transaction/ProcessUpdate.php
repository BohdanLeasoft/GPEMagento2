<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Service\Transaction;

use EMSPay\Payment\Service\Transaction\Process\Cancelled;
use EMSPay\Payment\Service\Transaction\Process\Complete;
use EMSPay\Payment\Service\Transaction\Process\Error;
use EMSPay\Payment\Service\Transaction\Process\Expired;
use EMSPay\Payment\Service\Transaction\Process\Processing;
use EMSPay\Payment\Service\Transaction\Process\Unknown;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Process Update service class
 */
class ProcessUpdate
{

    /**
     * @var Processing
     */
    private $processing;

    /**
     * @var Cancelled
     */
    private $cancelled;

    /**
     * @var Error
     */
    private $error;

    /**
     * @var Expired
     */
    private $expired;

    /**
     * @var Complete
     */
    private $complete;

    /**
     * @var Unknown
     */
    private $unknown;

    /**
     * Process constructor.
     *
     * @param Processing $processing
     * @param Cancelled $cancelled
     * @param Error $error
     * @param Expired $expired
     * @param Complete $complete
     * @param Unknown $unknown
     */
    public function __construct(
        Processing $processing,
        Cancelled $cancelled,
        Error $error,
        Expired $expired,
        Complete $complete,
        Unknown $unknown
    ) {
        $this->processing = $processing;
        $this->cancelled = $cancelled;
        $this->error = $error;
        $this->expired = $expired;
        $this->complete = $complete;
        $this->unknown = $unknown;
    }

    /**
     * @param array $transaction
     * @param OrderInterface $order
     * @param string $type
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $transaction, OrderInterface $order, string $type): array
    {
        $status = !empty($transaction['status']) ? $transaction['status'] : '';
        switch ($status) {
            case 'error':
                return $this->error->execute($order, $type);
            case 'expired':
                return $this->expired->execute($order, $type);
            case 'cancelled':
                return $this->cancelled->execute($order, $type);
            case 'completed':
                return $this->complete->execute($transaction, $order, $type);
            case 'processing':
                return $this->processing->execute($transaction, $order, $type);
            default:
                return $this->unknown->execute($order, $type, $status);
        }
    }
}
