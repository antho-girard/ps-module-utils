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

use Language;
use Tab;

/**
 * The only PrestaShop-touching tab adapter (integration-tested, not unit-tested).
 *
 * NOTE: Tab::getIdFromClassName() is @deprecated (since 1.7.1.0, superseded by
 * PrestaShopBundle\Entity\Repository\TabRepository::findOneIdByClassName()). It is kept HERE, and
 * only here, because the modern replacement needs the Doctrine EntityManager, which is not reliably
 * available at install time; it still works in PS9. When targeting PS10, swap this single method.
 */
final class PrestaShopTabGateway implements TabGatewayInterface
{
    public function findIdByClassName(string $className): ?int
    {
        $id = Tab::getIdFromClassName($className);

        return (false === $id || 0 === (int) $id) ? null : (int) $id;
    }

    /**
     * @throws \PrestaShopException
     */
    public function create(TabDefinition $tab): void
    {
        $psTab = new Tab();
        $psTab->class_name = $tab->className;
        $psTab->module = $tab->moduleName;
        $psTab->route_name = $tab->routeName;
        $psTab->id_parent = $tab->parentId ?? 0;
        $psTab->active = $tab->active;
        $psTab->icon = $tab->icon;

        if (null !== $tab->wording) {
            $psTab->wording = $tab->wording;
            $psTab->wording_domain = $tab->wordingDomain;
        }

        $psTab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $psTab->name[$lang['id_lang']] = $tab->names[$lang['iso_code']] ?? ($tab->names['en'] ?? $tab->className);
        }

        if (!$psTab->add()) {
            throw new \RuntimeException(sprintf('Cannot add tab %s', $tab->className));
        }
    }

    /**
     * @throws \PrestaShopException
     */
    public function delete(string $className): void
    {
        $id = $this->findIdByClassName($className);
        if (null === $id) {
            return;
        }
        (new Tab($id))->delete();
    }
}
