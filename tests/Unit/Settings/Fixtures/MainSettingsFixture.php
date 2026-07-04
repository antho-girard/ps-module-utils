<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

/**
 * Test fixture — a settings sub-object with a nested object and a scalar list,
 * used to characterize nested JSON (de)serialization the v4 rewrite must preserve.
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
class MainSettingsFixture
{
    /** @var string */
    public string $label = '';

    /** @var string[] */
    public array $countries = [];

    /** @var AccountSettingsFixture */
    public AccountSettingsFixture $account;

    public function __construct()
    {
        $this->account = new AccountSettingsFixture();
    }
}
