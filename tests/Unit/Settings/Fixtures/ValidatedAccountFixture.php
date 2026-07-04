<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Test fixture — a settings sub-object carrying validation constraints as attributes.
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
class ValidatedAccountFixture
{
    #[Assert\NotBlank(message: 'API key is required')]
    public string $apiKey = '';

    #[Assert\Choice(choices: ['live', 'test'], message: 'Invalid mode')]
    public string $mode = 'test';
}
