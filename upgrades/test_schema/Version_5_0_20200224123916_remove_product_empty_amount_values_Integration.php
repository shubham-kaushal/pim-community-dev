<?php

namespace Pim\Upgrade\Schema\Tests;

use Akeneo\Test\Integration\TestCase;
use Doctrine\DBAL\Connection;

/**
 * This class will be removed after 4.0 version
 */
class Version_5_0_20200224123916_remove_product_empty_amount_values_Integration extends TestCase
{
    use ExecuteMigrationTrait;

    protected function getConfiguration()
    {
        return $this->catalog->useTechnicalCatalog();
    }

    public function testItRemovesAllEmptyValues()
    {
        $this->getConnection()->executeQuery('DELETE FROM pim_catalog_product');
        $sql = <<<SQL
INSERT INTO pim_catalog_product (is_enabled, identifier, raw_values, created, updated) VALUES
    (1, 'product1', '{"a_metric": {"<all_channels>": {"<all_locales>": null}}}', NOW(), NOW()),
    (1, 'product2', '{"a_metric": {"<all_channels>": {"<all_locales>": {}}}}', NOW(), NOW()),
    (1, 'product3', '{"a_metric": {"<all_channels>": {"<all_locales>": {"unit": null, "amount": null}}}}', NOW(), NOW()),
    (1, 'product4', '{"a_metric": {"<all_channels>": {"<all_locales>": {"unit": "WATT", "amount": 5000}}}}', NOW(), NOW()),
    (1, 'product5', '{"a_metric": {"<all_channels>": {"fr_FR": {"unit": null, "amount": null}, "en_US": {"unit": "WATT", "amount": 5000}}}}', NOW(), NOW()),
    (1, 'product6', '{"a_metric": {"ecommerce": {"<all_locales>": {"unit": null, "amount": null}}, "mobile": {"<all_locales>": {"unit": "WATT", "amount": 5000}}}}', NOW(), NOW()),
    (1, 'product7', '{"a_price": {"<all_channels>": {"<all_locales>": null}}}', NOW(), NOW()),
    (1, 'product8', '{"a_price": {"<all_channels>": {"<all_locales>": []}}}', NOW(), NOW()),
    (1, 'product9', '{"a_price": {"<all_channels>": {"<all_locales>": [{"amount": null, "currency": "USD"}]}}}', NOW(), NOW()),
    (1, 'product10', '{"a_price": {"<all_channels>": {"<all_locales>": [{"amount": "1329.00", "currency": "USD"}]}}}', NOW(), NOW()),
    (1, 'product11', '{"a_price": {"<all_channels>": {"<all_locales>": [{"amount": "1329.00", "currency": "USD"}, {"amount": "1329.00", "currency": "EUR"}]}}}', NOW(), NOW()),
    (1, 'product12', '{"a_price": {"<all_channels>": {"<all_locales>": [{"amount": "1329.00", "currency": "USD"}, {"amount": null, "currency": "EUR"}]}}}', NOW(), NOW())
SQL;

        $this->getConnection()->executeQuery($sql);

        $this->reExecuteMigration($this->getMigrationLabel());

        $this->assertProductRawValuesEquals('product1', '{}');
        $this->assertProductRawValuesEquals('product2', '{}');
        $this->assertProductRawValuesEquals('product3', '{}');
        $this->assertProductRawValuesEquals('product4', '{"a_metric": {"<all_channels>": {"<all_locales>": {"unit": "WATT", "amount": 5000}}}}');
        $this->assertProductRawValuesEquals('product5', '{"a_metric": {"<all_channels>": {"en_US": {"unit": "WATT", "amount": 5000}}}}');
        $this->assertProductRawValuesEquals('product6', '{"a_metric": {"mobile": {"<all_locales>": {"unit": "WATT", "amount": 5000}}}}');
        $this->assertProductRawValuesEquals('product7', '{}');
        $this->assertProductRawValuesEquals('product8', '{}');
        $this->assertProductRawValuesEquals('product9', '{}');
        $this->assertProductRawValuesEquals('product10', '{"a_price": {"<all_channels>": {"<all_locales>": [{"amount": "1329.00", "currency": "USD"}]}}}');
        $this->assertProductRawValuesEquals('product11', '{"a_price": {"<all_channels>": {"<all_locales>": [{"amount": "1329.00", "currency": "USD"}, {"amount": "1329.00", "currency": "EUR"}]}}}');
        $this->assertProductRawValuesEquals('product12', '{"a_price": {"<all_channels>": {"<all_locales>": [{"amount": "1329.00", "currency": "USD"}]}}}');
    }

    private function getConnection(): Connection
    {
        return $this->get('database_connection');
    }

    private function assertProductRawValuesEquals(string $productIdentifier, string $expectedRawValues)
    {
        $result = $this->getConnection()->fetchArray(
            'SELECT raw_values FROM pim_catalog_product WHERE identifier=:identifier',
            ['identifier' => $productIdentifier]
        );
        $this->assertEquals($expectedRawValues, $result[0], sprintf('Raw values of product "%s" do not match with expected values', $productIdentifier));
    }

    private function getMigrationLabel(): string
    {
        $migration = (new \ReflectionClass($this))->getShortName();
        $migration = str_replace('_Integration', '', $migration);
        $migration = str_replace('Version', '', $migration);

        return $migration;
    }
}
