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

namespace AG\PSModuleUtils\Settings\Storage;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;

/**
 * The only bridge between the settings core and PrestaShop's native configuration store.
 *
 * Delegates to ShopConfigurationInterface and maps primitive shop ids to a ShopConstraint.
 * Covered by integration tests (real store), not by unit tests.
 */
final class PrestaShopConfigurationStorage implements ConfigurationStorageInterface
{
    public function __construct(private readonly ShopConfigurationInterface $configuration)
    {
    }

    /**
     * Builds a storage outside the Symfony container (install scripts, upgrades, CLI) where the
     * ShopConfigurationInterface service is not publicly gettable. In a Symfony controller, inject
     * the storage via the container instead (see config/services.php).
     */
    public static function create(): self
    {
        return new self(new Configuration());
    }

    public function get(string $key, ?int $idShop = null, ?int $idShopGroup = null): ?string
    {
        $value = $this->configuration->get($key, null, $this->constraint($idShop, $idShopGroup));

        return null === $value ? null : (string) $value;
    }

    public function set(string $key, string $value, ?int $idShop = null, ?int $idShopGroup = null): void
    {
        $this->configuration->set($key, $value, $this->constraint($idShop, $idShopGroup));
    }

    public function has(string $key, ?int $idShop = null, ?int $idShopGroup = null): bool
    {
        return $this->configuration->has($key, $this->constraint($idShop, $idShopGroup));
    }

    public function setGlobal(string $key, string $value): void
    {
        $this->configuration->set($key, $value, ShopConstraint::allShops());
    }

    private function constraint(?int $idShop, ?int $idShopGroup): ShopConstraint
    {
        return match (true) {
            null !== $idShop => ShopConstraint::shop($idShop),
            null !== $idShopGroup => ShopConstraint::shopGroup($idShopGroup),
            default => $this->currentContextConstraint(),
        };
    }

    /**
     * With no explicit shop/group, follow the CURRENT shop context (like the native
     * Configuration::updateValue) rather than forcing the global "all shops" level: a mono-shop
     * context reads/writes that shop, a group context that group, and only the all-shops context
     * (or a CLI/install without a precise context) targets the global level.
     */
    private function currentContextConstraint(): ShopConstraint
    {
        return match (\Shop::getContext()) {
            \Shop::CONTEXT_SHOP => ShopConstraint::shop((int) \Shop::getContextShopID()),
            \Shop::CONTEXT_GROUP => ShopConstraint::shopGroup((int) \Shop::getContextShopGroupID()),
            default => ShopConstraint::allShops(),
        };
    }
}
