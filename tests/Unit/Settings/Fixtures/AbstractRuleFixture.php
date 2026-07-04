<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

/**
 * Test fixture — common base for a POLYMORPHIC collection. The #[DiscriminatorMap] tells the
 * serializer which concrete subclass to instantiate from the `type` property of each element.
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'percentage' => PercentageRuleFixture::class,
    'fixed' => FixedRuleFixture::class,
])]
abstract class AbstractRuleFixture
{
    public string $label = '';
}
