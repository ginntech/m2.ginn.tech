<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Test\Unit\Asset\Bundle;

use Magento\Framework\View\Asset\Bundle\Manager;

class ManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var  Manager|\PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    /** @var  \Magento\Framework\Filesystem|\PHPUnit\Framework\MockObject\MockObject */
    protected $filesystem;

    /** @var  \Magento\Framework\View\Asset\Bundle|\PHPUnit\Framework\MockObject\MockObject */
    protected $bundle;

    /** @var  \Magento\Framework\View\Asset\Bundle\ConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $bundleConfig;

    /** @var  \Magento\Framework\View\Asset\ConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $assetConfig;

    /** @var  \Magento\Framework\View\Asset\LocalInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $asset;

    /** @var \Magento\Framework\View\Asset\Minification|\PHPUnit\Framework\MockObject\MockObject */
    private $minificationMock;

    protected function setUp(): void
    {
        $this->filesystem = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->bundle = $this->getMockBuilder(\Magento\Framework\View\Asset\Bundle::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->bundleConfig = $this->getMockBuilder(\Magento\Framework\View\Asset\Bundle\ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assetConfig = $this->getMockBuilder(\Magento\Framework\View\Asset\ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->asset = $this->getMockForAbstractClass(
            \Magento\Framework\View\Asset\LocalInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['getContentType']
        );

        $this->minificationMock = $this->getMockBuilder(\Magento\Framework\View\Asset\Minification::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new Manager(
            $this->filesystem,
            $this->bundle,
            $this->bundleConfig,
            $this->assetConfig,
            $this->minificationMock
        );
    }

    public function testAddAssetWithInvalidType()
    {
        $this->asset->expects($this->once())
            ->method('getContentType')
            ->willReturn('testType');

        $this->assertFalse($this->manager->addAsset($this->asset));
    }

    public function testAddAssetWithExcludedFile()
    {
        $dirRead = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMockBuilder(\Magento\Framework\View\Asset\File\FallbackContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configView = $this->getMockBuilder(\Magento\Framework\Config\View::class)
            ->setMockClassName('configView')
            ->disableOriginalConstructor()
            ->getMock();

        $this->asset->expects($this->atLeastOnce())
            ->method('getContext')
            ->willReturn($context);
        $this->asset->expects($this->atLeastOnce())
            ->method('getContentType')
            ->willReturn('js');
        $this->asset->expects($this->atLeastOnce())
            ->method('getModule')
            ->willReturn('Lib');
        $this->asset->expects($this->atLeastOnce())
            ->method('getSourceFile')
            ->willReturn('source/file.min.js');
        $this->asset->expects($this->atLeastOnce())
            ->method('getFilePath')
            ->willReturn('source/file.min.js');
        $this->filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->with(\Magento\Framework\App\Filesystem\DirectoryList::APP)
            ->willReturn($dirRead);
        $this->bundleConfig->expects($this->atLeastOnce())
            ->method('getConfig')
            ->with($context)
            ->willReturn($configView);
        $configView->expects($this->once())
            ->method('getExcludedFiles')
            ->willReturn(['Lib:' . ':source/file.min.js']);

        $this->assertFalse($this->manager->addAsset($this->asset));
    }

    public function testAddAssetWithExcludedDirectory()
    {
        $dirRead = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMockBuilder(\Magento\Framework\View\Asset\File\FallbackContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configView = $this->getMockBuilder(\Magento\Framework\Config\View::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->with(\Magento\Framework\App\Filesystem\DirectoryList::APP)
            ->willReturn($dirRead);
        $dirRead->expects($this->once())
            ->method('getAbsolutePath')
            ->with('/path/to/file.js')
            ->willReturn(true);
        $this->asset->expects($this->atLeastOnce())
            ->method('getSourceFile')
            ->willReturn('/path/to/source/file.min.js');
        $this->asset->expects($this->atLeastOnce())
            ->method('getContentType')
            ->willReturn('js');
        $this->asset->expects($this->once())
            ->method('getPath')
            ->willReturn('/path/to/file.js');
        $this->asset->expects($this->atLeastOnce())
            ->method('getModule')
            ->willReturn('');
        $this->asset->expects($this->atLeastOnce())
            ->method('getFilePath')
            ->willReturn('file/path.js');
        $this->asset->expects($this->atLeastOnce())
            ->method('getContext')
            ->willReturn($context);
        $this->bundleConfig->expects($this->atLeastOnce())
            ->method('getConfig')
            ->with($context)
            ->willReturn($configView);
        $configView->expects($this->once())
            ->method('getExcludedFiles')
            ->willReturn([]);
        $configView->expects($this->once())
            ->method('getExcludedDir')
            ->willReturn(['Lib:' . ':file']);

        $this->assertFalse($this->manager->addAsset($this->asset));
    }

    public function testAddAsset()
    {
        $dirRead = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\ReadInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMockBuilder(\Magento\Framework\View\Asset\File\FallbackContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configView = $this->getMockBuilder(\Magento\Framework\Config\View::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem->expects($this->once())
            ->method('getDirectoryRead')
            ->with(\Magento\Framework\App\Filesystem\DirectoryList::APP)
            ->willReturn($dirRead);
        $this->asset->expects($this->atLeastOnce())
            ->method('getSourceFile')
            ->willReturn('/path/to/source/file.min.js');
        $this->asset->expects($this->atLeastOnce())
            ->method('getContentType')
            ->willReturn('js');
        $this->asset->expects($this->once())
            ->method('getPath')
            ->willReturn('/path/to/file.js');
        $this->asset->expects($this->atLeastOnce())
            ->method('getContext')
            ->willReturn($context);
        $this->bundleConfig->expects($this->atLeastOnce())
            ->method('getConfig')
            ->with($context)
            ->willReturn($configView);
        $configView->expects($this->once())
            ->method('getExcludedFiles')
            ->willReturn([]);
        $configView->expects($this->once())
            ->method('getExcludedDir')
            ->willReturn([]);
        $this->bundle->expects($this->once())
            ->method('addAsset')
            ->with($this->asset);

        $this->assertTrue($this->manager->addAsset($this->asset));
    }

    public function testFlush()
    {
        $this->bundle->expects($this->once())
            ->method('flush');
        $this->manager->flush();
    }
}
