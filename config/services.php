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

/*
 * Ready-to-import service wiring for a PrestaShop 9 module's Symfony (back-office) container.
 * Import it from the module's own config/services.php:
 *
 *     $container->import('../../vendor/anthogirard/ps-module-utils/config/services.php');
 *
 * The settings services are then available for constructor injection in modern admin controllers.
 * Out-of-kernel contexts (upgrade scripts, CLI) do not use this file — they build the services
 * through SettingsFactory instead.
 */

use AG\PSModuleUtils\Settings\ConfigKeyReader;
use AG\PSModuleUtils\Settings\SettingsFactory;
use AG\PSModuleUtils\Settings\SettingsLoader;
use AG\PSModuleUtils\Settings\SettingsUpdater;
use AG\PSModuleUtils\Settings\Storage\PrestaShopConfigurationStorage;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Reflection reader for #[ConfigKey] bindings.
    $services->set(ConfigKeyReader::class);

    // The library builds its OWN deterministic serializer: the core's `serializer` service is
    // decorated for the Admin API (API Platform) and must not be inherited here.
    $services->set('ag_psmoduleutils.settings.serializer', Serializer::class)
        ->factory([SettingsFactory::class, 'serializer']);

    // Sole bridge to PrestaShop's configuration store; the native interface is autowirable.
    $services->set(PrestaShopConfigurationStorage::class)
        ->args([service(ShopConfigurationInterface::class)]);

    $services->set(SettingsLoader::class)
        ->args([
            service('ag_psmoduleutils.settings.serializer'),
            service(PrestaShopConfigurationStorage::class),
            service(ConfigKeyReader::class),
        ]);

    // The Validator IS safe to reuse from the core container (used by Symfony Forms).
    $services->set(SettingsUpdater::class)
        ->args([
            service('ag_psmoduleutils.settings.serializer'),
            service(ValidatorInterface::class),
            service(PrestaShopConfigurationStorage::class),
            service(ConfigKeyReader::class),
        ]);
};
