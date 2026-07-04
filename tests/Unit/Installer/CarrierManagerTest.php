<?php

namespace Tests\AG\PSModuleUtils\Installer;

use AG\PSModuleUtils\Installer\CarrierManager;
use PHPUnit\Framework\TestCase;
use Tests\AG\PSModuleUtils\Installer\Fixtures\InMemoryCarrierGateway;

/**
 * @package Tests\AG\PSModuleUtils\Installer
 */
class CarrierManagerTest extends TestCase
{
    public function testCreatesWhenAbsent(): void
    {
        $gateway = new InMemoryCarrierGateway();

        (new CarrierManager($gateway))->createCarrier([
            'configKey' => 'MYMODULE_CARRIER_ID',
            'delays' => ['en' => 'Delivery', 'fr' => 'Livraison'],
            'name' => 'My carrier',
            'is_module' => true,
        ], 'mymodule');

        $this->assertCount(1, $gateway->created);
        $carrier = $gateway->created[0];
        $this->assertSame('MYMODULE_CARRIER_ID', $carrier->configKey);
        $this->assertSame('mymodule', $carrier->moduleName);
        $this->assertSame(['en' => 'Delivery', 'fr' => 'Livraison'], $carrier->delays);
        $this->assertSame('My carrier', $carrier->attributes['name']);
    }

    public function testSkipsWhenAlreadyExistsForModule(): void
    {
        $gateway = new InMemoryCarrierGateway();
        $gateway->existing['MYMODULE_CARRIER_ID'] = true;

        (new CarrierManager($gateway))->createCarrier([
            'configKey' => 'MYMODULE_CARRIER_ID',
            'delays' => [],
        ], 'mymodule');

        $this->assertCount(0, $gateway->created);
    }

    public function testInstallCarriersIsResilient(): void
    {
        $gateway = new InMemoryCarrierGateway();
        $gateway->throwOnCreate = ['BAD'];

        (new CarrierManager($gateway))->installCarriers([
            ['configKey' => 'BAD', 'delays' => []],
            ['configKey' => 'GOOD', 'delays' => ['en' => 'Ok']],
        ], 'mymodule');

        $this->assertCount(1, $gateway->created);
        $this->assertSame('GOOD', $gateway->created[0]->configKey);
    }
}
