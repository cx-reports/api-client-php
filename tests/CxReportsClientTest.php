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
    }

    // public function testDownloadPdf()
    // {
    //     $savePath = "/tmp/report.pdf";
    //     $result = $this->client->downloadPdf("1", $savePath);

    //     $this->assertTrue($result);
    //     $this->assertFileExists($savePath);

    //     // Clean up
    //     unlink($savePath);
    // }

    // public function testGetReportTypes()
    // {
    //     $types = $this->client->getReportTypes();
    //     $this->assertCount(2, $types);

    //     // $this->assertInstanceOf(ReportType::class, $types[0]);
    //     $this->assertEquals(1, $types[0]->id);
    //     $this->assertEquals("Invoice", $types[0]->name);
    //     $this->assertEquals("Invoice report", $types[0]->description);
    //     $this->assertEquals("INV", $types[0]->code);
    //     $this->assertEquals(1, $types[0]->defaultReportId);
    //     $this->assertEquals("Default Invoice", $types[0]->defaultReportName);

    //     // $this->assertInstanceOf(ReportType::class, $types[1]);
    //     $this->assertEquals(2, $types[1]->id);
    //     $this->assertEquals("Receipt", $types[1]->name);
    //     $this->assertEquals("Receipt report", $types[1]->description);
    //     $this->assertEquals("REC", $types[1]->code);
    //     $this->assertNull($types[1]->defaultReportId);
    //     $this->assertNull($types[1]->defaultReportName);
    // }

    // public function testGetWorkspaces()
    // {
    //     $workspaces = $this->client->getWorkspaces();
    //     $this->assertCount(2, $workspaces);

    //     // $this->assertInstanceOf(Workspace::class, $workspaces[0]);
    //     $this->assertEquals(1, $workspaces[0]->id);
    //     $this->assertEquals("Workspace 1", $workspaces[0]->name);
    //     $this->assertEquals("Description 1", $workspaces[0]->description);
    //     $this->assertEquals("WS1", $workspaces[0]->code);

    //     // $this->assertInstanceOf(Workspace::class, $workspaces[1]);
    //     $this->assertEquals(2, $workspaces[1]->id);
    //     $this->assertEquals("Workspace 2", $workspaces[1]->name);
    //     $this->assertEquals("Description 2", $workspaces[1]->description);
    //     $this->assertEquals("WS2", $workspaces[1]->code);
    // }

    // public function testCreateNonceAuthToken()
    // {
    //     $nonce = $this->client->createNonceAuthToken();
    //     // $this->assertInstanceOf(NonceToken::class, $nonce);
    //     $this->assertEquals("generated-nonce", $nonce->nonce);
    // }

    // public function testGetReportPreviewURL()
    // {
    //     $url = $this->client->getReportPreviewURL(
    //         "invoice",
    //         [],
    //         [],
    //         1,
    //         "generated-nonce"
    //     );
    //     $this->assertEquals("https://example.com/preview", $url["preview_url"]);
    // }

    // public function testPostTempData()
    // {
    //     $tempData = ["content" => json_encode(["key" => "value"])];
    //     $response = $this->client->postTempData($tempData);
    //     // $this->assertInstanceOf(TemporaryData::class, $response);
    //     $this->assertEquals(12345, $response->tempDataId);
    //     $this->assertEquals(
    //         new \DateTimeImmutable("2024-12-31T23:59:59Z"),
    //         $response->expiryDate
    //     );
    // }
}

?>
