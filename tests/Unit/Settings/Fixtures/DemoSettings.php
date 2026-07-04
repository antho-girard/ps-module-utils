<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

use AG\PSModuleUtils\Settings\AbstractSettings;
use AG\PSModuleUtils\Settings\Attribute\ConfigKey;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Test fixture — a settings root: one #[ConfigKey] sub-object cascading validation,
 * plus an observable postLoading() to assert the loader runs it.
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
class DemoSettings extends AbstractSettings
{
    #[ConfigKey('DEMO_SETTINGS_ACCOUNT')]
    #[Assert\Valid]
    public ValidatedAccountFixture $account;

    /** @var bool Set by postLoading(), not persisted (no #[ConfigKey]). */
    public bool $postLoaded = false;

    public function __construct()
    {
        $this->account = new ValidatedAccountFixture();
    }

    public function postLoading(): static
    {
        $this->postLoaded = true;

        return $this;
    }
}
