<?php

namespace Tests\AG\PSModuleUtils\Logger;

use AG\PSModuleUtils\Logger\LogFileLocator;
use PHPUnit\Framework\TestCase;

/**
 * @package Tests\AG\PSModuleUtils\Logger
 */
class LogFileLocatorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/psmu_logs_' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->dir);
    }

    private function createFile(string $name): void
    {
        file_put_contents($this->dir . '/' . $name, 'log');
    }

    public function testReturnsMatchingFilesNewestFirst(): void
    {
        $this->createFile('202605_sample.log');
        $this->createFile('202607_sample.log');
        $this->createFile('202606_sample.log');
        $this->createFile('202607_other.log');   // different module — ignored
        $this->createFile('readme.txt');          // not a log — ignored

        $files = LogFileLocator::locate($this->dir, 'sample');

        $this->assertSame([
            $this->dir . '/202607_sample.log',
            $this->dir . '/202606_sample.log',
            $this->dir . '/202605_sample.log',
        ], $files);
    }

    public function testRespectsLimit(): void
    {
        $this->createFile('202605_sample.log');
        $this->createFile('202606_sample.log');
        $this->createFile('202607_sample.log');

        $files = LogFileLocator::locate($this->dir, 'sample', 2);

        $this->assertSame([
            $this->dir . '/202607_sample.log',
            $this->dir . '/202606_sample.log',
        ], $files);
    }

    public function testReturnsEmptyWhenNoMatch(): void
    {
        $this->assertSame([], LogFileLocator::locate($this->dir, 'absent'));
    }
}
