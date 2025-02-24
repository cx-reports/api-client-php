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
use CxReports\Models\ReportPDF;

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
        if($client == null){
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

    public function listReports($type, $workspace_id = null)
    {
        $url = $this->buildUrlWithWorkspace('reports?type=' . $type, $workspace_id );

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
        if(!empty($data)){
            $prepared_data = json_encode($data);
            $tmpData = $this->postTempData($prepared_data);
            $tmpDataId = $tmpData->id;
        } else {
            $prepared_data = null;
        }

        $ws = $workspace_id == null ? $this->default_workspace_id : $workspace_id;
        $nonce = $this->createNonceAuthToken()->nonce;

        $queryParams = [];
        if($prepared_params != null){
            $queryParams['params'] = $prepared_params;
        }
        if($prepared_data != null){
            $queryParams['data'] = $prepared_data;
        }
        if($nonce != null){
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
    
    private function processResponse($response){
        if($response->getStatusCode() != 200){
            throw new \Exception('Error While running the request');
        }
        return json_decode($response->getBody(), true);
    }

    private function buildUrlWithWorkspace($path, $workspace_id = null){
        $ws = $workspace_id == null ? $this->default_workspace_id : $workspace_id;
        return $this->url . '/api/v1/ws/' . $ws . '/' . $path;
    }

    private function buildUrl($path){
        return $this->url . '/api/v1/' . $path;
    }

    private function encodeReportPreviewParams($params){
        $prepared_params = [];
        if(!empty($params['params'])){
            $prepared_params['params'] = json_encode($params['params']);
        } else {
            $prepared_params['params'] = null;
        }
        if(!empty($params['data'])){
            $prepared_params['data'] = json_encode($params['data']);
        } else {
            $prepared_params['data'] = null;
        }
        if(!empty($params['nonce'])){
            $prepared_params['nonce'] = $params['nonce'];
        }
        if(!empty($params['tempDataId'])){
            $prepared_params['tempDataId'] = $params['tempDataId'];
        }
        if(!empty($params['timezone'])){
            $prepared_params['timezone'] = $params['timezone'];
        } else {
            $prepared_params['timezone'] = 'UTC';
        }

        return $prepared_params;
    }
}