<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

/**
 * Test fixture — a representative flat settings sub-object (scalars only),
 * used to characterize the JSON serialization contract the v4 rewrite must preserve.
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
class AccountSettingsFixture
{
    /** @var string */
    public string $apiKey = '';

    /** @var string */
    public string $mode = 'test';

    /** @var bool */
    public bool $enabled = false;

    /** @var int */
    public int $timeout = 30;

    /** @var float */
    public float $rate = 1.5;
}
