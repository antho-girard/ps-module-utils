<?php

namespace Tests\AG\PSModuleUtils\Settings;

use AG\PSModuleUtils\Settings\SettingsFactory;
use PHPUnit\Framework\TestCase;
use Tests\AG\PSModuleUtils\Settings\Fixtures\DemoSettings;
use Tests\AG\PSModuleUtils\Settings\Fixtures\InMemoryConfigurationStorage;

/**
 * @package Tests\AG\PSModuleUtils\Settings
 */
class SettingsLoaderTest extends TestCase
{
    /**
     * Loads each #[ConfigKey] sub-object from its stored JSON.
     *
     * @return void
     */
    public function testLoadsSubObjectFromStoredJson(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $storage->set('DEMO_SETTINGS_ACCOUNT', '{"apiKey":"stored","mode":"live"}');
        $loader = SettingsFactory::loader($storage);

        $settings = $loader->load(DemoSettings::class);

        $this->assertSame('stored', $settings->account->apiKey);
        $this->assertSame('live', $settings->account->mode);
    }

    /**
     * A missing key yields the sub-object's default values (fresh install).
     *
     * @return void
     */
    public function testMissingKeyYieldsDefaults(): void
    {
        $loader = SettingsFactory::loader(new InMemoryConfigurationStorage());

        $settings = $loader->load(DemoSettings::class);

        $this->assertSame('', $settings->account->apiKey);
        $this->assertSame('test', $settings->account->mode);
    }

    /**
     * postLoading() is invoked by the loader.
     *
     * @return void
     */
    public function testRunsPostLoading(): void
    {
        $loader = SettingsFactory::loader(new InMemoryConfigurationStorage());

        $settings = $loader->load(DemoSettings::class);

        $this->assertTrue($settings->postLoaded);
    }

    /**
     * normalize() turns a settings object into an array the caller can complete.
     *
     * @return void
     */
    public function testNormalizeReturnsArray(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $storage->set('DEMO_SETTINGS_ACCOUNT', '{"apiKey":"k","mode":"live"}');
        $loader = SettingsFactory::loader($storage);

        $data = $loader->normalize($loader->load(DemoSettings::class));

        $this->assertIsArray($data);
        $this->assertArrayHasKey('account', $data);
        $this->assertSame('live', $data['account']['mode']);
    }
}
