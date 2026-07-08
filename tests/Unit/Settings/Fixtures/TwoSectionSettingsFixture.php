<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

use AG\PSModuleUtils\Settings\AbstractSettings;
use AG\PSModuleUtils\Settings\Attribute\ConfigKey;

/**
 * Test fixture — a settings root carrying TWO #[ConfigKey] sections, to assert that
 * persistProperty() writes a single section and leaves the other untouched.
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
class TwoSectionSettingsFixture extends AbstractSettings
{
    #[ConfigKey('TWO_SECTION_ACCOUNT')]
    public AccountSettingsFixture $account;

    #[ConfigKey('TWO_SECTION_CATALOG')]
    public CatalogSettingsFixture $catalog;

    public function __construct()
    {
        $this->account = new AccountSettingsFixture();
        $this->catalog = new CatalogSettingsFixture();
    }

    public function postLoading(): static
    {
        return $this;
    }
}
