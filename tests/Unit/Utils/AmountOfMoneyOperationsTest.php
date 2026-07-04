<?php

namespace Tests\AG\PSModuleUtils\Utils;

use AG\PSModuleUtils\Utils\AmountOfMoney;
use PHPUnit\Framework\TestCase;

/**
 * Covers the v4 operation API: variadic arithmetic, multiply, allocation, readable
 * comparisons, predicates and the zero() factory.
 *
 * @package Tests\AG\PSModuleUtils\Utils
 */
class AmountOfMoneyOperationsTest extends TestCase
{
    // ---------------------------------------------------------------- add / subtract

    public function testAddIsVariadic(): void
    {
        $total = AmountOfMoney::fromStandardUnit(10.00, 'EUR')->add(
            AmountOfMoney::fromStandardUnit(20.00, 'EUR'),
            AmountOfMoney::fromStandardUnit(5.50, 'EUR')
        );

        $this->assertSame(3550, $total->getMinorUnits());
    }

    public function testSubtractIsVariadic(): void
    {
        $result = AmountOfMoney::fromStandardUnit(100.00, 'EUR')->subtract(
            AmountOfMoney::fromStandardUnit(30.00, 'EUR'),
            AmountOfMoney::fromStandardUnit(20.00, 'EUR')
        );

        $this->assertSame(5000, $result->getMinorUnits());
    }

    public function testAddDoesNotMutateOperands(): void
    {
        $a = AmountOfMoney::fromStandardUnit(10.00, 'EUR');
        $a->add(AmountOfMoney::fromStandardUnit(5.00, 'EUR'));

        $this->assertSame(1000, $a->getMinorUnits());
    }

    public function testStaticSumOfSeveralAmounts(): void
    {
        $total = AmountOfMoney::sum(
            AmountOfMoney::fromStandardUnit(1.00, 'EUR'),
            AmountOfMoney::fromStandardUnit(2.00, 'EUR'),
            AmountOfMoney::fromStandardUnit(3.00, 'EUR')
        );

        $this->assertSame(600, $total->getMinorUnits());
    }

    public function testArithmeticAcrossDifferentCurrenciesThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AmountOfMoney::fromStandardUnit(10.00, 'EUR')
            ->add(AmountOfMoney::fromStandardUnit(10.00, 'USD'));
    }

    // ---------------------------------------------------------------- multiply / absolute

    public function testMultiplyByInteger(): void
    {
        $result = AmountOfMoney::fromStandardUnit(10.00, 'EUR')->multiply(3);

        $this->assertSame(3000, $result->getMinorUnits());
    }

    public function testMultiplyByDecimalRoundsHalfUp(): void
    {
        // 10.00 * 1.005 = 10.05
        $result = AmountOfMoney::fromStandardUnit(10.00, 'EUR')->multiply('1.005');

        $this->assertSame(1005, $result->getMinorUnits());
    }

    public function testAbsoluteOfNegative(): void
    {
        $result = AmountOfMoney::fromStandardUnit(-70.00, 'EUR')->absolute();

        $this->assertSame(7000, $result->getMinorUnits());
    }

    // ---------------------------------------------------------------- allocation

    public function testAllocateToSplitsWithoutLosingMinorUnits(): void
    {
        $parts = AmountOfMoney::fromStandardUnit(10.00, 'EUR')->allocateTo(3);

        $this->assertCount(3, $parts);
        $this->assertSame([334, 333, 333], array_map(fn (AmountOfMoney $p) => $p->getMinorUnits(), $parts));
        $this->assertSame(1000, array_sum(array_map(fn (AmountOfMoney $p) => $p->getMinorUnits(), $parts)));
    }

    public function testAllocateByRatios(): void
    {
        $parts = AmountOfMoney::fromStandardUnit(10.00, 'EUR')->allocate(70, 30);

        $this->assertSame([700, 300], array_map(fn (AmountOfMoney $p) => $p->getMinorUnits(), $parts));
    }

    // ---------------------------------------------------------------- comparison

    public function testEquals(): void
    {
        $a = AmountOfMoney::fromStandardUnit(10.50, 'EUR');
        $b = AmountOfMoney::fromStandardUnit(10.50, 'EUR');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals(AmountOfMoney::fromStandardUnit(10.00, 'EUR')));
    }

    public function testGreaterAndLessThan(): void
    {
        $ten = AmountOfMoney::fromStandardUnit(10.00, 'EUR');
        $twenty = AmountOfMoney::fromStandardUnit(20.00, 'EUR');

        $this->assertTrue($twenty->greaterThan($ten));
        $this->assertTrue($twenty->greaterThanOrEqual($twenty));
        $this->assertTrue($ten->lessThan($twenty));
        $this->assertTrue($ten->lessThanOrEqual($ten));
    }

    // ---------------------------------------------------------------- predicates

    public function testPredicates(): void
    {
        $this->assertTrue(AmountOfMoney::zero('EUR')->isZero());
        $this->assertTrue(AmountOfMoney::fromStandardUnit(1.00, 'EUR')->isPositive());
        $this->assertTrue(AmountOfMoney::fromStandardUnit(-1.00, 'EUR')->isNegative());
        $this->assertFalse(AmountOfMoney::zero('EUR')->isPositive());
    }

    // ---------------------------------------------------------------- zero

    public function testZeroFactory(): void
    {
        $zero = AmountOfMoney::zero('JPY');

        $this->assertSame(0, $zero->getMinorUnits());
        $this->assertSame('JPY', $zero->getCurrencyCode());
    }
}
