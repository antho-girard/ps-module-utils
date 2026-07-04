<?php

namespace Tests\AG\PSModuleUtils\Installer\Fixtures;

use AG\PSModuleUtils\Installer\OrderState\OrderStateDefinition;
use AG\PSModuleUtils\Installer\OrderState\OrderStateGatewayInterface;

/**
 * In-memory order-state gateway for unit tests (no PrestaShop).
 *
 * @package Tests\AG\PSModuleUtils\Installer\Fixtures
 */
final class InMemoryOrderStateGateway implements OrderStateGatewayInterface
{
    /** @var array<string, bool> configKey => already exists */
    public array $existing = [];

    /** @var array<int, OrderStateDefinition> */
    public array $created = [];

    /** @var array<int, string> config keys for which create() must throw */
    public array $throwOnCreate = [];

    public function exists(string $configKey): bool
    {
        return $this->existing[$configKey] ?? false;
    }

    public function create(OrderStateDefinition $orderState): void
    {
        if (in_array($orderState->configKey, $this->throwOnCreate, true)) {
            throw new \RuntimeException(sprintf('Cannot create order state %s', $orderState->configKey));
        }
        $this->created[] = $orderState;
        $this->existing[$orderState->configKey] = true;
    }
}
