<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license LGPL-3.0+
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicCloudconvertBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
