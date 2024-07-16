<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PdfReportClient\PdfReportClient;
use PdfReportClient\Models\Report;
use PdfReportClient\Models\ReportType;
use PdfReportClient\Models\Workspace;
use PdfReportClient\Models\TemporaryData;
use PdfReportClient\Models\TemporaryDataCreate;
use PdfReportClient\Models\NonceToken;

class PdfReportClientTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        // Mock responses for each endpoint
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                [
                    'id' => 1,
                    'name' => 'Report 1',
                    'reportTypeId' => 101,
                    'reportTypeName' => 'Type 1',
                    'reportTemplateName' => 'Template 1',
                    'previewImage' => 'http://example.com/image1.png',
                    'themeName' => 'Theme 1',
                    'isDefault' => true,
                ],
                [
                    'id' => 2,
                    'name' => 'Report 2',
                    'reportTypeId' => 102,
                    'reportTypeName' => 'Type 2',
                    'reportTemplateName' => 'Template 2',
                    'previewImage' => null,
                    'themeName' => 'Theme 2',
                    'isDefault' => false,
                ],
            ])),
            new Response(200, [], 'PDF content'),
            new Response(200, [], json_encode([
                [
                    'id' => 1,
                    'name' => 'Invoice',
                    'description' => 'Invoice report',
                    'code' => 'INV',
                    'defaultReportId' => 1,
                    'defaultReportName' => 'Default Invoice',
                ],
                [
                    'id' => 2,
                    'name' => 'Receipt',
                    'description' => 'Receipt report',
                    'code' => 'REC',
                    'defaultReportId' => null,
                    'defaultReportName' => null,
                ],
            ])),
            new Response(200, [], json_encode([
                [
                    'id' => 1,
                    'name' => 'Workspace 1',
                    'description' => 'Description 1',
                    'code' => 'WS1',
                ],
                [
                    'id' => 2,
                    'name' => 'Workspace 2',
                    'description' => 'Description 2',
                    'code' => 'WS2',
                ],
            ])),
            new Response(200, [], json_encode(['nonce' => 'generated-nonce'])),
            new Response(200, [], json_encode(['preview_url' => 'https://example.com/preview'])),
            new Response(200, [], json_encode([
                'tempDataId' => 12345,
                'expiryDate' => '2024-12-31T23:59:59Z',
            ])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $httpClient = new Client(['handler' => $handlerStack]);

        $this->client = new PdfReportClient('https://mock-api-url.com', 'mock-pat', $httpClient);
    }

    public function testListReports()
    {
        $reports = $this->client->listReports();
        $this->assertCount(2, $reports);

        $this->assertInstanceOf(Report::class, $reports[0]);
        $this->assertEquals(1, $reports[0]->id);
        $this->assertEquals('Report 1', $reports[0]->name);
        $this->assertEquals(101, $reports[0]->reportTypeId);
        $this->assertEquals('Type 1', $reports[0]->reportTypeName);
        $this->assertEquals('Template 1', $reports[0]->reportTemplateName);
        $this->assertEquals('http://example.com/image1.png', $reports[0]->previewImage);
        $this->assertEquals('Theme 1', $reports[0]->themeName);
        $this->assertTrue($reports[0]->isDefault);

        $this->assertInstanceOf(Report::class, $reports[1]);
        $this->assertEquals(2, $reports[1]->id);
        $this->assertEquals('Report 2', $reports[1]->name);
        $this->assertEquals(102, $reports[1]->reportTypeId);
        $this->assertEquals('Type 2', $reports[1]->reportTypeName);
        $this->assertEquals('Template 2', $reports[1]->reportTemplateName);
        $this->assertNull($reports[1]->previewImage);
        $this->assertEquals('Theme 2', $reports[1]->themeName);
        $this->assertFalse($reports[1]->isDefault);
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

    public function testGetReportTypes()
    {
        $types = $this->client->getReportTypes();
        $this->assertCount(2, $types);

        $this->assertInstanceOf(ReportType::class, $types[0]);
        $this->assertEquals(1, $types[0]->id);
        $this->assertEquals('Invoice', $types[0]->name);
        $this->assertEquals('Invoice report', $types[0]->description);
        $this->assertEquals('INV', $types[0]->code);
        $this->assertEquals(1, $types[0]->defaultReportId);
        $this->assertEquals('Default Invoice', $types[0]->defaultReportName);

        $this->assertInstanceOf(ReportType::class, $types[1]);
        $this->assertEquals(2, $types[1]->id);
        $this->assertEquals('Receipt', $types[1]->name);
        $this->assertEquals('Receipt report', $types[1]->description);
        $this->assertEquals('REC', $types[1]->code);
        $this->assertNull($types[1]->defaultReportId);
        $this->assertNull($types[1]->defaultReportName);
    }

    public function testGetWorkspaces()
    {
        $workspaces = $this->client->getWorkspaces();
        $this->assertCount(2, $workspaces);

        $this->assertInstanceOf(Workspace::class, $workspaces[0]);
        $this->assertEquals(1, $workspaces[0]->id);
        $this->assertEquals('Workspace 1', $workspaces[0]->name);
        $this->assertEquals('Description 1', $workspaces[0]->description);
        $this->assertEquals('WS1', $workspaces[0]->code);

        $this->assertInstanceOf(Workspace::class, $workspaces[1]);
        $this->assertEquals(2, $workspaces[1]->id);
        $this->assertEquals('Workspace 2', $workspaces[1]->name);
        $this->assertEquals('Description 2', $workspaces[1]->description);
        $this->assertEquals('WS2', $workspaces[1]->code);
    }

    public function testCreateNonceAuthToken()
    {
        $nonce = $this->client->createNonceAuthToken();
        $this->assertInstanceOf(NonceToken::class, $nonce);
        $this->assertEquals('generated-nonce', $nonce->nonce);
    }

    public function testGetReportPreviewURL()
    {
        $url = $this->client->getReportPreviewURL('invoice', [], [], 1, 'generated-nonce');
        $this->assertEquals('https://example.com/preview', $url['preview_url']);
    }

    public function testPostTempData()
    {
        $tempData = ['content' => json_encode(['key' => 'value'])];
        $response = $this->client->postTempData($tempData);
        $this->assertInstanceOf(TemporaryData::class, $response);
        $this->assertEquals(12345, $response->tempDataId);
        $this->assertEquals(new \DateTimeImmutable('2024-12-31T23:59:59Z'), $response->expiryDate);
    }
}