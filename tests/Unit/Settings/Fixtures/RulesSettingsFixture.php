<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

/**
 * Test fixture — a settings sub-object holding an ARRAY OF POLYMORPHIC OBJECTS
 * (different concrete classes sharing AbstractRuleFixture).
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
class RulesSettingsFixture
{
    /** @var AbstractRuleFixture[] */
    public array $rules = [];
}
