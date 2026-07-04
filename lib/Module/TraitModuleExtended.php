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

namespace AG\PSModuleUtils\Module;

use AG\PSModuleUtils\Exception\ExceptionList;
use AG\PSModuleUtils\Installer\AbstractInstaller;

trait TraitModuleExtended
{
    public function installModule(AbstractInstaller $installer): bool
    {
        try {
            if (false === parent::install()) {
                $installer->getLogger()->error('parent::install() returns false');

                return false;
            }
            $installer->runInstall();
        } catch (ExceptionList $list) {
            $exceptions = $list->getExceptions();
            foreach ($exceptions as $e) {
                $installer->getLogger()->error(sprintf(
                    'Install Process: %s - File: %s - Line: %s - Trace: %s',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                ));

                return false;
            }
        } catch (\Exception $e) {
            $installer->getLogger()->error(sprintf('Install Process: %s - File: %s - Line: %s - Trace: %s',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));

            return false;
        }

        return true;
    }

    public function uninstallModule(AbstractInstaller $installer): bool
    {
        try {
            $installer->runUninstall();
        } catch (\Exception $e) {
            $installer->getLogger()->error(sprintf(
                'Uninstall Process: %s - File: %s - Line: %s - Trace: %s',
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));

            return false;
        }

        return true;
    }

    public function displayConfigurationPage(string $controllerName): void
    {
        \Tools::redirectAdmin(\Context::getContext()->link->getAdminLink($controllerName));
    }

    /**
     * Clears the Symfony cache after the current request, delegating to the native
     * Tools::clearSf2Cache() (which also dispatches the actionClearSf2Cache hook).
     */
    public function removeSymfonyCache(?string $env = null): void
    {
        register_shutdown_function(static function () use ($env): void {
            \Tools::clearSf2Cache($env);
        });
    }
}
