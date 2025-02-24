<?php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use CxReports\Client\CxReportsClient;

class CxReportsClientImportTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testNewClient()
    {
        $client = new CxReportsClient("http://example.com", "1", "token");
        $this->assertInstanceOf(CxReportsClient::class, $client);
    }
}
?>
