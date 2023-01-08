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

namespace Markocupic\CloudconvertBundle\Tests\Conversion;

use Contao\TestCase\ContaoTestCase;
use Markocupic\CloudconvertBundle\ContaoManager\Plugin;
use Markocupic\CloudconvertBundle\Conversion\ConvertFile;
use Markocupic\CloudconvertBundle\Logger\ContaoLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConvertFileTest extends ContaoTestCase
{
    private ContainerBuilder $container;
    private ConvertFile $cf;
    private string $projectDir;

    private string $source;

    protected function setUp(): void
    {
        $this->container = $this->getContainerWithContaoConfiguration(sys_get_temp_dir());
        $this->projectDir = $this->container->getParameter('kernel.project_dir');
        $this->container->setParameter('markocupic_cloudconvert.api_key', 'api_key');

        $apiKey = $this->container->getParameter('markocupic_cloudconvert.api_key');

        $logger = new ContaoLogger(null);

        $this->cf = new ConvertFile($logger, $apiKey);

        $this->source = $this->projectDir.'/msword.docx';
        file_put_contents($this->source, 'foo');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_file($this->source)) {
            unlink($this->source);
        }
    }

    public function testInstantiation(): void
    {
        $apiKey = $this->container->getParameter('markocupic_cloudconvert.api_key');

        $logger = new ContaoLogger(null);

        $this->assertInstanceOf(ConvertFile::class, new ConvertFile($logger, $apiKey));
    }


    public function testSetSource(): void
    {
        $this->cf->reset();

        $this->cf->file($this->source);
        $this->assertSame($this->source, $this->cf->getSource());
    }

    public function testSetRemoveAndClearOptions(): void
    {
        $this->cf->reset();

        $this->cf->setOption('foo', 'bar');
        $this->cf->setOption('bar', 'foo');

        $opt = $this->cf->getOptions();
        $this->assertSame(2, \count($opt));
        $this->assertSame('bar', $opt['foo']);

        $this->cf->removeOption('foo');
        $opt = $this->cf->getOptions();
        $this->assertSame(1, \count($opt));
        $this->assertSame('foo', $opt['bar']);

        $this->cf->clearOptions();
        $this->assertSame([], $this->cf->getOptions());
    }

    public function testGetApiKey(): void
    {
        $this->cf->reset();

        $this->assertSame('api_key', $this->cf->getApiKey());

        $this->cf->setApiKey('custom_api_key');
        $this->assertSame('custom_api_key', $this->cf->getApiKey());
    }
}
