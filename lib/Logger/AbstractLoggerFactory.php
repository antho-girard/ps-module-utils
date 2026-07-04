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

namespace AG\PSModuleUtils\Logger;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;

/**
 * Builds the module's Monolog logger (rotating file in var/logs, correlation UID, channels).
 *
 * Correlation UID: a UidProcessor is created ONCE per factory instance, so every message from that
 * instance — including channel clones returned by withChannel() — shares the same UID. To correlate
 * a whole process across several services, reuse ONE factory instance: fetch getLogger() once and
 * inject it, or register the factory as a shared service (the Symfony DI default). A new factory
 * instance means a new UID (correlation would break).
 */
abstract class AbstractLoggerFactory
{
    private readonly Logger $logger;

    abstract public function getLoggerLevel(): mixed;

    public function __construct(protected readonly string $moduleName)
    {
        $this->logger = new Logger($this->moduleName);
        $level = $this->getLoggerLevel();
        $fileHandler = new RotatingFileHandler(
            sprintf('%s%s.log', $this->getLogFilePath(), $this->moduleName),
            3,
            $level
        );
        $fileHandler->setFilenameFormat('{date}_{filename}', 'Ym');
        $this->logger->pushHandler($fileHandler)
                     ->pushProcessor(new UidProcessor(7));
    }

    /**
     * Default log directory: PrestaShop's standard var/logs/. The file is named after the module
     * (var/logs/{date}_{module}.log) so modules sharing this directory never collide. Override to
     * use a different directory.
     */
    public function getLogFilePath(): string
    {
        return _PS_ROOT_DIR_ . '/var/logs/';
    }

    public function withChannel(string $channel): Logger
    {
        return $this->logger->withName($channel);
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Most recent log files for this module (newest first; default 3 = the rotation depth), e.g. to
     * offer download links in the back office.
     *
     * @return list<string> absolute file paths
     */
    public function getLogFiles(int $limit = 3): array
    {
        return LogFileLocator::locate($this->getLogFilePath(), $this->moduleName, $limit);
    }
}
