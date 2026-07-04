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

use AG\PSModuleUtils\Installer\OrderState\OrderStateDefinition;
use AG\PSModuleUtils\Installer\OrderState\OrderStateGatewayInterface;
use AG\PSModuleUtils\Installer\OrderState\PrestaShopOrderStateGateway;
use Monolog\Logger;

/**
 * Installs order states idempotently. Depends only on OrderStateGatewayInterface, so its logic is
 * unit-testable; PrestaShop persistence lives in the injected gateway.
 *
 * Expected order-state shape (e.g. from install/defaults.yml key "orderStatuses"):
 *   [
 *     'configKey' => 'MYMODULE_STATE_ID',           // required; global config key holding the id
 *     'names'     => ['en' => 'Paid', 'fr' => 'Payé'],
 *     'logo'      => 'icon.gif',                      // optional; file in views/img/icons/
 *     'color' => '#01B887', 'send_email' => 0, ...    // any OrderState field (hydrated)
 *   ]
 */
class OrderStateManager
{
    private ?Logger $logger = null;

    public function __construct(private readonly OrderStateGatewayInterface $gateway)
    {
    }

    /**
     * Convenience factory wiring the default PrestaShop adapter.
     */
    public static function create(): self
    {
        return new self(new PrestaShopOrderStateGateway());
    }

    /**
     * @param array<int, array<string, mixed>> $orderStates
     */
    public function installOrderStates(array $orderStates, string $moduleName): void
    {
        foreach ($orderStates as $orderState) {
            try {
                $this->createOrderState($orderState, $moduleName);
            } catch (\Throwable $e) {
                $this->logger?->error(sprintf(
                    'Failed to install order state %s: %s',
                    $orderState['configKey'] ?? '(unknown)',
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * @param array<string, mixed> $moduleOrderState
     */
    public function createOrderState(array $moduleOrderState, string $moduleName): void
    {
        if ($this->gateway->exists($moduleOrderState['configKey'])) {
            $this->logger?->info(sprintf('Order status %s already exists', $moduleOrderState['configKey']));

            return;
        }

        $this->logger?->info(sprintf('Install order status %s', $moduleOrderState['configKey']));

        $this->gateway->create(new OrderStateDefinition(
            configKey: $moduleOrderState['configKey'],
            moduleName: $moduleName,
            names: $moduleOrderState['names'] ?? [],
            logo: $moduleOrderState['logo'] ?? null,
            attributes: $moduleOrderState
        ));
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
}
