<?php

namespace Tests\AG\PSModuleUtils\Settings;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tests\AG\PSModuleUtils\Settings\Fixtures\AccountSettingsFixture;
use Tests\AG\PSModuleUtils\Settings\Fixtures\MainSettingsFixture;

/**
 * Characterization test — pins the JSON serialization CONTRACT for config sub-objects.
 *
 * ps-module-utils stores each config sub-object as JSON in a ps_configuration key. The v4
 * rewrite breaks the API but MUST preserve DATA compatibility: JSON already written by v3 in
 * live shops must still deserialize after a module upgrades. That invariant holds as long as
 * property names map 1:1 to JSON keys and reading tolerates key reordering, missing keys
 * (older data) and unknown keys (removed fields).
 *
 * The Serializer here is assembled deterministically, the way the v4 factory does:
 * ObjectNormalizer + ReflectionExtractor + JsonEncoder — NOT inherited from the PrestaShop core.
 *
 * @package Tests\AG\PSModuleUtils\Settings
 */
class ConfigJsonFormatCharacterizationTest extends TestCase
{
    private Serializer $serializer;

    /**
     * Builds the deterministic Serializer used by all cases.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $extractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
        $objectNormalizer = new ObjectNormalizer(null, null, null, $extractor);
        $this->serializer = new Serializer(
            [$objectNormalizer, new ArrayDenormalizer()],
            [new JsonEncoder()]
        );
    }

    /**
     * JSON keys are exactly the DTO property names, with their values. This 1:1 mapping
     * is the backbone of data compatibility.
     *
     * @return void
     */
    public function testSerializedKeysMatchPropertyNames(): void
    {
        $account = new AccountSettingsFixture();
        $account->apiKey = 'abc123';
        $account->mode = 'live';
        $account->enabled = true;
        $account->timeout = 45;
        $account->rate = 2.5;

        $decoded = json_decode($this->serializer->serialize($account, 'json'), true);

        $this->assertEquals(
            ['apiKey' => 'abc123', 'mode' => 'live', 'enabled' => true, 'timeout' => 45, 'rate' => 2.5],
            $decoded
        );
    }

    /**
     * Serialize then deserialize yields an equivalent object (flat case).
     *
     * @return void
     */
    public function testRoundTripPreservesFlatObject(): void
    {
        $account = new AccountSettingsFixture();
        $account->apiKey = 'key';
        $account->mode = 'live';
        $account->enabled = true;
        $account->timeout = 10;
        $account->rate = 3.25;

        $json = $this->serializer->serialize($account, 'json');
        $restored = $this->serializer->deserialize($json, AccountSettingsFixture::class, 'json');

        $this->assertEquals($account, $restored);
    }

    /**
     * Nested object + scalar list round-trip preserved.
     *
     * @return void
     */
    public function testRoundTripPreservesNestedObject(): void
    {
        $main = new MainSettingsFixture();
        $main->label = 'Shop';
        $main->countries = ['FR', 'BE', 'DE'];
        $main->account->apiKey = 'nested-key';
        $main->account->mode = 'live';

        $json = $this->serializer->serialize($main, 'json');
        $restored = $this->serializer->deserialize($json, MainSettingsFixture::class, 'json');

        $this->assertEquals($main, $restored);
        $this->assertInstanceOf(AccountSettingsFixture::class, $restored->account);
        $this->assertSame(['FR', 'BE', 'DE'], $restored->countries);
    }

    /**
     * Data compat: v4 reads v3-written JSON whose keys are in a different order.
     *
     * @return void
     */
    public function testReadsV3JsonWithReorderedKeys(): void
    {
        $v3Json = '{"mode":"live","rate":2.0,"apiKey":"stored","timeout":60,"enabled":true}';

        /** @var AccountSettingsFixture $restored */
        $restored = $this->serializer->deserialize($v3Json, AccountSettingsFixture::class, 'json');

        $this->assertSame('stored', $restored->apiKey);
        $this->assertSame('live', $restored->mode);
        $this->assertTrue($restored->enabled);
        $this->assertSame(60, $restored->timeout);
        $this->assertSame(2.0, $restored->rate);
    }

    /**
     * Data compat: v3 JSON lacking a field added later keeps the DTO's default value
     * (a module can add a config field in v4 without breaking existing stored data).
     *
     * @return void
     */
    public function testReadsV3JsonMissingNewFieldKeepsDefault(): void
    {
        $v3Json = '{"apiKey":"stored","mode":"live"}';

        /** @var AccountSettingsFixture $restored */
        $restored = $this->serializer->deserialize($v3Json, AccountSettingsFixture::class, 'json');

        $this->assertSame('stored', $restored->apiKey);
        $this->assertSame('live', $restored->mode);
        $this->assertFalse($restored->enabled, 'Missing field must keep the property default');
        $this->assertSame(30, $restored->timeout);
        $this->assertSame(1.5, $restored->rate);
    }

    /**
     * Data compat: v3 JSON carrying a legacy key removed in v4 is ignored, not fatal.
     *
     * @return void
     */
    public function testReadsV3JsonWithUnknownKeyIgnored(): void
    {
        $v3Json = '{"apiKey":"stored","mode":"live","legacyRemovedField":"whatever"}';

        /** @var AccountSettingsFixture $restored */
        $restored = $this->serializer->deserialize($v3Json, AccountSettingsFixture::class, 'json');

        $this->assertSame('stored', $restored->apiKey);
        $this->assertSame('live', $restored->mode);
    }
}
