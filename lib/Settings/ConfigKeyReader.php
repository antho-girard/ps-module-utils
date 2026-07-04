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

namespace AG\PSModuleUtils\Settings;

use AG\PSModuleUtils\Settings\Attribute\ConfigKey;

/**
 * Reads the #[ConfigKey] attributes of a settings class by reflection and returns,
 * for each annotated property, the ps_configuration key and the property's type.
 */
final class ConfigKeyReader
{
    /**
     * @param class-string $settingsClass
     *
     * @return list<array{property: string, key: string, type: class-string}>
     */
    public function read(string $settingsClass): array
    {
        $bindings = [];
        $reflection = new \ReflectionClass($settingsClass);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(ConfigKey::class);
            if ([] === $attributes) {
                continue;
            }

            $type = $property->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                throw new \LogicException(sprintf(
                    'Property "%s::$%s" annotated with #[ConfigKey] must be typed with a settings class.',
                    $settingsClass,
                    $property->getName()
                ));
            }

            /** @var ConfigKey $configKey */
            $configKey = $attributes[0]->newInstance();

            /** @var class-string $typeName */
            $typeName = $type->getName();

            $bindings[] = [
                'property' => $property->getName(),
                'key' => $configKey->name,
                'type' => $typeName,
            ];
        }

        return $bindings;
    }
}
