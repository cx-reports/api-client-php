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
use CxReports\Models\Job;
use CxReports\Models\JobRun;
use CxReports\Models\JobRunRequest;
use CxReports\Models\JobRunStatus;
use CxReports\Models\ReportPage;
use CxReports\Models\ReportPDF;
use CxReports\Models\ReportExportRequest;
use CxReports\Models\ReportTemplate;
use CxReports\Models\ReportThemeListItem;
use CxReports\Models\AsyncReportGenerationRequest;
use CxReports\Models\AsyncReportGenerationResponse;
use CxReports\Models\TemporaryFileStatusResponse;

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

    public function testListJobs()
    {
        $jobs = $this->client->listJobs();
        $this->assertIsArray($jobs);
        $this->assertContainsOnlyInstancesOf(Job::class, $jobs);
    }

    public function testGetThemes()
    {
        $themes = $this->client->getThemes();
        $this->assertIsArray($themes);
        $this->assertContainsOnlyInstancesOf(ReportThemeListItem::class, $themes);
    }

    public function testGetTemplates()
    {
        $templates = $this->client->getTemplates();
        $this->assertIsArray($templates);
        $this->assertContainsOnlyInstancesOf(ReportTemplate::class, $templates);
    }

    public function testListReportPages()
    {
        $reportId = getenv('CX_REPORTS_TEST_REPORT_ID') ?: '';
        if ($reportId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_REPORT_ID is not set.');
        }

        $pages = $this->client->listReportPages($reportId);
        $this->assertIsArray($pages);
        $this->assertContainsOnlyInstancesOf(ReportPage::class, $pages);
    }

    public function testDownloadPdfWithData()
    {
        $reportId = getenv('CX_REPORTS_TEST_REPORT_ID') ?: '';
        if ($reportId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_REPORT_ID is not set.');
        }

        $body = new ReportExportRequest([
            'params' => ['number' => 123],
        ]);

        $response = $this->client->downloadPdfWithData($reportId, $body);
        $this->assertInstanceOf(ReportPDF::class, $response);
        $this->assertNotEmpty($response->fileName);
        $this->assertNotEmpty($response->pdf);
    }

    public function testAsyncExportFlow()
    {
        $reportId = getenv('CX_REPORTS_TEST_REPORT_ID') ?: '';
        if ($reportId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_REPORT_ID is not set.');
        }

        $body = new AsyncReportGenerationRequest([
            'params' => ['number' => 123],
        ]);

        // 1. Kick off the async generation
        $start = $this->client->startExport($reportId, $body);
        $this->assertInstanceOf(AsyncReportGenerationResponse::class, $start);
        $this->assertNotEmpty($start->temporaryFileId);

        // 2. Poll status until ready (or fail) with a 30s budget
        $tempFileId = $start->temporaryFileId;
        $deadline = time() + 30;
        $status = null;
        do {
            $status = $this->client->getAsyncExportStatus($tempFileId);
            $this->assertInstanceOf(TemporaryFileStatusResponse::class, $status);
            if ($status->status === 'Failed') {
                $this->fail('Async export failed: ' . ($status->errorMessage ?? 'unknown'));
            }
            if ($status->isReady) {
                break;
            }
            sleep(1);
        } while (time() < $deadline);

        $this->assertTrue((bool) $status->isReady, 'Export did not become ready within 30s');

        // 3. Download the produced file
        $content = $this->client->getAsyncExportContent($tempFileId);
        $this->assertInstanceOf(ReportPDF::class, $content);
        $this->assertNotEmpty($content->fileName);
        $this->assertNotEmpty($content->pdf);
    }

    public function testStartNewJobRunAndStatus()
    {
        $jobId = getenv('CX_REPORTS_TEST_JOB_ID') ?: '';
        if ($jobId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_JOB_ID is not set.');
        }

        $run = $this->client->startNewJobRun($jobId, new JobRunRequest([]));
        $this->assertInstanceOf(JobRun::class, $run);
        $this->assertNotEmpty($run->jobRunId);

        $status = $this->client->getJobRunStatus($jobId, $run->jobRunId);
        $this->assertInstanceOf(JobRunStatus::class, $status);
    }

    public function testGenerateJobRunReviewDocument()
    {
        $jobId = getenv('CX_REPORTS_TEST_JOB_ID') ?: '';
        $jobRunId = getenv('CX_REPORTS_TEST_JOB_RUN_ID') ?: '';
        if ($jobId === '' || $jobRunId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_JOB_ID and CX_REPORTS_TEST_JOB_RUN_ID must be set, pointing at a review-required job run.');
        }

        $resp = $this->client->generateJobRunReviewDocument($jobId, $jobRunId);
        $this->assertInstanceOf(AsyncReportGenerationResponse::class, $resp);
        $this->assertNotEmpty($resp->temporaryFileId);
    }

    public function testDeliverAllJobRunEntries()
    {
        // NOTE: this endpoint actually delivers entries (sends emails, etc.).
        // Only set these env vars in a test/staging tenant.
        $jobId = getenv('CX_REPORTS_TEST_JOB_ID') ?: '';
        $jobRunId = getenv('CX_REPORTS_TEST_JOB_RUN_ID') ?: '';
        if ($jobId === '' || $jobRunId === '') {
            $this->markTestSkipped('CX_REPORTS_TEST_JOB_ID and CX_REPORTS_TEST_JOB_RUN_ID must be set.');
        }

        $this->assertTrue($this->client->deliverAllJobRunEntries($jobId, $jobRunId));
    }
}

?>
