<?php declare(strict_types=1);

namespace Pim\Upgrade\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This migration will delete the empty values from metric values of products.
 * For example, the value {attr: {<all_channels>: {<all_locales>: {"amount": null, "unit": null}}}} will be removed
 * from the raw_values field.
 */
final class Version_5_0_20200224123916_remove_product_empty_amount_values
    extends AbstractMigration
    implements ContainerAwareInterface
{
    private const MYSQL_BATCH_SIZE = 1000;

    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SELECT "disable migration warning"');

        /** @var string[] $metricAttributesCodes */
        $metricAttributesCodes = $this->findMetricAttributesCodes();
        /** @var string[] $priceCollectionAttributesCodes */
        $priceCollectionAttributesCodes = $this->findPriceCollectionAttributesCodes();

        $rows = $this->getAllProducts();
        foreach ($rows as $i => $row) {
            $values = json_decode($row['raw_values'], true);

            $cleanValues = $this->cleanMetricValues($values, $metricAttributesCodes);
            $cleanValues = $this->cleanPriceCollectionValues($cleanValues, $priceCollectionAttributesCodes);

            if ($values !== $cleanValues) {
                $this->connection->executeQuery(
                    'UPDATE pim_catalog_product SET raw_values = :rawValues WHERE identifier = :identifier',
                    [
                        'rawValues' => json_encode((object)$cleanValues),
                        'identifier' => $row['identifier'],
                    ], [
                        'rawValues' => Types::STRING,
                        'identifier' => Types::STRING,
                    ]
                );
            }
        }
    }

    private function cleanMetricValues(array $values, array $metricAttributesCodes): array
    {
        foreach ($metricAttributesCodes as $attributeCode) {
            if (!isset($values[$attributeCode])) {
                continue;
            }

            $newValue = [];
            foreach ($values[$attributeCode] as $channel => $localeValues) {
                foreach ($localeValues as $locale => $data) {
                    if ($this->isMetricFilled($data)) {
                        $newValue[$channel][$locale] = $data;
                    }
                }
            }
            if (!empty($newValue)) {
                $values[$attributeCode] = $newValue;
            } else {
                unset($values[$attributeCode]);
            }
        }

        return $values;
    }

    private function isMetricFilled($data): bool
    {
        if (null === $data) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        return isset($data['unit']) && isset($data['amount']);
    }

    private function findMetricAttributesCodes(): array
    {
        $sql = "SELECT code FROM pim_catalog_attribute WHERE attribute_type = 'pim_catalog_metric'";

        return $this->connection->executeQuery($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function cleanPriceCollectionValues(array $values, array $priceCollectionAttributesCodes): array
    {
        foreach ($priceCollectionAttributesCodes as $attributeCode) {
            if (!isset($values[$attributeCode])) {
                continue;
            }

            $newValue = [];
            foreach ($values[$attributeCode] as $channel => $localeValues) {
                foreach ($localeValues as $locale => $data) {
                    if (!is_array($data)) {
                        continue;
                    }
                    foreach ($data as $item) {
                        if ($this->isPriceFilled($item)) {
                            $newValue[$channel][$locale][] = $item;
                        }
                    }
                }
            }
            if (!empty($newValue)) {
                $values[$attributeCode] = $newValue;
            } else {
                unset($values[$attributeCode]);
            }
        }

        return $values;
    }

    private function isPriceFilled($data): bool
    {
        if (null === $data) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        return isset($data['amount']);
    }

    private function findPriceCollectionAttributesCodes(): array
    {
        $sql = "SELECT code FROM pim_catalog_attribute WHERE attribute_type = 'pim_catalog_price_collection'";

        return $this->connection->executeQuery($sql)->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getAllProducts(): \Generator
    {
        $lastId = null;

        while (true) {
            $sql = sprintf(
                "SELECT identifier, raw_values FROM pim_catalog_product %s ORDER BY identifier LIMIT %d",
                $lastId !== null ? sprintf('WHERE identifier > "%s"', $lastId) : '',
                self::MYSQL_BATCH_SIZE
            );

            $rows = $this->connection->executeQuery($sql)->fetchAll();

            if (count($rows) === 0) {
                break;
            }

            foreach ($rows as $row) {
                yield $row;
                $lastId = $row['identifier'];
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
