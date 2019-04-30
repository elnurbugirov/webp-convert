<?php

namespace WebPConvert\Tests\Serve;

use WebPConvert\Serve\ServeFile;
use WebPConvert\Serve\MockedHeader;
use WebPConvert\Serve\Exceptions\ServeFailedException;

use PHPUnit\Framework\TestCase;

class ServeFileTest extends TestCase
{

    public function testServeDefaultOptions()
    {
        MockedHeader::reset();

        $filename = __DIR__ . '/../images/plaintext-with-jpg-extension.jpg';
        $this->assertTrue(file_exists($filename));

        ob_start();
        ServeFile::serve($filename, 'image/webp', []);
        $result = ob_get_clean();

        // Test that content of file was send to output
        $this->assertEquals("text\n", $result);

        // Test that headers were set as expected
        $this->assertTrue(MockedHeader::hasHeader('Content-type: image/webp'));
        $this->assertTrue(MockedHeader::hasHeader('Vary: Accept'));
        $this->assertTrue(MockedHeader::hasHeader('Last-Modified: Mon, 29 Apr 2019 12:54:37 GMT'));
        $this->assertTrue(MockedHeader::hasHeader('Cache-Control: public, max-age=86400'));
        $this->assertTrue(MockedHeader::hasHeaderContaining('Expires:'));
    }


    public function testServeOtherOptions()
    {
        MockedHeader::reset();

        $filename = __DIR__ . '/../images/plaintext-with-jpg-extension.jpg';
        $this->assertTrue(file_exists($filename));

        $options = [
            'add-vary-accept-header' => false,
            'set-content-type-header' => false,
            'set-last-modified-header' => false,
            'set-cache-control-header' => false,
            'cache-control-header' => 'private, max-age=100',
        ];

        ob_start();
        ServeFile::serve($filename, 'image/webp', $options);
        $result = ob_get_clean();

        // Test that content of file was send to output
        $this->assertEquals("text\n", $result);

        // Test that headers were set as expected
        $this->assertFalse(MockedHeader::hasHeader('Content-type: image/webp'));
        $this->assertFalse(MockedHeader::hasHeader('Vary: Accept'));
        $this->assertFalse(MockedHeader::hasHeader('Last-Modified: Mon, 29 Apr 2019 12:54:37 GMT'));
        $this->assertFalse(MockedHeader::hasHeader('Cache-Control: public, max-age=86400'));
        $this->assertFalse(MockedHeader::hasHeaderContaining('Expires:'));
    }

    public function testServeCustomCacheControl()
    {
        MockedHeader::reset();
        $filename = __DIR__ . '/../images/plaintext-with-jpg-extension.jpg';
        $this->assertTrue(file_exists($filename));
        $options = [
            'set-cache-control-header' => true,
            'cache-control-header' => 'private, max-age=100',
        ];
        ob_start();
        ServeFile::serve($filename, 'image/webp', $options);
        $result = ob_get_clean();
        $this->assertTrue(MockedHeader::hasHeader('Cache-Control: private, max-age=100'));
        $this->assertTrue(MockedHeader::hasHeaderContaining('Expires:'));
    }

    public function testServeCustomCacheControlNoMaxAge()
    {
        MockedHeader::reset();
        $filename = __DIR__ . '/../images/plaintext-with-jpg-extension.jpg';
        $this->assertTrue(file_exists($filename));
        $options = [
            'set-cache-control-header' => true,
            'cache-control-header' => 'private',
        ];
        ob_start();
        ServeFile::serve($filename, 'image/webp', $options);
        $result = ob_get_clean();
        $this->assertTrue(MockedHeader::hasHeader('Cache-Control: private'));

        // When there is no max-age, there should neither be any Expires
        $this->assertFalse(MockedHeader::hasHeaderContaining('Expires:'));
    }

    public function testServeNonexistantFile()
    {
        MockedHeader::reset();

        $filename = __DIR__ . '/i-dont-exist';
        $this->assertFalse(file_exists($filename));

        $this->expectException(ServeFailedException::class);

        ob_start();
        ServeFile::serve($filename, 'image/webp', []);
        $result = ob_get_clean();

        $this->assertEquals("", $result);

        $this->assertTrue(MockedHeader::hasHeader('X-WebP-Convert-Error: Could not read file'));
    }

}
require_once('mock-header.inc');
