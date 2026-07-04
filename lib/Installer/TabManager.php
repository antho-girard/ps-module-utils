<?php
/*
 * MIT License
 *
 * Copyright (c) 2022 Anthony Girard
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace AG\PSModuleUtils\Installer;

use AG\PSModuleUtils\Installer\Tab\PrestaShopTabGateway;
use AG\PSModuleUtils\Installer\Tab\TabDefinition;
use AG\PSModuleUtils\Installer\Tab\TabGatewayInterface;
use Monolog\Logger;

/**
 * Installs/removes back-office tabs (menu entries and, above all, the permission entries needed by
 * #[AdminSecurity]). Depends only on TabGatewayInterface, so its logic is unit-testable; the actual
 * PrestaShop Tab persistence lives in the injected gateway.
 *
 * A tab bound to a Symfony admin controller MUST carry its `routeName`; `className` must equal the
 * route's `_legacy_controller` and the value checked by `#[AdminSecurity]`. Use `visible => false`
 * for a permission-only, menu-less tab.
 *
 * Expected tab shape (e.g. from install/defaults.yml key "menus"):
 *   [
 *     'className'       => 'AdminMyModuleConfigure',   // required; = route _legacy_controller
 *     'routeName'       => 'admin_mymodule_configure', // required for a Symfony controller tab
 *     'parentClassName' => 'IMPROVE',                  // optional; menu section
 *     'visible'         => false,                       // optional (default true)
 *     'icon'            => 'settings',                  // optional
 *     'wording'         => 'Configure',                 // optional; translation key
 *     'wordingDomain'   => 'Modules.Mymodule.Admin',    // optional; translation domain
 *     'names'           => ['en' => 'Configure', 'fr' => 'Configurer'], // optional per-lang name
 *   ]
 */
class TabManager
{
    private ?Logger $logger = null;

    public function __construct(private readonly TabGatewayInterface $gateway)
    {
    }

    /**
     * Convenience factory wiring the default PrestaShop adapter.
     */
    public static function create(): self
    {
        return new self(new PrestaShopTabGateway());
    }

    /**
     * @param array<int, array<string, mixed>> $tabs
     */
    public function installTabs(array $tabs, string $moduleName): void
    {
        foreach ($tabs as $tab) {
            try {
                $this->createTab($tab, $moduleName);
            } catch (\Throwable $e) {
                // A single menu failure must not abort the whole install: log and carry on.
                $this->logger?->error(sprintf(
                    'Failed to install tab %s: %s',
                    $tab['className'] ?? '(unknown)',
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * @param array<string, mixed> $moduleTab
     */
    public function createTab(array $moduleTab, string $moduleName): void
    {
        if (null !== $this->gateway->findIdByClassName($moduleTab['className'])) {
            return;
        }

        $this->logger?->info(sprintf('Install tab %s', $moduleTab['className']));

        $parentId = null;
        if (!empty($moduleTab['parentClassName'])) {
            $parentId = $this->gateway->findIdByClassName($moduleTab['parentClassName']);
        }

        $this->gateway->create(new TabDefinition(
            className: $moduleTab['className'],
            moduleName: $moduleName,
            parentId: $parentId,
            routeName: $moduleTab['routeName'] ?? null,
            active: (bool) ($moduleTab['visible'] ?? true),
            icon: $moduleTab['icon'] ?? null,
            wording: $moduleTab['wording'] ?? null,
            wordingDomain: $moduleTab['wordingDomain'] ?? null,
            names: $moduleTab['names'] ?? []
        ));
    }

    public function uninstallTab(string $className): void
    {
        if (null === $this->gateway->findIdByClassName($className)) {
            return;
        }
        $this->logger?->info(sprintf('Uninstall tab %s', $className));
        $this->gateway->delete($className);
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
}
