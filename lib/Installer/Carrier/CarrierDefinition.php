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

namespace AG\PSModuleUtils\Installer\Carrier;

/**
 * Immutable, framework-agnostic description of a carrier to create. Built by CarrierManager
 * (unit-testable) and consumed by a CarrierGatewayInterface adapter.
 */
final class CarrierDefinition
{
    /**
     * @param array<string, string> $delays     iso code => delay label (fallback to 'en' then '')
     * @param array<string, mixed>  $attributes raw Carrier fields to hydrate (name, active,
     *                                           shipping_external, is_module, range_behavior, …);
     *                                           non-fields are ignored by ObjectModel::hydrate()
     */
    public function __construct(
        public readonly string $configKey,
        public readonly string $moduleName,
        public readonly array $delays,
        public readonly array $attributes = []
    ) {
    }
}
