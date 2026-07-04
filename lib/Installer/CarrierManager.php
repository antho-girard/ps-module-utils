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

use AG\PSModuleUtils\Installer\Carrier\CarrierDefinition;
use AG\PSModuleUtils\Installer\Carrier\CarrierGatewayInterface;
use AG\PSModuleUtils\Installer\Carrier\PrestaShopCarrierGateway;
use Monolog\Logger;

/**
 * Installs carriers idempotently. Depends only on CarrierGatewayInterface, so its logic is
 * unit-testable; PrestaShop persistence lives in the injected gateway.
 *
 * Scope: the carrier, its delays, logo and config key. Groups/zones/price ranges are the module's
 * responsibility (business-specific).
 *
 * Expected carrier shape (e.g. from install/defaults.yml key "carriers"):
 *   [
 *     'configKey' => 'MYMODULE_CARRIER_ID',          // required; global config key holding the id
 *     'delays'    => ['en' => 'Delivery', 'fr' => 'Livraison'],
 *     'name' => 'My carrier', 'is_module' => true, 'shipping_external' => true, ...  // Carrier fields
 *   ]
 */
class CarrierManager
{
    private ?Logger $logger = null;

    public function __construct(private readonly CarrierGatewayInterface $gateway)
    {
    }

    /**
     * Convenience factory wiring the default PrestaShop adapter.
     */
    public static function create(): self
    {
        return new self(new PrestaShopCarrierGateway());
    }

    /**
     * @param array<int, array<string, mixed>> $carriers
     */
    public function installCarriers(array $carriers, string $moduleName): void
    {
        foreach ($carriers as $carrier) {
            try {
                $this->createCarrier($carrier, $moduleName);
            } catch (\Throwable $e) {
                $this->logger?->error(sprintf(
                    'Failed to install carrier %s: %s',
                    $carrier['configKey'] ?? '(unknown)',
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * @param array<string, mixed> $moduleCarrier
     */
    public function createCarrier(array $moduleCarrier, string $moduleName): void
    {
        if ($this->gateway->existsForModule($moduleCarrier['configKey'], $moduleName)) {
            $this->logger?->info(sprintf('Carrier %s already exists', $moduleCarrier['configKey']));

            return;
        }

        $this->logger?->info(sprintf('Install carrier %s', $moduleCarrier['configKey']));

        $this->gateway->create(new CarrierDefinition(
            configKey: $moduleCarrier['configKey'],
            moduleName: $moduleName,
            delays: $moduleCarrier['delays'] ?? [],
            attributes: $moduleCarrier
        ));
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }
}
