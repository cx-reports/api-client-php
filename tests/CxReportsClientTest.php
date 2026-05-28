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
        $url = getenv('CX_REPORTS_URL') ?: '';
        $workspace_id = getenv('CX_REPORTS_WORKSPACE_ID') ?: 0;
        $pat = getenv('CX_REPORTS_PAT') ?: '';

        if ($url === '' || $pat === '') {
            $this->markTestSkipped('Live test requires CX_REPORTS_URL, CX_REPORTS_WORKSPACE_ID, and CX_REPORTS_PAT to be set.');
        }

        $this->client = new CxReportsClient($url, $workspace_id, $pat);
    }

    public function testListReports()
    {
        $reports = $this->client->listReports("invoice");
        $this->assertIsArray($reports);
        $this->assertContainsOnlyInstancesOf(Report::class, $reports);
    }

    public function testDownloadPdf()
    {
        $reportId = getenv('CX_REPORTS_TEST_REPORT_ID') ?: '';
        if ($reportId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_REPORT_ID is not set.');
        }

        $response = $this->client->downloadPdf($reportId, ["params" => ["number" => 123]]);
        $this->assertNotEmpty($response->fileName);
        $this->assertNotEmpty($response->pdf);

        $savePath = sys_get_temp_dir() . '/' . $response->fileName;
        file_put_contents($savePath, $response->pdf);
        $this->assertFileExists($savePath);
        unlink($savePath);
    }

    public function testDownloadPdfFromAnotherWs()
    {
        $altReportId = getenv('CX_REPORTS_TEST_ALT_REPORT_ID') ?: '';
        $altWorkspaceId = getenv('CX_REPORTS_TEST_ALT_WORKSPACE_ID') ?: '';
        if ($altReportId === '' || $altWorkspaceId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_ALT_REPORT_ID and CX_REPORTS_TEST_ALT_WORKSPACE_ID must be set.');
        }

        $response = $this->client->downloadPdf($altReportId, [], $altWorkspaceId);
        $this->assertNotEmpty($response->fileName);
        $this->assertNotEmpty($response->pdf);

        $savePath = sys_get_temp_dir() . '/' . $response->fileName;
        file_put_contents($savePath, $response->pdf);
        $this->assertFileExists($savePath);
        unlink($savePath);
    }

    public function testGetReportTypes()
    {
        $types = $this->client->getReportTypes();
        $this->assertIsArray($types);
        $this->assertContainsOnlyInstancesOf(ReportType::class, $types);
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
        $reportId = getenv('CX_REPORTS_TEST_REPORT_ID') ?: '';
        if ($reportId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_REPORT_ID is not set.');
        }

        $baseUrl = rtrim(getenv('CX_REPORTS_URL'), '/');
        $workspaceId = getenv('CX_REPORTS_WORKSPACE_ID');

        $url = $this->client->getReportPreviewURL($reportId, ["params" => ["number" => 123]]);
        $this->assertStringContainsString("$baseUrl/ws/$workspaceId/reports/$reportId/preview", $url);
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
