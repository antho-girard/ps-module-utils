<?php

namespace Tests\AG\PSModuleUtils\Installer\Fixtures;

use AG\PSModuleUtils\Installer\Carrier\CarrierDefinition;
use AG\PSModuleUtils\Installer\Carrier\CarrierGatewayInterface;

/**
 * In-memory carrier gateway for unit tests (no PrestaShop).
 *
 * @package Tests\AG\PSModuleUtils\Installer\Fixtures
 */
final class InMemoryCarrierGateway implements CarrierGatewayInterface
{
    /** @var array<string, bool> configKey => already exists for module */
    public array $existing = [];

    /** @var array<int, CarrierDefinition> */
    public array $created = [];

    /** @var array<int, string> config keys for which create() must throw */
    public array $throwOnCreate = [];

    public function existsForModule(string $configKey, string $moduleName): bool
    {
        return $this->existing[$configKey] ?? false;
    }

    public function create(CarrierDefinition $carrier): void
    {
        if (in_array($carrier->configKey, $this->throwOnCreate, true)) {
            throw new \RuntimeException(sprintf('Cannot create carrier %s', $carrier->configKey));
        }
        $this->created[] = $carrier;
        $this->existing[$carrier->configKey] = true;
    }
}
