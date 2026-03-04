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
use CxReports\Models\DocumentFIleFormat;
use CxReports\Models\Job;
use CxReports\Models\JobRun;
use CxReports\Models\JobRunRequest;
use CxReports\Models\JobRunStatus;
use CxReports\Models\TemporaryFileStatusResponse;
use CxReports\Models\Theme;
use CxReports\Models\Template;


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
            return new \Exception('Error getting export status');
        }
    }

    public function getAsyncExportContent($tempFileId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('exports/' . $tempFileId . '/content', $workspace_id);
        try {
            $response = $this->client->get($url);
            $reportName = $response->getHeader('Content-Disposition')[0];
            //filename="Sample report.pdf"; filename*=UTF-8''Sample%20report.pdf....... 
            // extract the filename from the header
            $reportName = explode('filename*=UTF-8\'\'', $reportName)[1];
            $reportName = str_replace('"', '', $reportName);
            $reportName = str_replace(' ', '_', $reportName);
            $reportName = urldecode($reportName);
            $pdf = $response->getBody()->getContents();
            return new ReportPDF([
                'filename' => $reportName,
                'pdf' => $pdf,
            ]);
        } catch (RequestException $e) {
            return new \Exception('Error fetching report pages');
        }
    }

    public function listJobs($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs', $workspace_id);

        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);

            $jobs = array_map(function ($jobData) {
                return new Job($jobData);
            }, $data);
            return $jobs;
        } catch (RequestException $e) {
            return new \Exception('Error fetching jobs');
        }
    }

    public function startNewJobRun($jobid, JobRunRequest $requestBody, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobid . '/runs', $workspace_id);
        try {
            $response = $this->client->post($url, [
                'json' => $requestBody
            ]);
            $data = $this->processResponse($response);
            return new JobRun($data);
        } catch (RequestException $e) {
            return new \Exception('Error starting new Job Run');
        }
    }

    public function getJobRunStatus($jobid, $runId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobid . '/runs/' . $runId . '/status', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return new JobRunStatus($data);
        } catch (RequestException $e) {
            return new \Exception('Error fetching JobRun status');
        }
    }

    public function generateJobRunReviewDocument($jobid, $runId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobid . '/runs/' . $runId . '/generate-review-document', $workspace_id);
        try {
            $response = $this->client->post($url);
            $data = $this->processResponse($response);
            return new AsyncReportGenerationResponse($data);
        } catch (RequestException $e) {
            return new \Exception('Error generating Job Run review document');
        }
    }

    public function deliverAllJobRunEntries($jobid, $runId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('jobs/' . $jobid . '/runs/' . $runId . '/deliver', $workspace_id);
        try {
            $response = $this->client->post($url);
            return true;
        } catch (RequestException $e) {
            return new \Exception('Error generating Job Run review document');
        }
    }

    public function listReports($type, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports?type=' . $type, $workspace_id);

        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);

            $reports = array_map(function ($reportData) {
                return new Report($reportData);
            }, $data);
            return $reports;
        } catch (RequestException $e) {
            return new \Exception('Error fetching reports');
        }
    }

    public function listReportPages($reportId, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports/' . $reportId . '/pages', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);

            $reportPages = array_map(function ($pageData) {
                return new ReportPage($pageData);
            }, $data);
            return $reportPages;
        } catch (RequestException $e) {
            return new \Exception('Error fetching report pages');
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
            $body = $response->getBody();
            $reportName = $response->getHeader('Content-Disposition')[0];
            //filename="Sample report.pdf"; filename*=UTF-8''Sample%20report.pdf....... 
            // extract the filename from the header
            $reportName = explode('filename*=UTF-8\'\'', $reportName)[1];
            $reportName = str_replace('"', '', $reportName);
            $reportName = str_replace(' ', '_', $reportName);
            $reportName = urldecode($reportName);
            $pdf = $response->getBody()->getContents();
            return new ReportPDF([
                'filename' => $reportName,
                'pdf' => $pdf,
            ]);
        } catch (RequestException $e) {
            return new \Exception('Error fetching reports');
        }
    }


    public function downloadPdfWithData($reportId, ReportExportRequest $requestBody, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports/' . $reportId . '/pdf', $workspace_id);

        try {
            $response = $this->client->post($url, [
                'json' => $requestBody,
            ]);

            $reportName = $response->getHeader('Content-Disposition')[0];
            // filename="Sample report.pdf"; filename*=UTF-8''Sample%20report.pdf....... 
            // extract the filename from the header
            $reportName = explode('filename*=UTF-8\'\'', $reportName)[1];
            $reportName = str_replace('"', '', $reportName);
            $reportName = str_replace(' ', '_', $reportName);
            $reportName = urldecode($reportName);

            $pdf = $response->getBody()->getContents();

            return new ReportPDF([
                'filename' => $reportName,
                'pdf' => $pdf,
            ]);
        } catch (RequestException $e) {
            throw new \Exception('Error downloading document: ' . $e->getMessage());
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
        } catch (RequestException $th) {
            throw new \Exception('Failed to start async export: ' . $th->getMessage());
        }
    }



    public function getReportTypes($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('report-types', $workspace_id);
        try {
            $response = $this->client->get($url);
            $types = $this->processResponse($response);
            $types = array_map(function ($typeData) {
                return new ReportType($typeData);
            }, $types);
            return $types;
        } catch (RequestException $e) {
            return new \Exception('Error fetching report types');
        }
    }

    public function getThemes($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('themes', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return array_map(function ($themeData) {
                return new Theme($themeData);
            }, $data);
        } catch (RequestException $e) {
            return new \Exception('Error fetching themes');
        }
    }

    public function getTemplates($workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('templates', $workspace_id);
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            return array_map(function ($templateData) {
                return new Template($templateData);
            }, $data);
        } catch (RequestException $e) {
            return new \Exception('Error fetching templates');
        }
    }

    public function getWorkspaces()
    {
        $url = $this->buildUrl('workspaces');
        try {
            $response = $this->client->get($url);
            $data = $this->processResponse($response);
            $workspaces = array_map(function ($workspaceData) {
                return new Workspace($workspaceData);
            }, $data);
            return $workspaces;
        } catch (RequestException $e) {
            return new \Exception('Error fetching workspaces');
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
            return new \Exception('Error fetching nonce token');
        }
    }

    public function getReportPreviewURL($reportId, $params = [], $workspace_id = null)
    {
        $prepared_params = $this->encodeReportPreviewParams($params);

        $tempDataId = null;
        if (!empty($data)) {
            $prepared_data = json_encode($data);
            $tmpData = $this->postTempData($prepared_data);
            $tmpDataId = $tmpData->tempDataId;
        } else {
            $prepared_data = null;
        }

        $ws = $workspace_id == null ? $this->default_workspace_id : $workspace_id;
        $nonce = $this->createNonceAuthToken()->nonce;

        $queryParams = [];
        if ($prepared_params != null) {
            $queryParams['params'] = $prepared_params;
        }
        if ($prepared_data != null) {
            $queryParams['data'] = $prepared_data;
        }
        if ($nonce != null) {
            $queryParams['nonce'] = $nonce;
        }
        $url = $this->url . '/ws/' . $ws . '/reports/' . $reportId . '/preview?' . http_build_query($queryParams);

        return $url;
    }

    public function postTempData($data, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('temporary-data', $workspace_id);
        // body should be a json, with key 'content' and value as the data
        $body = json_encode(['content' => $data]);
        try {
            $response = $this->client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $body,
            ]);
            return new TemporaryData(json_decode($response->getBody(), true));
        } catch (RequestException $e) {
            return new \Exception('Error posting temporary data');
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
        if (!empty($params['format'])) {
            $prepared_params['format'] = $params['format'];
        } else {
            $prepared_params['format'] = DocumentFIleFormat::pdf;
        }
        if (!empty($params['includeAttachments'])) {
            $prepared_params['includeAttachments'] = $params['includeAttachments'];
        } else {
            $prepared_params['includeAttachments'] = false;
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
