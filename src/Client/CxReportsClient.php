<?php

namespace CxReports\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use CxReports\Models\Report;
use CxReports\Models\ReportType;
use CxReports\Models\Workspace;
use CxReports\Models\TemporaryData;
use CxReports\Models\TemporaryDataCreate;
use CxReports\Models\NonceToken;
use CxReports\Models\ReportExportRequest;
use CxReports\Models\ReportPage;
use CxReports\Models\ReportPDF;
use CxReports\Models\AsyncReportGenerationRequest;
use CxReports\Models\AsyncReportGenerationResponse;
use CxReports\Models\DocumentFileFormat;
use CxReports\Models\Job;
use CxReports\Models\JobRun;
use CxReports\Models\JobRunRequest;
use CxReports\Models\JobRunStatus;
use CxReports\Models\TemporaryFileStatusResponse;
use CxReports\Models\ReportThemeListItem;
use CxReports\Models\ReportTemplate;

class CxReportsClient
{
    private $client;
    private $url;
    private $pat;
    private $default_workspace_id;

    public function __construct($url, $workspace_id, $pat, Client $client = null)
    {
        $this->url = $url;
        $this->pat = $pat;
        $this->default_workspace_id = $workspace_id;
        if ($client == null) {
            $this->client = new Client([
                'base_uri' => $this->url,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->pat,
                    'Accept' => 'application/json',
                ],
            ]);
        } else {
            $this->client = $client;
        }
    }

    public function getAsyncExportStatus($tempFileId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('exports/' . $tempFileId . '/status', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return new TemporaryFileStatusResponse($data);
        } catch (RequestException $e) {
            throw new \Exception('Error getting export status: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function getAsyncExportContent($tempFileId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('exports/' . $tempFileId . '/content', $workspace_id);
        try {
            $response = $this->client->get($url);
            $reportName = $this->extractFilenameFromContentDisposition($response);
            $pdf = $response->getBody()->getContents();
            return new ReportPDF([
                'filename' => $reportName,
                'pdf' => $pdf,
            ]);
        } catch (RequestException $e) {
            throw new \Exception('Error downloading export content: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function listJobs($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs', $workspace_id);

        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);

            return array_map(function ($jobData) {
                return new Job($jobData);
            }, $data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching jobs: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function startNewJobRun($jobIdOrCode, JobRunRequest $requestBody, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobIdOrCode . '/runs', $workspace_id);
        try {
            $response = $this->client->post($url, [
                'json' => $requestBody,
            ]);
            $data = $this->processResponse($response);
            return new JobRun($data);
        } catch (RequestException $e) {
            throw new \Exception('Error starting new Job Run: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function getJobRunStatus($jobIdOrCode, $jobRunId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobIdOrCode . '/runs/' . $jobRunId . '/status', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return new JobRunStatus($data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching JobRun status: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function generateJobRunReviewDocument($jobIdOrCode, $jobRunId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobIdOrCode . '/runs/' . $jobRunId . '/generate-review-document', $workspace_id);
        try {
            $response = $this->client->post($url);
            $data = $this->processResponse($response);
            return new AsyncReportGenerationResponse($data);
        } catch (RequestException $e) {
            throw new \Exception('Error generating Job Run review document: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function deliverAllJobRunEntries($jobIdOrCode, $jobRunId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobIdOrCode . '/runs/' . $jobRunId . '/deliver', $workspace_id);
        try {
            $this->client->post($url);
            return true;
        } catch (RequestException $e) {
            throw new \Exception('Error delivering job run entries: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function listReports($type, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports?type=' . $type, $workspace_id);

        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);

            return array_map(function ($reportData) {
                return new Report($reportData);
            }, $data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching reports: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function listReportPages($reportIdOrTypeCode, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports/' . $reportIdOrTypeCode . '/pages', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);

            return array_map(function ($pageData) {
                return new ReportPage($pageData);
            }, $data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching report pages: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function downloadPdf($reportId, $params = [], $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports/' . $reportId . '/pdf', $workspace_id);
        $encodedParams = $this->encodeReportPreviewParams($params);
        try {
            $response = $this->client->get($url, [
                'query' => $encodedParams,
            ]);
            $reportName = $this->extractFilenameFromContentDisposition($response);
            $pdf = $response->getBody()->getContents();
            return new ReportPDF([
                'filename' => $reportName,
                'pdf' => $pdf,
            ]);
        } catch (RequestException $e) {
            throw new \Exception('Error downloading PDF: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function downloadPdfWithData($reportId, ReportExportRequest $requestBody, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports/' . $reportId . '/pdf', $workspace_id);

        try {
            $response = $this->client->post($url, [
                'json' => $requestBody,
            ]);
            $reportName = $this->extractFilenameFromContentDisposition($response);
            $pdf = $response->getBody()->getContents();
            return new ReportPDF([
                'filename' => $reportName,
                'pdf' => $pdf,
            ]);
        } catch (RequestException $e) {
            throw new \Exception('Error downloading document: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function startExport($reportId, AsyncReportGenerationRequest $requestBody, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports/' . $reportId . '/export', $workspace_id);
        try {
            $response = $this->client->post($url, [
                'json' => $requestBody,
            ]);
            $exportResponse = $this->processResponse($response);
            return new AsyncReportGenerationResponse($exportResponse);
        } catch (RequestException $e) {
            throw new \Exception('Failed to start async export: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function getReportTypes($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('report-types', $workspace_id);
        try {
            $response = $this->client->get($url);
            $types = $this->processResponse($response);
            return array_map(function ($typeData) {
                return new ReportType($typeData);
            }, $types);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching report types: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function getThemes($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('themes', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return array_map(function ($themeData) {
                return new ReportThemeListItem($themeData);
            }, $data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching themes: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function getTemplates($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('templates', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return array_map(function ($templateData) {
                return new ReportTemplate($templateData);
            }, $data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching templates: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function getWorkspaces()
    {
        $url = $this->buildUrl('workspaces');
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return array_map(function ($workspaceData) {
                return new Workspace($workspaceData);
            }, $data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching workspaces: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function createNonceAuthToken()
    {
        $url = $this->buildUrl('nonce-tokens');
        try {
            $response = $this->client->post($url);
            $data = $this->processResponse($response);
            return new NonceToken($data);
        } catch (RequestException $e) {
            throw new \Exception('Error fetching nonce token: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    public function getReportPreviewURL($reportId, $params = [], $workspace_id = null)
    {
        $ws = $workspace_id == null ? $this->default_workspace_id : $workspace_id;

        if (!empty($params['data'])) {
            $tmpData = $this->postTempData(json_encode($params['data']), $workspace_id);
            $params['tempDataId'] = $tmpData->tempDataId;
            unset($params['data']);
        }

        $params['nonce'] = $this->createNonceAuthToken()->nonce;

        $queryParams = array_filter(
            $this->encodeReportPreviewParams($params),
            function ($v) {
                return $v !== null;
            }
        );

        return $this->url . '/ws/' . $ws . '/reports/' . $reportId . '/preview?' . http_build_query($queryParams);
    }

    public function postTempData($data, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('temporary-data', $workspace_id);
        $body = json_encode(['content' => $data]);
        try {
            $response = $this->client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $body,
            ]);
            return new TemporaryData(json_decode($response->getBody(), true));
        } catch (RequestException $e) {
            throw new \Exception('Error posting temporary data: ' . $e->getMessage() . $this->responseBodySuffix($e));
        }
    }

    private function processResponse($response)
    {
        if ($response->getStatusCode() != 200 && $response->getStatusCode() != 202) {
            throw new \Exception('Error While running the request');
        }
        return json_decode($response->getBody(), true);
    }

    private function buildUrlWithWorkspace($path, $workspace_id = null)
    {
        $ws = $workspace_id == null ? $this->default_workspace_id : $workspace_id;
        return $this->url . '/api/v1/ws/' . $ws . '/' . $path;
    }

    private function buildUrl($path)
    {
        return $this->url . '/api/v1/' . $path;
    }

    private function responseBodySuffix(RequestException $e)
    {
        if (!$e->hasResponse()) {
            return '';
        }
        $body = (string) $e->getResponse()->getBody();
        return $body === '' ? '' : "\nResponse body: " . $body;
    }

    private function extractFilenameFromContentDisposition($response, $fallback = 'download')
    {
        $headers = $response->getHeader('Content-Disposition');
        if (empty($headers)) {
            return $fallback;
        }
        $header = $headers[0];

        // RFC 5987 encoded form: filename*=UTF-8''<percent-encoded>
        if (preg_match("/filename\*\s*=\s*UTF-8''([^;]+)/i", $header, $m)) {
            $name = urldecode(trim($m[1], '"'));
            return str_replace(' ', '_', $name);
        }

        // Plain form: filename="..."
        if (preg_match('/filename\s*=\s*"?([^";]+)"?/i', $header, $m)) {
            return str_replace(' ', '_', trim($m[1], '"'));
        }

        return $fallback;
    }

    private function encodeReportPreviewParams($params)
    {
        $prepared_params = [];
        if (!empty($params['params'])) {
            $prepared_params['params'] = json_encode($params['params']);
        } else {
            $prepared_params['params'] = null;
        }
        if (!empty($params['data'])) {
            $prepared_params['data'] = json_encode($params['data']);
        } else {
            $prepared_params['data'] = null;
        }
        if (!empty($params['nonce'])) {
            $prepared_params['nonce'] = $params['nonce'];
        }
        if (!empty($params['tempDataId'])) {
            $prepared_params['tempDataId'] = $params['tempDataId'];
        }
        if (!empty($params['timezone'])) {
            $prepared_params['timezone'] = $params['timezone'];
        } else {
            $prepared_params['timezone'] = 'UTC';
        }
        if (isset($params['format']) && $params['format'] !== '') {
            $prepared_params['format'] = $params['format'] instanceof DocumentFileFormat
                ? $params['format']->value
                : $params['format'];
        }
        if (isset($params['includeAttachments'])) {
            $prepared_params['includeAttachments'] = $params['includeAttachments'] ? 'true' : 'false';
        }
        if (!empty($params['lang'])) {
            $prepared_params['lang'] = $params['lang'];
        }
        if (!empty($params['theme'])) {
            $prepared_params['theme'] = $params['theme'];
        }
        if (!empty($params['template'])) {
            $prepared_params['template'] = $params['template'];
        }
        return $prepared_params;
    }
}
