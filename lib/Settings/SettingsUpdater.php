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

use AG\PSModuleUtils\Settings\Exception\SettingsValidationException;
use AG\PSModuleUtils\Settings\Storage\ConfigurationStorageInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Generic settings updater.
 *
 * - save(): validate the settings (via their #[Assert] attributes) then persist. Use anywhere
 *   (upgrade script, CLI, arbitrary code) — no controller/Form context required.
 * - persist(): persist without validating. Use when the caller already validated (e.g. a Symfony
 *   Form bound on the settings via data_class), to avoid double validation.
 *
 * Framework-agnostic (depends on the storage port, not on PrestaShop), hence unit-testable.
 */
final class SettingsUpdater
{
    public function __construct(
        private readonly SerializerInterface&DenormalizerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly ConfigurationStorageInterface $storage,
        private readonly ConfigKeyReader $reader = new ConfigKeyReader()
    ) {
    }

    /**
     * @throws SettingsValidationException when the settings violate their constraints
     */
    public function save(AbstractSettings $settings, ?int $idShop = null, ?int $idShopGroup = null): AbstractSettings
    {
        $violations = $this->validator->validate($settings);
        if (count($violations) > 0) {
            throw new SettingsValidationException($violations);
        }

        return $this->persist($settings, $idShop, $idShopGroup);
    }

    public function persist(AbstractSettings $settings, ?int $idShop = null, ?int $idShopGroup = null): AbstractSettings
    {
        foreach ($this->reader->read($settings::class) as $binding) {
            $json = $this->serializer->serialize($settings->{$binding['property']}, 'json');
            $this->storage->set($binding['key'], $json, $idShop, $idShopGroup);
        }

        return $settings;
    }

    /**
     * Denormalizes a raw array (e.g. defaults from a YAML file) into the settings, validates, then
     * persists.
     *
     * @param class-string<AbstractSettings> $settingsClass
     * @param array<string, mixed>           $data
     *
     * @throws SettingsValidationException
     */
    public function saveFromArray(string $settingsClass, array $data, ?int $idShop = null, ?int $idShopGroup = null): AbstractSettings
    {
        return $this->save($this->denormalize($settingsClass, $data), $idShop, $idShopGroup);
    }

    /**
     * Same as saveFromArray() but without validation — for seeding defaults that may be
     * intentionally incomplete (e.g. a blank API key filled in later by the merchant).
     *
     * @param class-string<AbstractSettings> $settingsClass
     * @param array<string, mixed>           $data
     */
    public function persistFromArray(string $settingsClass, array $data, ?int $idShop = null, ?int $idShopGroup = null): AbstractSettings
    {
        return $this->persist($this->denormalize($settingsClass, $data), $idShop, $idShopGroup);
    }

    /**
     * @param class-string<AbstractSettings> $settingsClass
     * @param array<string, mixed>           $data
     */
    private function denormalize(string $settingsClass, array $data): AbstractSettings
    {
        /** @var AbstractSettings $settings */
        $settings = $this->serializer->denormalize($data, $settingsClass);

        return $settings;
    }
}
