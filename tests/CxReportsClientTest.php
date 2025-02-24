<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use CxReports\Client\CxReportsClient;
use CxReports\Models\Report;
use CxReports\Models\ReportType;
use CxReports\Models\Workspace;
use CxReports\Models\NonceToken;
use CxReports\Models\TemporaryData;

class CxReportsClientTest extends TestCase
{
    private $client;

    protected function setUp(): void
    {
        $url = "";
        $workspace_id = 0;
        $pat = "";
        $client = new CxReportsClient($url, $workspace_id, $pat);
        $this->client = $client;
    }

    public function testListReports()
    {
        $reports = $this->client->listReports("invoice");
        $this->assertCount(1, $reports);
        $this->assertEquals('Invoice', $reports[0]->name);

        $this->assertInstanceOf(Report::class, $reports[0]);
    }

    public function testDownloadPdf()
    {
        $savePath = "./tmp/report.pdf";
        $result = $this->client->downloadPdf("496", $savePath);

        $this->assertTrue($result);
        $this->assertFileExists($savePath);

        // Clean up
        unlink($savePath);
    }

    public function testGetReportTypes()
    {
        $types = $this->client->getReportTypes();
        $this->assertCount(2, $types);
        $this->assertContainsOnlyInstancesOf(ReportType::class, $types);

        $names = array_map(function ($type) {
            return $type->name;
        }, $types);
        
        $this->assertContains('Miscellaneous', $names);
        $this->assertContains('Invoice', $names);
    }

    public function testGetWorkspaces()
    {
        $workspaces = $this->client->getWorkspaces();
        $count = count($workspaces);
        $this->assertGreaterThan(0, $count);
        $this->assertInstanceOf(Workspace::class, $workspaces[0]);

    }

    public function testCreateNonceAuthToken()
    {
        $nonce = $this->client->createNonceAuthToken();
        $this->assertInstanceOf(NonceToken::class, $nonce);
    }

    public function testGetReportPreviewURL()
    {
        $url = $this->client->getReportPreviewURL(497, ["number" => 123]);
        $this->assertStringContainsString("https://master.cx-reports.app/ws/72/reports/497/preview", $url);
    }

    public function testPostTempData()
    {
        $tempData = json_encode(["key" => "value"]);
        $response = $this->client->postTempData($tempData);
        $this->assertInstanceOf(TemporaryData::class, $response);
        $this->assertNotEmpty($response->tempDataId);
    }
}

?>
