<?php

namespace Tests\AG\PSModuleUtils\Utils;

use AG\PSModuleUtils\Utils\AmountOfMoney;
use PHPUnit\Framework\TestCase;

/**
 * Characterization test — pins the CURRENT (v3) rounding behaviour of AmountOfMoney
 * on half-unit and floating-point edge cases, BEFORE the v4 rewrite onto moneyphp/money only.
 *
 * These golden values are the observed v3 behaviour under the test bootstrap's mocked
 * Tools::ps_round() (PHP round(), HALF_AWAY_FROM_ZERO). They are a safety net: if the v4
 * rewrite changes any of them (moneyphp rounding vs PrestaShop native), it must be a
 * conscious decision, not an accident. Reconciling the mock with real PrestaShop rounding
 * is a separate integration concern.
 *
 * @package Tests\AG\PSModuleUtils\Utils
 */
class AmountOfMoneyRoundingCharacterizationTest extends TestCase
{
    /**
     * Half-cent inputs on a 2-decimal currency round away from zero (current behaviour).
     *
     * @dataProvider halfUnitProvider
     *
     * @param float $input        Amount in standard unit.
     * @param float $expectedAmount Expected getAmount().
     * @param int   $expectedCents  Expected getMinorUnits().
     * @return void
     */
    public function testHalfUnitStandardAmountsRoundAsInV3(float $input, float $expectedAmount, int $expectedCents): void
    {
        $amount = AmountOfMoney::fromStandardUnit($input, 'EUR');

        $this->assertEqualsWithDelta($expectedAmount, $amount->getAmount(), 0.00001);
        $this->assertSame($expectedCents, $amount->getMinorUnits());
    }

    /**
     * @return array<string, array{float, float, int}>
     */
    public static function halfUnitProvider(): array
    {
        return [
            '2.345 -> 2.35'   => [2.345, 2.35, 235],
            '2.355 -> 2.36'   => [2.355, 2.36, 236],
            '1.005 -> 1.01'   => [1.005, 1.01, 101],
            '0.005 -> 0.01'   => [0.005, 0.01, 1],
            '-2.345 -> -2.35' => [-2.345, -2.35, -235],
        ];
    }

    /**
     * Summing classic floating-point values goes through minor units without drift.
     *
     * @return void
     */
    public function testSumOfFloatingPointValuesHasNoDrift(): void
    {
        $total = AmountOfMoney::sum(
            AmountOfMoney::fromStandardUnit(0.1, 'EUR'),
            AmountOfMoney::fromStandardUnit(0.2, 'EUR')
        );

        $this->assertEqualsWithDelta(0.30, $total->getAmount(), 0.00001);
        $this->assertSame(30, $total->getMinorUnits());
    }

    /**
     * Conversion at exact 2-decimal boundaries is preserved.
     *
     * @return void
     */
    public function testConversionAtHalfCentBoundaries(): void
    {
        $base = AmountOfMoney::fromStandardUnit(100.00, 'EUR');

        $this->assertEqualsWithDelta(111.5, $base->convertTo('USD', 1.115)->getAmount(), 0.00001);
        $this->assertEqualsWithDelta(112.5, $base->convertTo('USD', 1.125)->getAmount(), 0.00001);
    }

    /**
     * A 3-decimal currency (BHD) rounds a half-unit at the third decimal upward.
     *
     * @return void
     */
    public function testThreeDecimalCurrencyHalfUnitRounding(): void
    {
        $amount = AmountOfMoney::fromStandardUnit(1.2345, 'BHD');

        $this->assertSame(1235, $amount->getMinorUnits());
    }

    /**
     * Negative amounts keep two decimals in formatPrice().
     *
     * @return void
     */
    public function testFormatPricePreservesNegativeAmount(): void
    {
        $amount = AmountOfMoney::fromStandardUnit(-2.5, 'EUR');

        $this->assertSame('-2.50 EUR', $amount->formatPrice());
    }
}
