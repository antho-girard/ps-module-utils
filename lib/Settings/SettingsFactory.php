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
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Assembles the settings services with a DETERMINISTIC Serializer/Validator configuration,
 * without relying on the PrestaShop container (whose serializer is decorated for the Admin API).
 *
 * Usable in any PrestaShop context, including out-of-kernel ones (upgrade scripts, CLI), where
 * the caller wraps the native configuration in a PrestaShopConfigurationStorage. In a Symfony BO
 * controller, prefer the provided config/services.php (dependency injection).
 */
final class SettingsFactory
{
    public static function serializer(): Serializer
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());

        // PhpDocExtractor reads `@var Type[]` docblocks (arrays of objects); ReflectionExtractor
        // handles typed scalar/object properties.
        $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);

        // ClassDiscriminator resolves #[DiscriminatorMap] so collections of a common base
        // deserialize into the right concrete subclass.
        $discriminator = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

        $objectNormalizer = new ObjectNormalizer($classMetadataFactory, null, null, $extractor, $discriminator);

        return new Serializer([$objectNormalizer, new ArrayDenormalizer()], [new JsonEncoder()]);
    }

    public static function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public static function loader(ConfigurationStorageInterface $storage): SettingsLoader
    {
        return new SettingsLoader(self::serializer(), $storage);
    }

    public static function updater(ConfigurationStorageInterface $storage): SettingsUpdater
    {
        return new SettingsUpdater(self::serializer(), self::validator(), $storage);
    }
}
