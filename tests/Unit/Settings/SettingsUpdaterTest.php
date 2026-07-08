<?php

namespace Tests\AG\PSModuleUtils\Settings;

use AG\PSModuleUtils\Settings\Exception\SettingsValidationException;
use AG\PSModuleUtils\Settings\SettingsFactory;
use PHPUnit\Framework\TestCase;
use Tests\AG\PSModuleUtils\Settings\Fixtures\DemoSettings;
use Tests\AG\PSModuleUtils\Settings\Fixtures\InMemoryConfigurationStorage;
use Tests\AG\PSModuleUtils\Settings\Fixtures\TwoSectionSettingsFixture;

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

    /**
     * persistGlobal() routes every #[ConfigKey] binding through the storage's global writer
     * (all-shops level), not the context-aware set().
     *
     * @return void
     */
    public function testPersistGlobalWritesAtGlobalLevel(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $updater = SettingsFactory::updater($storage);

        $updater->persistGlobal(new DemoSettings());

        $this->assertSame(['DEMO_SETTINGS_ACCOUNT'], $storage->globalWrites);
    }

    /**
     * persistFromArrayGlobal() seeds defaults globally (without validating) — the way an installer
     * seeds a fallback shared by every shop.
     *
     * @return void
     */
    public function testPersistFromArrayGlobalSeedsGlobally(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $updater = SettingsFactory::updater($storage);

        $updater->persistFromArrayGlobal(DemoSettings::class, ['account' => ['apiKey' => '', 'mode' => 'test']]);

        $this->assertContains('DEMO_SETTINGS_ACCOUNT', $storage->globalWrites);
        $decoded = json_decode((string) $storage->get('DEMO_SETTINGS_ACCOUNT'), true);
        $this->assertSame(['apiKey' => '', 'mode' => 'test'], $decoded);
    }

    /**
     * persistProperty() writes only the targeted section's key and leaves the other keys untouched
     * (so a multi-tab page saving one tab does not rewrite the others).
     *
     * @return void
     */
    public function testPersistPropertyWritesOnlyTheTargetedKey(): void
    {
        $storage = new InMemoryConfigurationStorage();
        $updater = SettingsFactory::updater($storage);

        $updater->persistProperty(new TwoSectionSettingsFixture(), 'account');

        $this->assertTrue($storage->has('TWO_SECTION_ACCOUNT'), 'targeted key must be written');
        $this->assertFalse($storage->has('TWO_SECTION_CATALOG'), 'other key must be left untouched');
    }

    /**
     * persistProperty() rejects a property that carries no #[ConfigKey], rather than silently
     * doing nothing (catches typos).
     *
     * @return void
     */
    public function testPersistPropertyThrowsOnUnknownProperty(): void
    {
        $updater = SettingsFactory::updater(new InMemoryConfigurationStorage());

        $this->expectException(\InvalidArgumentException::class);
        $updater->persistProperty(new TwoSectionSettingsFixture(), 'doesNotExist');
    }
}
