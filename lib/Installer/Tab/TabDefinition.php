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
 * Immutable, framework-agnostic description of a back-office tab to create.
 * Built by TabManager (unit-testable) and consumed by a TabGatewayInterface adapter.
 */
final class TabDefinition
{
    /**
     * @param array<string, string> $names iso code => name (fallback to 'en' then class name)
     */
    public function __construct(
        public readonly string $className,
        public readonly string $moduleName,
        public readonly ?int $parentId = null,
        public readonly ?string $routeName = null,
        public readonly bool $active = true,
        public readonly ?string $icon = null,
        public readonly ?string $wording = null,
        public readonly ?string $wordingDomain = null,
        public readonly array $names = []
    ) {
    }
}
