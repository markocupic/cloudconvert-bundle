<?php

declare(strict_types=1);

/*
 * This file is part of Cloudconvert Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/cloudconvert-bundle
 */

namespace Markocupic\CloudconvertBundle\Tests\Conversion;

use Markocupic\CloudconvertBundle\Conversion\ConvertFile;
use Markocupic\CloudconvertBundle\Exception\SourceNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ConvertFileTest extends TestCase
{
    private ConvertFile $convertFile;

    private string $source;

    protected function setUp(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $this->convertFile = new ConvertFile($requestStack, new TokenStorage(), sys_get_temp_dir().'/cloudconvert', 'api_key');

        $this->source = sys_get_temp_dir().'/cloudconvert_test_msword.docx';
        file_put_contents($this->source, 'foo');
    }

    protected function tearDown(): void
    {
        if (is_file($this->source)) {
            unlink($this->source);
        }
    }

    public function testSetSource(): void
    {
        $this->convertFile->file($this->source);

        $this->assertSame($this->source, $this->convertFile->getSource());
    }

    public function testThrowsIfSourceDoesNotExist(): void
    {
        $this->expectException(SourceNotFoundException::class);

        $this->convertFile->file(sys_get_temp_dir().'/cloudconvert_test_missing.docx');
    }

    public function testResetClearsTheSource(): void
    {
        $this->convertFile->file($this->source);
        $this->convertFile->reset();

        $this->assertNull($this->convertFile->getSource());
    }

    public function testSetRemoveAndClearOptions(): void
    {
        $this->convertFile->setOption('foo', 'bar');
        $this->convertFile->setOption('bar', 'foo');

        $this->assertSame(['foo' => 'bar', 'bar' => 'foo'], $this->convertFile->getOptions());

        $this->convertFile->removeOption('foo');
        $this->assertSame(['bar' => 'foo'], $this->convertFile->getOptions());

        $this->convertFile->clearOptions();
        $this->assertSame([], $this->convertFile->getOptions());
    }

    public function testGetAndSetApiKey(): void
    {
        $this->assertSame('api_key', $this->convertFile->getApiKey());

        $this->convertFile->setApiKey('custom_api_key');
        $this->assertSame('custom_api_key', $this->convertFile->getApiKey());
    }

    public function testCacheHashCode(): void
    {
        $this->assertNull($this->convertFile->getCacheHashCode());

        $this->convertFile->setCacheHashCode('abc');
        $this->assertSame('abc', $this->convertFile->getCacheHashCode());
    }
}
