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
 * - persistGlobal() / persistFromArrayGlobal(): persist at the all-shops (global) level regardless
 *   of the current shop context. Use to seed install defaults so they act as the fallback for every
 *   shop (the context-aware persist() would otherwise write only the install's current shop).
 *
 * By default persist()/save() follow the current shop context (per-shop configuration works out of
 * the box). Framework-agnostic (depends on the storage port, not on PrestaShop), hence unit-testable.
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
        return $this->write(
            $settings,
            fn (string $key, string $json) => $this->storage->set($key, $json, $idShop, $idShopGroup)
        );
    }

    /**
     * Persists a SINGLE #[ConfigKey] binding (one section/tab), leaving the others untouched.
     * Needed for multi-tab configuration pages where each tab saves independently: persist() would
     * otherwise rewrite every key — and, with the context-aware storage, pin a value that was only
     * inherited from the global level as a per-shop override.
     *
     * @throws \InvalidArgumentException if $property carries no #[ConfigKey] on the settings class
     */
    public function persistProperty(AbstractSettings $settings, string $property, ?int $idShop = null, ?int $idShopGroup = null): AbstractSettings
    {
        return $this->write(
            $settings,
            fn (string $key, string $json) => $this->storage->set($key, $json, $idShop, $idShopGroup),
            $property
        );
    }

    /**
     * Persists at the all-shops (global) level, without validating. Meant for seeding install
     * defaults: written globally, they serve as the fallback for every shop (a per-shop context at
     * install time would otherwise leave the other shops without a default).
     */
    public function persistGlobal(AbstractSettings $settings): AbstractSettings
    {
        return $this->write(
            $settings,
            fn (string $key, string $json) => $this->storage->setGlobal($key, $json)
        );
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
     * Same as persistFromArray() but at the all-shops (global) level — the recommended way to seed
     * install defaults (see persistGlobal()).
     *
     * @param class-string<AbstractSettings> $settingsClass
     * @param array<string, mixed>           $data
     */
    public function persistFromArrayGlobal(string $settingsClass, array $data): AbstractSettings
    {
        return $this->persistGlobal($this->denormalize($settingsClass, $data));
    }

    /**
     * Serializes each #[ConfigKey] binding to JSON and hands it to $writer(key, json). Shared by
     * persist(), persistGlobal() and persistProperty(). When $onlyProperty is set, only that binding
     * is written (and its absence is an error, to catch typos rather than silently no-op).
     *
     * @param callable(string, string): void $writer
     *
     * @throws \InvalidArgumentException if $onlyProperty has no #[ConfigKey] binding
     */
    private function write(AbstractSettings $settings, callable $writer, ?string $onlyProperty = null): AbstractSettings
    {
        $matched = false;
        foreach ($this->reader->read($settings::class) as $binding) {
            if (null !== $onlyProperty && $binding['property'] !== $onlyProperty) {
                continue;
            }
            $matched = true;
            $json = $this->serializer->serialize($settings->{$binding['property']}, 'json');
            $writer($binding['key'], $json);
        }

        if (null !== $onlyProperty && !$matched) {
            throw new \InvalidArgumentException(sprintf(
                'No #[ConfigKey] binding found for property "%s" on %s.',
                $onlyProperty,
                $settings::class
            ));
        }

        return $settings;
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
