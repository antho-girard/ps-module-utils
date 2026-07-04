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

use AG\PSModuleUtils\Settings\Storage\ConfigurationStorageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Generic settings loader: for each #[ConfigKey] property, reads its JSON from the
 * configuration store and deserializes it into the typed sub-object, then runs postLoading().
 *
 * Framework-agnostic (depends on the storage port, not on PrestaShop), hence unit-testable.
 */
final class SettingsLoader
{
    public function __construct(
        private readonly SerializerInterface&NormalizerInterface $serializer,
        private readonly ConfigurationStorageInterface $storage,
        private readonly ConfigKeyReader $reader = new ConfigKeyReader()
    ) {
    }

    /**
     * @template T of AbstractSettings
     *
     * @param class-string<T> $settingsClass
     *
     * @return T
     */
    public function load(string $settingsClass, ?int $idShop = null, ?int $idShopGroup = null): AbstractSettings
    {
        $settings = new $settingsClass();

        foreach ($this->reader->read($settingsClass) as $binding) {
            $json = $this->storage->get($binding['key'], $idShop, $idShopGroup) ?? '{}';
            $settings->{$binding['property']} = $this->serializer->deserialize($json, $binding['type'], 'json');
        }

        return $settings->postLoading();
    }

    /**
     * Normalizes a settings object into an array, e.g. to feed a template. The caller is free
     * to complete the returned array with extra values (constants, computed fields, …).
     *
     * @return array<string, mixed>
     */
    public function normalize(AbstractSettings $settings): array
    {
        /** @var array<string, mixed> $normalized */
        $normalized = $this->serializer->normalize($settings);

        return $normalized;
    }
}
