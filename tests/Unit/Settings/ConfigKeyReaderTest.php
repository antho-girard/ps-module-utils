<?php

namespace Tests\AG\PSModuleUtils\Settings;

use AG\PSModuleUtils\Settings\Attribute\ConfigKey;
use AG\PSModuleUtils\Settings\ConfigKeyReader;
use PHPUnit\Framework\TestCase;
use Tests\AG\PSModuleUtils\Settings\Fixtures\DemoSettings;
use Tests\AG\PSModuleUtils\Settings\Fixtures\ValidatedAccountFixture;

/**
 * @package Tests\AG\PSModuleUtils\Settings
 */
class ConfigKeyReaderTest extends TestCase
{
    /**
     * Reads the #[ConfigKey] property into a binding (property, key, type); ignores others.
     *
     * @return void
     */
    public function testReadsConfigKeyBindings(): void
    {
        $bindings = (new ConfigKeyReader())->read(DemoSettings::class);

        $this->assertSame(
            [['property' => 'account', 'key' => 'DEMO_SETTINGS_ACCOUNT', 'type' => ValidatedAccountFixture::class]],
            $bindings
        );
    }

    /**
     * A class without any #[ConfigKey] yields an empty binding list.
     *
     * @return void
     */
    public function testReturnsEmptyWhenNoConfigKey(): void
    {
        $this->assertSame([], (new ConfigKeyReader())->read(ValidatedAccountFixture::class));
    }

    /**
     * A #[ConfigKey] on a scalar-typed property is a programming error and throws.
     *
     * @return void
     */
    public function testThrowsWhenConfigKeyOnBuiltinType(): void
    {
        $subject = new class {
            #[ConfigKey('BAD')]
            public string $notAnObject = '';
        };

        $this->expectException(\LogicException::class);

        (new ConfigKeyReader())->read($subject::class);
    }
}
