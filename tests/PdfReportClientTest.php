<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use PdfReportClient\PdfReportClient;

class PdfReportClientTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                ['id' => '1', 'name' => 'Report 1'],
                ['id' => '2', 'name' => 'Report 2'],
            ])),
            new Response(200, [], 'PDF content'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $this->client = new PdfReportClient('https://mock-api-url.com', 'mock-pat', $httpClient);
    }

    public function testListReports()
    {
        $reports = $this->client->listReports();
        $this->assertCount(2, $reports);
        $this->assertEquals('Report 1', $reports[0]['name']);
        $this->assertEquals('Report 2', $reports[1]['name']);
    }

    public function testDownloadPdf()
    {
        $savePath = '/tmp/report.pdf';
        $result = $this->client->downloadPdf('1', $savePath);

        $this->assertTrue($result);
        $this->assertFileExists($savePath);

        // Clean up
        unlink($savePath);
    }
}