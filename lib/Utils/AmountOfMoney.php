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

namespace AG\PSModuleUtils\Utils;

use Alcohol\ISO4217;
use Money\Converter;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\CurrencyPair;
use Money\Exchange\FixedExchange;
use Money\Exchange\ReversedCurrenciesExchange;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

/**
 * Immutable currency-aware monetary amount.
 *
 * Backed solely by moneyphp/money: the value is stored as an integer number of minor units
 * (no float storage), and every operation — parsing, rounding (HALF_UP), arithmetic and
 * cross-currency conversion — goes through moneyphp's calculator. ISO 4217 metadata (exponent,
 * numeric code) comes from alcohol/iso4217.
 *
 * All operations return a new instance; arithmetic and comparison between different currencies
 * throw (moneyphp enforces identical currencies).
 */
final class AmountOfMoney
{
    private function __construct(
        private readonly Money $money,
        private readonly int $exp,
        private readonly string $currencyNumeric
    ) {
    }

    // ---------------------------------------------------------------- Factories

    public static function fromSmallestUnit(int|float|string $amountInSmallestUnit, string $currencyCode): self
    {
        $details = self::currencyDetails($currencyCode);
        $money = new Money((int) round((float) $amountInSmallestUnit), new Currency($currencyCode));

        return new self($money, (int) $details['exp'], (string) $details['numeric']);
    }

    public static function fromStandardUnit(int|float|string $amountInStandardUnit, string $currencyCode): self
    {
        $details = self::currencyDetails($currencyCode);
        $money = self::parser()->parse((string) $amountInStandardUnit, new Currency($currencyCode));

        return new self($money, (int) $details['exp'], (string) $details['numeric']);
    }

    public static function zero(string $currencyCode): self
    {
        $details = self::currencyDetails($currencyCode);

        return new self(new Money(0, new Currency($currencyCode)), (int) $details['exp'], (string) $details['numeric']);
    }

    // ---------------------------------------------------------------- Accessors

    public function getAmount(): float
    {
        return (float) $this->decimalString();
    }

    public function getMinorUnits(): int
    {
        return (int) $this->money->getAmount();
    }

    public function getCurrencyCode(): string
    {
        return $this->money->getCurrency()->getCode();
    }

    public function getCurrencyNumeric(): string
    {
        return $this->currencyNumeric;
    }

    public function formatPrice(): string
    {
        return sprintf('%s %s', number_format($this->getAmount(), $this->exp, '.', ''), $this->getCurrencyCode());
    }

    // ---------------------------------------------------------------- Arithmetic

    public function add(self ...$others): self
    {
        return $this->withMoney($this->money->add(...self::moneys($others)));
    }

    public function subtract(self ...$others): self
    {
        return $this->withMoney($this->money->subtract(...self::moneys($others)));
    }

    /**
     * @param Money::ROUND_* $roundingMode
     */
    public function multiply(int|float|string $factor, int $roundingMode = Money::ROUND_HALF_UP): self
    {
        return $this->withMoney($this->money->multiply((string) $factor, $roundingMode));
    }

    public function absolute(): self
    {
        return $this->withMoney($this->money->absolute());
    }

    /**
     * Distributes the amount according to integer ratios, without losing a minor unit.
     *
     * @return array<int, self>
     */
    public function allocate(int ...$ratios): array
    {
        return array_map(fn (Money $m) => $this->withMoney($m), $this->money->allocate($ratios));
    }

    /**
     * Splits the amount into N parts, without losing a minor unit.
     *
     * @return array<int, self>
     */
    public function allocateTo(int $n): array
    {
        return array_map(fn (Money $m) => $this->withMoney($m), $this->money->allocateTo($n));
    }

    /**
     * Sums two or more amounts of the same currency.
     */
    public static function sum(self $first, self ...$rest): self
    {
        return $first->add(...$rest);
    }

    // ---------------------------------------------------------------- Comparison

    public function compare(self $other): int
    {
        return $this->money->compare($other->money);
    }

    public function equals(self $other): bool
    {
        return $this->money->equals($other->money);
    }

    public function greaterThan(self $other): bool
    {
        return $this->money->greaterThan($other->money);
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->money->greaterThanOrEqual($other->money);
    }

    public function lessThan(self $other): bool
    {
        return $this->money->lessThan($other->money);
    }

    public function lessThanOrEqual(self $other): bool
    {
        return $this->money->lessThanOrEqual($other->money);
    }

    // ---------------------------------------------------------------- Predicates

    public function isZero(): bool
    {
        return $this->money->isZero();
    }

    public function isPositive(): bool
    {
        return $this->money->isPositive();
    }

    public function isNegative(): bool
    {
        return $this->money->isNegative();
    }

    // ---------------------------------------------------------------- Conversion

    /**
     * Converts to a target currency by MULTIPLYING with the conversion rate.
     */
    public function convertTo(string $targetCurrencyCode, int|float $conversionRate): self
    {
        $details = self::currencyDetails($targetCurrencyCode);

        $pair = new CurrencyPair($this->money->getCurrency(), new Currency($targetCurrencyCode), (string) $conversionRate);
        $converter = new Converter(new ISOCurrencies(), new FixedExchange([]));
        $converted = $converter->convertAgainstCurrencyPair($this->money, $pair, Money::ROUND_HALF_UP);

        return new self($converted, (int) $details['exp'], (string) $details['numeric']);
    }

    /**
     * Converts from a source currency by DIVIDING with the conversion rate.
     */
    public function convertFrom(string $targetCurrencyCode, int|float $conversionRate): self
    {
        $details = self::currencyDetails($targetCurrencyCode);

        // 1 target = $conversionRate source; reversing gives the source -> target ratio (1 / rate),
        // computed exactly by moneyphp's calculator (no float division).
        $exchange = new ReversedCurrenciesExchange(
            new FixedExchange([$targetCurrencyCode => [$this->getCurrencyCode() => (string) $conversionRate]])
        );
        $converter = new Converter(new ISOCurrencies(), $exchange);
        $converted = $converter->convert($this->money, new Currency($targetCurrencyCode), Money::ROUND_HALF_UP);

        return new self($converted, (int) $details['exp'], (string) $details['numeric']);
    }

    // ---------------------------------------------------------------- Internals

    private function withMoney(Money $money): self
    {
        return new self($money, $this->exp, $this->currencyNumeric);
    }

    /**
     * @param array<int, self> $amounts
     *
     * @return array<int, Money>
     */
    private static function moneys(array $amounts): array
    {
        return array_map(fn (self $a) => $a->money, $amounts);
    }

    /**
     * @return array{alpha3: string, numeric: string, exp: int, ...}
     */
    private static function currencyDetails(string $currencyCode): array
    {
        return (new ISO4217())->getByCode($currencyCode);
    }

    private static function parser(): DecimalMoneyParser
    {
        return new DecimalMoneyParser(new ISOCurrencies());
    }

    private function decimalString(): string
    {
        return (new DecimalMoneyFormatter(new ISOCurrencies()))->format($this->money);
    }
}
