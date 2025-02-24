<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class CxReportsEstablishSimpleTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testThis()
    {
        $a = 5;
        $b = 10;
        $this->assertEquals(15, $a + $b);
        $this->assertTrue(true);
    }
}
?>
