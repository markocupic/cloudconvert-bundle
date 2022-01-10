<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\Logger;

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ContaoLogger
{
    private ?LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function log(string $strText, string $strMethod, string $strLogLevel = LogLevel::INFO, string $strContaoLogLevel = ContaoContext::GENERAL): void
    {
        if (null !== $this->logger) {
            $this->logger->log(
                $strLogLevel,
                $strText,
                [
                    'contao' => new ContaoContext($strMethod, $strContaoLogLevel),
                ]
            );
        }
    }
}
