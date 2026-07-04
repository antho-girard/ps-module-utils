<?php

namespace Tests\AG\PSModuleUtils\Installer;

use AG\PSModuleUtils\Installer\TabManager;
use PHPUnit\Framework\TestCase;
use Tests\AG\PSModuleUtils\Installer\Fixtures\InMemoryTabGateway;

/**
 * Unit tests for TabManager's logic, using an in-memory gateway (no PrestaShop). The PrestaShop
 * Tab persistence is covered separately by integration (PrestaShopTabGateway).
 *
 * @package Tests\AG\PSModuleUtils\Installer
 */
class TabManagerTest extends TestCase
{
    public function testCreatesTabMappingAllFields(): void
    {
        $gateway = new InMemoryTabGateway();
        $gateway->existing['IMPROVE'] = 5;

        (new TabManager($gateway))->createTab([
            'className' => 'AdminMyModuleConfigure',
            'routeName' => 'admin_mymodule_configure',
            'parentClassName' => 'IMPROVE',
            'visible' => false,
            'icon' => 'settings',
            'wording' => 'Configure',
            'wordingDomain' => 'Modules.Mymodule.Admin',
            'names' => ['en' => 'Configure', 'fr' => 'Configurer'],
        ], 'mymodule');

        $this->assertCount(1, $gateway->created);
        $tab = $gateway->created[0];
        $this->assertSame('AdminMyModuleConfigure', $tab->className);
        $this->assertSame('mymodule', $tab->moduleName);
        $this->assertSame('admin_mymodule_configure', $tab->routeName);
        $this->assertSame(5, $tab->parentId);
        $this->assertFalse($tab->active);
        $this->assertSame('settings', $tab->icon);
        $this->assertSame('Configure', $tab->wording);
        $this->assertSame('Modules.Mymodule.Admin', $tab->wordingDomain);
        $this->assertSame(['en' => 'Configure', 'fr' => 'Configurer'], $tab->names);
    }

    public function testSkipsWhenTabAlreadyExists(): void
    {
        $gateway = new InMemoryTabGateway();
        $gateway->existing['AdminMyModuleConfigure'] = 42;

        (new TabManager($gateway))->createTab(['className' => 'AdminMyModuleConfigure'], 'mymodule');

        $this->assertCount(0, $gateway->created);
    }

    public function testAppliesDefaultsVisibleTrueAndNoParent(): void
    {
        $gateway = new InMemoryTabGateway();

        (new TabManager($gateway))->createTab(['className' => 'AdminMyModule'], 'mymodule');

        $tab = $gateway->created[0];
        $this->assertTrue($tab->active);
        $this->assertNull($tab->parentId);
        $this->assertNull($tab->routeName);
        $this->assertSame([], $tab->names);
    }

    public function testInstallTabsIteratesEach(): void
    {
        $gateway = new InMemoryTabGateway();

        (new TabManager($gateway))->installTabs([
            ['className' => 'AdminA'],
            ['className' => 'AdminB'],
        ], 'mymodule');

        $this->assertCount(2, $gateway->created);
    }

    public function testInstallTabsIgnoresFailureAndContinues(): void
    {
        $gateway = new InMemoryTabGateway();
        $gateway->throwOnCreate = ['AdminBad'];

        // No logger set: the failure is swallowed (logged only when a logger is present).
        (new TabManager($gateway))->installTabs([
            ['className' => 'AdminBad'],
            ['className' => 'AdminGood'],
        ], 'mymodule');

        // AdminBad failed but did not abort; AdminGood was still created.
        $this->assertCount(1, $gateway->created);
        $this->assertSame('AdminGood', $gateway->created[0]->className);
    }

    public function testUninstallTabDeletesWhenPresentOnly(): void
    {
        $gateway = new InMemoryTabGateway();
        $gateway->existing['AdminA'] = 1;
        $manager = new TabManager($gateway);

        $manager->uninstallTab('AdminA');
        $manager->uninstallTab('AdminAbsent');

        $this->assertSame(['AdminA'], $gateway->deleted);
    }
}
