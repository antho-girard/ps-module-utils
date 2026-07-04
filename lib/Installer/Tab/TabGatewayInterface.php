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

namespace AG\PSModuleUtils\Installer\Tab;

/**
 * Port for back-office tab persistence. Keeps TabManager free of PrestaShop's Tab ObjectModel
 * (and of the deprecated Tab::getIdFromClassName), so its logic stays unit-testable. The sole
 * adapter (PrestaShopTabGateway) is the only PrestaShop-touching piece, covered by integration.
 */
interface TabGatewayInterface
{
    /**
     * @return int|null the tab id, or null if no tab has this class name
     */
    public function findIdByClassName(string $className): ?int;

    /**
     * @throws \Throwable when the tab cannot be persisted (TabManager::installTabs() catches it
     *                    so one failing menu does not abort the install)
     */
    public function create(TabDefinition $tab): void;

    public function delete(string $className): void;
}
