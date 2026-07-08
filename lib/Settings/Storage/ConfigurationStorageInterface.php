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

/**
 * Port for reading/writing raw JSON config values, keyed and shop-scoped.
 *
 * The settings core depends only on this interface (no PrestaShop type), which keeps it
 * unit-testable with an in-memory double. PrestaShopConfigurationStorage is the sole
 * adapter bridging it to the native ShopConfigurationInterface / ShopConstraint.
 *
 * Shop scope is expressed with primitive ids (kept framework-agnostic here); the adapter
 * maps them to a ShopConstraint. When both ids are null the operation targets the CURRENT
 * shop context (mono-shop, group or all-shops), mirroring PrestaShop's native
 * Configuration::updateValue — so per-shop configuration works automatically. Use setGlobal()
 * to force the all-shops (global) level regardless of the current context, e.g. to seed
 * install defaults that must act as the fallback for every shop.
 */
interface ConfigurationStorageInterface
{
    public function get(string $key, ?int $idShop = null, ?int $idShopGroup = null): ?string;

    public function set(string $key, string $value, ?int $idShop = null, ?int $idShopGroup = null): void;

    public function has(string $key, ?int $idShop = null, ?int $idShopGroup = null): bool;

    /**
     * Writes at the all-shops (global) level, whatever the current shop context. In multistore,
     * reads for a specific shop fall back to this global value when no per-shop override exists.
     */
    public function setGlobal(string $key, string $value): void;
}
