<?php

namespace Hryvinskyi\SeoImageOptimizer\Test\Unit\Model\Url;

use Hryvinskyi\SeoImageOptimizer\Model\Url\ConvertUrlToPath;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class ConvertUrlToPathTest extends TestCase
{
    private ConvertUrlToPath $model;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $baseUrl = 'https://example.com/';
        $baseUrlMedia = 'https://example.com/media/';
        $baseUrlStatic = 'https://example.com/static/';
        $pathMedia = '/tmp/pub/media/';
        $pathStatic = '/tmp/pub/static/';
        $pathRoot = '/tmp/pub/';

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock->method('getBaseUrl')
            ->willReturnMap([
                [UrlInterface::URL_TYPE_LINK, null, $baseUrl],
                [UrlInterface::URL_TYPE_MEDIA, null, $baseUrlMedia],
                [UrlInterface::URL_TYPE_STATIC, null, $baseUrlStatic],
            ]);

        $filesystemMock = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $directoryReadMediaMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $directoryReadMediaMock
            ->method('getAbsolutePath')
            ->willReturn($pathMedia);

        $directoryReadStaticMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $directoryReadStaticMock
            ->method('getAbsolutePath')
            ->willReturn($pathStatic);

        $directoryReadRootMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $directoryReadRootMock
            ->method('getAbsolutePath')
            ->willReturn($pathRoot);

        $filesystemMock
            ->method('getDirectoryRead')
            ->willReturnMap([
                [\Magento\Framework\App\Filesystem\DirectoryList::MEDIA, DriverPool::FILE, $directoryReadMediaMock],
                [\Magento\Framework\App\Filesystem\DirectoryList::STATIC_VIEW, DriverPool::FILE, $directoryReadStaticMock],
                [\Magento\Framework\App\Filesystem\DirectoryList::PUB, DriverPool::FILE, $directoryReadRootMock],
            ]);

        $storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock->method('getStore')
            ->willReturn($storeMock);

        $this->model = $objectManager->getObject(
            ConvertUrlToPath::class,
            [
                'storeManager' => $storeManagerMock,
                'filesystem' => $filesystemMock,
            ]
        );
    }

    public function testAbsoluteMediaUrl()
    {
        $this->assertEquals('/tmp/pub/media/image.png', $this->model->execute('https://example.com/media/image.png'));
    }

    public function testAbsoluteStaticUrl()
    {
        $this->assertEquals('/tmp/pub/static/image.png', $this->model->execute('https://example.com/static/image.png'));
    }

    public function testRelativeMediaUrl()
    {
        $this->assertEquals('/tmp/pub/media/image.png', $this->model->execute('/media/image.png'));
        $this->assertEquals('/tmp/pub/media/image.png', $this->model->execute('media/image.png'));
    }

    public function testRelativeStaticUrl()
    {
        $this->assertEquals('/tmp/pub/static/image.png', $this->model->execute('/static/image.png'));
        $this->assertEquals('/tmp/pub/static/image.png', $this->model->execute('static/image.png'));
    }

    public function testOtherUrl()
    {
        $this->assertEquals(
            'https://foo.example.com/media/image.png',
            $this->model->execute('https://foo.example.com/media/image.png')
        );
        $this->assertEquals(
            '/medias/image.png',
            $this->model->execute('/medias/image.png')
        );
        $this->assertEquals(
            '/statics/image.png',
            $this->model->execute('/statics/image.png')
        );
    }
}
