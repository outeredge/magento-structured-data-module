<?php

namespace OuterEdge\StructuredData\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class MigrateRelatedPagesToSameAs implements DataPatchInterface
{
    private const SOURCE_PATH = 'structureddata/contact/related_pages';
    private const DESTINATION_PATH = 'structureddata/organization/sameas';

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private WriterInterface $configWriter,
        private SerializerInterface $serializer
    ) {
    }

    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');
        $sourceSelect = $connection->select()
            ->from($configTable, ['scope', 'scope_id', 'value'])
            ->where('path = ?', self::SOURCE_PATH);

        foreach ($connection->fetchAll($sourceSelect) as $row) {
            $scope = (string) ($row['scope'] ?? 'default');
            $scopeId = (int) ($row['scope_id'] ?? 0);
            $urls = $this->extractUrls(
                (string) ($row['value'] ?? ''),
                $scope,
                $scopeId
            );
            if ($urls === []) {
                continue;
            }

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
                implode("\n", $urls),
                $scope,
                $scopeId
            );
        }

        return $this;
    }

    /**
     * @return array<int, string>
     */
    private function extractUrls(string $value, string $scope, int $scopeId): array
    {
        if (trim($value) === '') {
            return [];
        }

        try {
            $pages = $this->serializer->unserialize($value);
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Unable to migrate legacy related pages for scope "%s" (%d): invalid serialized data.',
                    $scope,
                    $scopeId
                ),
                0,
                $exception
            );
        }

        if (!is_array($pages)) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Unable to migrate legacy related pages for scope "%s" (%d): expected an array of URL rows.',
                    $scope,
                    $scopeId
                )
            );
        }

        $urls = [];
        foreach ($pages as $page) {
            if (!is_array($page) || !array_key_exists('url', $page)) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Unable to migrate legacy related pages for scope "%s" (%d): malformed URL row.',
                        $scope,
                        $scopeId
                    )
                );
            }

            $rawUrl = $page['url'];
            if ($rawUrl !== null && !is_scalar($rawUrl)) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Unable to migrate legacy related pages for scope "%s" (%d): URL row is not scalar.',
                        $scope,
                        $scopeId
                    )
                );
            }

            $url = trim((string) $rawUrl);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return $urls;
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
