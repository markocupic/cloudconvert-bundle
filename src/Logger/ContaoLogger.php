<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic 2023 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\Logger;

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class ContaoLogger
{
    public function __construct(
        private LoggerInterface|null $logger,
    ) {
    }

    public function log(string $strText, string $strMethod, string $strLogLevel = LogLevel::INFO, string $strContaoLogLevel = ContaoContext::GENERAL): void
    {
        $this->logger?->log(
            $strLogLevel,
            $strText,
            [
                'contao' => new ContaoContext($strMethod, $strContaoLogLevel),
            ]
        );
    }
}
