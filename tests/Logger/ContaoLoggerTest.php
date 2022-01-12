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

namespace Markocupic\CloudconvertBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\TestCase\ContaoTestCase;
use Markocupic\CloudconvertBundle\ContaoManager\Plugin;
use Markocupic\CloudconvertBundle\Logger\ContaoLogger;
use Markocupic\CloudconvertBundle\MarkocupicCloudconvertBundle;

class ContaoLoggerTest extends ContaoTestCase
{
    
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(ContaoLogger::class, new ContaoLogger(null));
    }

}
