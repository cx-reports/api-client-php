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
        $client = new CxReportsClient("https://master.cx-reports.app", "72", "9Py6MdgPOvIcXRc0l7SW2O/IFzbthKL/qI/jMHhMvxU=");
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
        $this->assertEquals('Miscellaneous', $types[0]->name);
        $this->assertEquals('Invoice', $types[1]->name);

        $this->assertInstanceOf(ReportType::class, $types[0]);
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
        print_r($url);
        $this->assertEquals("https://example.com/preview", $url["preview_url"]);
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
