<?php

namespace Tests\AG\PSModuleUtils\Installer\Fixtures;

use AG\PSModuleUtils\Installer\Tab\TabDefinition;
use AG\PSModuleUtils\Installer\Tab\TabGatewayInterface;

/**
 * In-memory tab gateway for unit tests — records created/deleted tabs, no PrestaShop dependency.
 *
 * @package Tests\AG\PSModuleUtils\Installer\Fixtures
 */
final class InMemoryTabGateway implements TabGatewayInterface
{
    /** @var array<string, int> className => id (preset existing tabs) */
    public array $existing = [];

    /** @var array<int, TabDefinition> */
    public array $created = [];

    /** @var array<int, string> */
    public array $deleted = [];

    /** @var array<int, string> class names for which create() must throw (simulates a PS failure) */
    public array $throwOnCreate = [];

    public function findIdByClassName(string $className): ?int
    {
        return $this->existing[$className] ?? null;
    }

    public function create(TabDefinition $tab): void
    {
        if (in_array($tab->className, $this->throwOnCreate, true)) {
            throw new \RuntimeException(sprintf('Cannot add tab %s', $tab->className));
        }
        $this->created[] = $tab;
        $this->existing[$tab->className] = count($this->existing) + 1;
    }

    public function delete(string $className): void
    {
        $this->deleted[] = $className;
        unset($this->existing[$className]);
    }
}
