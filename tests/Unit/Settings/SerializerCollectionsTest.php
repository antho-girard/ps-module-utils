<?php

namespace Tests\AG\PSModuleUtils\Settings;

use AG\PSModuleUtils\Settings\SettingsFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;
use Tests\AG\PSModuleUtils\Settings\Fixtures\CatalogSettingsFixture;
use Tests\AG\PSModuleUtils\Settings\Fixtures\FixedRuleFixture;
use Tests\AG\PSModuleUtils\Settings\Fixtures\LineItemFixture;
use Tests\AG\PSModuleUtils\Settings\Fixtures\PercentageRuleFixture;
use Tests\AG\PSModuleUtils\Settings\Fixtures\RulesSettingsFixture;

/**
 * Proves the library's deterministic Serializer handles the shapes a real module config needs:
 * arrays of objects and polymorphic collections (#[DiscriminatorMap]).
 *
 * @package Tests\AG\PSModuleUtils\Settings
 */
class SerializerCollectionsTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = SettingsFactory::serializer();
    }

    public function testArrayOfObjectsRoundTrip(): void
    {
        $catalog = new CatalogSettingsFixture();
        $first = new LineItemFixture();
        $first->sku = 'X1';
        $first->qty = 2;
        $second = new LineItemFixture();
        $second->sku = 'Y2';
        $second->qty = 5;
        $catalog->items = [$first, $second];

        $json = $this->serializer->serialize($catalog, 'json');
        /** @var CatalogSettingsFixture $restored */
        $restored = $this->serializer->deserialize($json, CatalogSettingsFixture::class, 'json');

        $this->assertCount(2, $restored->items);
        $this->assertContainsOnlyInstancesOf(LineItemFixture::class, $restored->items);
        $this->assertSame('X1', $restored->items[0]->sku);
        $this->assertSame(5, $restored->items[1]->qty);
    }

    public function testPolymorphicCollectionDeserializesToConcreteClasses(): void
    {
        $json = '{"rules":['
            . '{"type":"percentage","label":"A","percent":10.0},'
            . '{"type":"fixed","label":"B","amount":500}'
            . ']}';

        /** @var RulesSettingsFixture $restored */
        $restored = $this->serializer->deserialize($json, RulesSettingsFixture::class, 'json');

        $this->assertInstanceOf(PercentageRuleFixture::class, $restored->rules[0]);
        $this->assertSame('A', $restored->rules[0]->label);
        $this->assertSame(10.0, $restored->rules[0]->percent);

        $this->assertInstanceOf(FixedRuleFixture::class, $restored->rules[1]);
        $this->assertSame('B', $restored->rules[1]->label);
        $this->assertSame(500, $restored->rules[1]->amount);
    }

    public function testPolymorphicCollectionSerializesTypeDiscriminator(): void
    {
        $rules = new RulesSettingsFixture();
        $rule = new PercentageRuleFixture();
        $rule->label = 'A';
        $rule->percent = 10.0;
        $rules->rules = [$rule];

        $decoded = json_decode($this->serializer->serialize($rules, 'json'), true);

        $this->assertSame('percentage', $decoded['rules'][0]['type']);
        $this->assertSame(10.0, $decoded['rules'][0]['percent']);
    }
}
