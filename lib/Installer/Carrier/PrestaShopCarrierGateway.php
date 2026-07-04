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

use AG\PSModuleUtils\Tools;
use Carrier;
use Configuration;
use Context;
use Language;
use Validate;

/**
 * The only PrestaShop-touching carrier adapter (integration-tested, not unit-tested).
 *
 * Scope: creates the Carrier, its per-language delays, the logo images and the config key holding
 * its id. Groups, zones and price ranges remain the module's responsibility (business-specific).
 */
final class PrestaShopCarrierGateway implements CarrierGatewayInterface
{
    public function existsForModule(string $configKey, string $moduleName): bool
    {
        $idCarrier = Configuration::getGlobalValue($configKey);
        $carrier = Carrier::getCarrierByReference((int) $idCarrier);

        return false !== $carrier
            && Validate::isLoadedObject($carrier)
            && $carrier->external_module_name === $moduleName;
    }

    /**
     * @throws \PrestaShopException
     */
    public function create(CarrierDefinition $carrier): void
    {
        $psCarrier = new Carrier();
        $psCarrier->hydrate($carrier->attributes);

        $psCarrier->delay = [];
        foreach (Language::getLanguages(false) as $lang) {
            $psCarrier->delay[(int) $lang['id_lang']] = $carrier->delays[$lang['iso_code']] ?? ($carrier->delays['en'] ?? '');
        }

        if (!$psCarrier->save()) {
            throw new \RuntimeException(sprintf('Cannot create carrier %s', $carrier->configKey));
        }

        $logoPath = _PS_MODULE_DIR_ . $carrier->moduleName . '/views/img/carrier_icon_17.png';
        Tools::copy($logoPath, _PS_SHIP_IMG_DIR_ . (int) $psCarrier->id . '.jpg');
        Tools::copy(
            $logoPath,
            _PS_TMP_IMG_DIR_ . 'carrier_mini_' . (int) $psCarrier->id . '_' . Context::getContext()->language->id . '.png'
        );

        Configuration::updateGlobalValue($carrier->configKey, (int) $psCarrier->id);
    }
}
