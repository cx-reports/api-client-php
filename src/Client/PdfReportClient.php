<?php

namespace PdfReportClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PdfReportClient\Models\Report;
use PdfReportClient\Models\ReportType;
use PdfReportClient\Models\Workspace;
use PdfReportClient\Models\TemporaryData;
use PdfReportClient\Models\TemporaryDataCreate;
use PdfReportClient\Models\NonceToken;

class PdfReportClient
{
    private $client;
    private $url;
    private $pat;

    public function __construct($url, $pat, Client $client = null)
    {
        $this->url = $url;
        $this->pat = $pat;
        $this->client = $client ?: new Client([
            'base_uri' => $this->url,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->pat,
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function listReports()
    {
        try {
            $response = $this->client->get('/reports');
            $data = json_decode($response->getBody(), true);
            $reports = array_map(function ($reportData) {
                return new Report($reportData);
            }, $data);
            return $reports;
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function downloadPdf($reportId, $savePath)
    {
        try {
            $response = $this->client->get('/reports/' . $reportId . '/pdf', ['sink' => $savePath]);
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getReportTypes()
    {
        try {
            $response = $this->client->get('/report-types');
            $data = json_decode($response->getBody(), true);
            $types = array_map(function ($typeData) {
                return new ReportType($typeData);
            }, $data);
            return $types;
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getWorkspaces()
    {
        try {
            $response = $this->client->get('/workspaces');
            $data = json_decode($response->getBody(), true);
            $workspaces = array_map(function ($workspaceData) {
                return new Workspace($workspaceData);
            }, $data);
            return $workspaces;
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function createNonceAuthToken()
    {
        try {
            $response = $this->client->post('/auth/nonce');
            $data = json_decode($response->getBody(), true);
            return new NonceToken($data);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getReportPreviewURL($reportType, $params, $data, $tmpDataId, $nonce)
    {
        try {
            $response = $this->client->post('/reports/preview', [
                'json' => [
                    'reportType' => $reportType,
                    'params' => $params,
                    'data' => $data,
                    'tmpDataId' => $tmpDataId,
                    'nonce' => $nonce,
                ],
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function postTempData($data)
    {
        try {
            $response = $this->client->post('/reports/temp-data', [
                'json' => $data,
            ]);
            $responseData = json_decode($response->getBody(), true);
            return new TemporaryData($responseData);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}