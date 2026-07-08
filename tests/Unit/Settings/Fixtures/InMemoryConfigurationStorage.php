<?php

namespace Tests\AG\PSModuleUtils\Settings\Fixtures;

use AG\PSModuleUtils\Settings\Storage\ConfigurationStorageInterface;

/**
 * In-memory test double for the configuration storage port — lets the settings core be
 * unit-tested with no PrestaShop dependency. Shop scope is ignored (single flat store).
 *
 * @package Tests\AG\PSModuleUtils\Settings\Fixtures
 */
final class InMemoryConfigurationStorage implements ConfigurationStorageInterface
{
    /** @var array<string, string> */
    private array $store = [];

    /** @var list<string> keys written through setGlobal() (for assertions) */
    public array $globalWrites = [];

    public function get(string $key, ?int $idShop = null, ?int $idShopGroup = null): ?string
    {
        return $this->store[$key] ?? null;
    }

    public function set(string $key, string $value, ?int $idShop = null, ?int $idShopGroup = null): void
    {
        $this->store[$key] = $value;
    }

    public function has(string $key, ?int $idShop = null, ?int $idShopGroup = null): bool
    {
        return isset($this->store[$key]);
    }

    public function setGlobal(string $key, string $value): void
    {
        $this->store[$key] = $value;
        $this->globalWrites[] = $key;
    }
}
