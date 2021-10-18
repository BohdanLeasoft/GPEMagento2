<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace EMSPay\Payment\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

/**
 * UpgradeData setup class
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var SetupData
     */
    private $installer;

    /**
     * UpgradeData constructor.
     *
     * @param SetupData $installer
     */
    public function __construct(SetupData $installer)
    {
        $this->installer = $installer;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $moduleVersion = $context->getVersion();

        if (version_compare($moduleVersion, '0.9.0', '<')) {
            $this->installer->addTansactionId($setup);
            $this->installer->addIndex($setup);
        }
    }
}
