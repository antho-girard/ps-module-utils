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

/**
 * Locates a module's rotating log files on disk. Pure (no Monolog/PrestaShop) so it is fully
 * unit-testable; used by AbstractLoggerFactory::getLogFiles() to offer BO download links.
 */
final class LogFileLocator
{
    /**
     * Most recent log files for a module, newest first, capped at $limit. Matches the rotating
     * handler naming "{date}_{moduleName}.log" (the fixed-width date prefix makes reverse string
     * order chronological).
     *
     * @return list<string> absolute file paths
     */
    public static function locate(string $directory, string $moduleName, int $limit = 3): array
    {
        $files = glob(rtrim($directory, '/') . '/*_' . $moduleName . '.log');
        if (false === $files) {
            return [];
        }

        rsort($files);

        return array_slice($files, 0, max(0, $limit));
    }
}
