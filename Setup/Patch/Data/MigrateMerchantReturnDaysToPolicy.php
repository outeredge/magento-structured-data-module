<?php

namespace OuterEdge\StructuredData\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class MigrateMerchantReturnDaysToPolicy implements DataPatchInterface
{
    private const SOURCE_PATH = 'structureddata/shipping_return/merchant_return_days';
    private const DESTINATION_PATH = 'structureddata/shipping_return/return_policy';

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private WriterInterface $configWriter
    ) {
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');
        $sourceSelect = $connection->select()
            ->from($configTable, ['scope', 'scope_id', 'value'])
            ->where('path = ?', self::SOURCE_PATH)
            ->where('value > 0');

        foreach ($connection->fetchAll($sourceSelect) as $row) {
            $scope = (string) ($row['scope'] ?? 'default');
            $scopeId = (int) ($row['scope_id'] ?? 0);

            $destinationSelect = $connection->select()
                ->from($configTable, ['config_id', 'value'])
                ->where('scope = ?', $scope)
                ->where('scope_id = ?', $scopeId)
                ->where('path = ?', self::DESTINATION_PATH)
                ->limit(1);
            $destinationRow = $connection->fetchRow($destinationSelect);

            // An existing row, including an explicitly saved empty value, is
            // an intentional destination setting and must not be overwritten.
            if ($destinationRow !== false) {
                continue;
            }

            $this->configWriter->save(
                self::DESTINATION_PATH,
                'MerchantReturnFiniteReturnWindow',
                $scope,
                $scopeId
            );
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
