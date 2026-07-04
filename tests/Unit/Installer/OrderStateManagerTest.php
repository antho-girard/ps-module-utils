<?php

namespace Tests\AG\PSModuleUtils\Installer;

use AG\PSModuleUtils\Installer\OrderStateManager;
use PHPUnit\Framework\TestCase;
use Tests\AG\PSModuleUtils\Installer\Fixtures\InMemoryOrderStateGateway;

/**
 * @package Tests\AG\PSModuleUtils\Installer
 */
class OrderStateManagerTest extends TestCase
{
    public function testCreatesWhenAbsent(): void
    {
        $gateway = new InMemoryOrderStateGateway();

        (new OrderStateManager($gateway))->createOrderState([
            'configKey' => 'MYMODULE_STATE_ID',
            'names' => ['en' => 'Paid', 'fr' => 'Payé'],
            'logo' => 'icon.gif',
            'color' => '#01B887',
            'send_email' => 0,
        ], 'mymodule');

        $this->assertCount(1, $gateway->created);
        $state = $gateway->created[0];
        $this->assertSame('MYMODULE_STATE_ID', $state->configKey);
        $this->assertSame('mymodule', $state->moduleName);
        $this->assertSame(['en' => 'Paid', 'fr' => 'Payé'], $state->names);
        $this->assertSame('icon.gif', $state->logo);
        $this->assertSame('#01B887', $state->attributes['color']);
    }

    public function testSkipsWhenAlreadyExists(): void
    {
        $gateway = new InMemoryOrderStateGateway();
        $gateway->existing['MYMODULE_STATE_ID'] = true;

        (new OrderStateManager($gateway))->createOrderState(['configKey' => 'MYMODULE_STATE_ID'], 'mymodule');

        $this->assertCount(0, $gateway->created);
    }

    public function testLogoDefaultsToNullWhenAbsent(): void
    {
        $gateway = new InMemoryOrderStateGateway();

        (new OrderStateManager($gateway))->createOrderState([
            'configKey' => 'MYMODULE_STATE_ID',
            'names' => ['en' => 'Paid'],
        ], 'mymodule');

        $this->assertNull($gateway->created[0]->logo);
    }

    public function testInstallOrderStatesIsResilient(): void
    {
        $gateway = new InMemoryOrderStateGateway();
        $gateway->throwOnCreate = ['BAD'];

        (new OrderStateManager($gateway))->installOrderStates([
            ['configKey' => 'BAD'],
            ['configKey' => 'GOOD', 'names' => ['en' => 'Ok']],
        ], 'mymodule');

        $this->assertCount(1, $gateway->created);
        $this->assertSame('GOOD', $gateway->created[0]->configKey);
    }
}
