# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0-beta.1] - 2026-07-05

First **pre-release** of the v4 line: a rewrite native to PrestaShop 9 / PHP 8.
Feature-complete and validated end-to-end on a real PrestaShop 9.0.2 install
(install/uninstall, the three configuration shapes, back-office form). Public
documentation is not written yet — hence `beta`.

Consumers must opt in to the pre-release, e.g.
`composer require anthogirard/ps-module-utils:^4.0@beta`
(or set `minimum-stability` accordingly).

### Requirements

- PHP `>=8.1 <8.5`.
- PrestaShop 9.x. Symfony 6.4 components (Serializer, Validator, PropertyInfo,
  PropertyAccess) are **provided by the PrestaShop core at runtime** — they are
  dev dependencies here, not shipped.
- `ext-bcmath` (required by `moneyphp/money`).

### Added

- **Attribute-driven configuration.** `#[ConfigKey]` maps a DTO property to a
  `ps_configuration` key; `ConfigKeyReader` resolves the bindings. Validation is
  declared with Symfony `#[Assert]` attributes on the DTO (single source of truth,
  reused as-is by back-office forms).
- **`SettingsLoader` / `SettingsUpdater` / `SettingsFactory`.** Load and normalize
  settings; `save()`/`persist()` (validating vs non-validating) and
  `saveFromArray()`/`persistFromArray()` to seed defaults from an array (e.g. a
  YAML file) at install time. The factory assembles a deterministic Serializer
  (ObjectNormalizer + ReflectionExtractor + PhpDocExtractor + JsonEncoder +
  ClassDiscriminator) independently of the core's decorated `serializer`.
- **Configuration storage abstraction.** `Storage\ConfigurationStorageInterface`
  with `Storage\PrestaShopConfigurationStorage` and a `create()` factory that works
  outside the container (install / CLI / upgrade).
- **Rich configuration shapes.** Flat sub-objects, arrays of objects, and
  **polymorphic collections** via `#[DiscriminatorMap]`.
- **`Settings\Exception\SettingsValidationException`** carrying translatable
  constraint messages.
- **Installer managers refactored to a port/adapter design** (framework-agnostic
  core + a single PrestaShop-touching adapter + a value object), making the logic
  unit-testable:
  - `TabManager` — `route_name` / `wording` / `wording_domain` / `icon` /
    visibility support, `uninstallTab()`, and a resilient `installTabs()` that logs
    and skips a failing tab instead of aborting the install.
  - `CarrierManager` and `OrderStateManager` — idempotent create, multilingual
    fields, nullable logger, resilient per-item handling.
- **Logging helpers.** `Logger\LogFileLocator::locate()` and
  `AbstractLoggerFactory::getLogFiles()` to expose the last rotating log files
  (e.g. as back-office download links). Correlation-UID sharing is documented
  (reuse one factory instance / register it as a shared service).
- **`config/services.php`** ready to import from a module's `config/services.yml`
  for zero-boilerplate back-office wiring.
- **`AmountOfMoney`** enriched API: variadic `add`/`subtract`, `multiply`,
  `absolute`, `allocate`/`allocateTo`, comparisons (`equals`,
  `greaterThan[OrEqual]`, `lessThan[OrEqual]`), `isZero`/`isPositive`/`isNegative`,
  `zero()`, static `sum()`.

### Changed

- **Monetary values now rely solely on `moneyphp/money`** (no float arithmetic).
  `getAmountInCents()` is renamed `getMinorUnits()`.
- **Dependency-injection model.** The legacy service container (`getService()`) is
  gone; the library provides `config/services.php` for back-office autowiring and a
  standalone factory for every other context.

### Removed

- Legacy configuration stack: `AbstractSettingsLoader`, `AbstractSettingsUpdater`,
  `OptionsResolver\*`, `Validation\*`, `Serializer\AbstractSettingsSerializer`.
- `Presenter\PresenterInterface`.
- The legacy service-container accessor (`getService()`).

### Data compatibility

Although the API breaks, **stored data stays compatible**: JSON already written by
v3 in `ps_configuration` still deserializes under v4 as long as property names map
1:1 to JSON keys (reading tolerates key reordering, missing keys, and unknown keys).

[4.0.0-beta.1]: https://github.com/antho-girard/ps-module-utils/releases/tag/4.0.0-beta.1
