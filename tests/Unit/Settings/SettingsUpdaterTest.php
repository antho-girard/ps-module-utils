<?php

namespace Tests\AG\PSModuleUtils\Settings;

use AG\PSModuleUtils\Settings\Exception\SettingsValidationException;
use AG\PSModuleUtils\Settings\SettingsFactory;
use PHPUnit\Framework\TestCase;
use Tests\AG\PSModuleUtils\Settings\Fixtures\DemoSettings;
use Tests\AG\PSModuleUtils\Settings\Fixtures\InMemoryConfigurationStorage;

/**
 * @package Tests\AG\PSModuleUtils\Settings
 */
class SettingsUpdaterTest extends TestCase
{
    /**
     * save() persists valid settings as JSON under the #[ConfigKey] key.
     *
     * @return void
     */
    public function testSavePersistsValidSettings(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $updater = SettingsFactory::updater($storage);

        $settings = new DemoSettings();
        $settings->account->apiKey = 'k';
        $settings->account->mode = 'live';

        $updater->save($settings);

        $decoded = json_decode((string) $storage->get('DEMO_SETTINGS_ACCOUNT'), true);
        $this->assertSame(['apiKey' => 'k', 'mode' => 'live'], $decoded);
    }

    /**
     * save() throws with the violation list when constraints fail, and does not persist.
     *
     * @return void
     */
    public function testSaveThrowsAndDoesNotPersistWhenInvalid(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $updater = SettingsFactory::updater($storage);

        // apiKey empty -> NotBlank fails.
        $settings = new DemoSettings();

        try {
            $updater->save($settings);
            $this->fail('Expected SettingsValidationException');
        } catch (SettingsValidationException $e) {
            $this->assertGreaterThan(0, $e->getViolations()->count());
            $this->assertFalse($storage->has('DEMO_SETTINGS_ACCOUNT'), 'Invalid settings must not be persisted');
        }
    }

    /**
     * persist() writes without validating (caller already validated, e.g. Symfony Form).
     *
     * @return void
     */
    public function testPersistSkipsValidation(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $updater = SettingsFactory::updater($storage);

        // Invalid (empty apiKey) but persist() must not validate.
        $settings = new DemoSettings();

        $updater->persist($settings);

        $this->assertTrue($storage->has('DEMO_SETTINGS_ACCOUNT'));
    }

    /**
     * persistFromArray() denormalizes a raw array (e.g. YAML defaults) and persists without
     * validating — a blank required field is allowed at seeding time.
     *
     * @return void
     */
    public function testPersistFromArraySeedsWithoutValidation(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $updater = SettingsFactory::updater($storage);

        $updater->persistFromArray(DemoSettings::class, ['account' => ['apiKey' => '', 'mode' => 'live']]);

        $decoded = json_decode((string) $storage->get('DEMO_SETTINGS_ACCOUNT'), true);
        $this->assertSame(['apiKey' => '', 'mode' => 'live'], $decoded);
    }

    /**
     * saveFromArray() denormalizes then validates — an invalid array is rejected.
     *
     * @return void
     */
    public function testSaveFromArrayValidates(): void
    {
        $updater = SettingsFactory::updater(new InMemoryConfigurationStorage());

        $this->expectException(SettingsValidationException::class);
        $updater->saveFromArray(DemoSettings::class, ['account' => ['apiKey' => '', 'mode' => 'live']]);
    }
}
