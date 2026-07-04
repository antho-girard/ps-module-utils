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

namespace AG\PSModuleUtils\Installer\OrderState;

use AG\PSModuleUtils\Tools;
use Configuration;
use Language;
use OrderState;
use Validate;

/**
 * The only PrestaShop-touching order-state adapter (integration-tested, not unit-tested).
 */
final class PrestaShopOrderStateGateway implements OrderStateGatewayInterface
{
    public function exists(string $configKey): bool
    {
        $orderState = new OrderState((int) Configuration::getGlobalValue($configKey));

        return Validate::isLoadedObject($orderState) && !$orderState->deleted;
    }

    /**
     * @throws \PrestaShopException
     */
    public function create(OrderStateDefinition $orderState): void
    {
        $state = new OrderState();
        $state->hydrate($orderState->attributes);
        $state->module_name = $orderState->moduleName;

        $state->name = [];
        foreach (Language::getLanguages(false) as $lang) {
            $state->name[(int) $lang['id_lang']] = $orderState->names[$lang['iso_code']] ?? ($orderState->names['en'] ?? '');
        }

        if (!$state->save()) {
            throw new \RuntimeException(sprintf('Cannot create order state %s', $orderState->configKey));
        }

        if (null !== $orderState->logo) {
            $source = realpath(_PS_MODULE_DIR_ . $orderState->moduleName . '/views/img/icons/' . $orderState->logo);
            if (false !== $source) {
                Tools::copy($source, _PS_ROOT_DIR_ . '/img/os/' . (int) $state->id . '.gif');
            }
        }

        Configuration::updateGlobalValue($orderState->configKey, (int) $state->id);
    }
}
