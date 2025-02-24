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

    public function listReports($type)
    {
        $url = $this->buildUrlWithWorkspace('reports?type=' . $type);

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

    public function downloadPdf($reportId, $savePath)
    {
        try {
            $url = $this->buildUrlWithWorkspace('reports/' . $reportId . '/pdf');
            $response = $this->client->get($url, ['sink' => $savePath]);
            return $response->getStatusCode() === 200;
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

    public function getReportPreviewURL($reportId, $params = [], $data = [])
    {
        if(!empty($params)){
            $prepared_params = json_encode($params);
        } else {
            $prepared_params = null;
        }

        $tempDataId = null;
        if(!empty($data)){
            $prepared_data = json_encode($data);
            $tmpData = $this->postTempData($prepared_data);
            $tmpDataId = $tmpData->id;
        } else {
            $prepared_data = null;
        }

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
        $url = $this->url . '/ws/' . $this->default_workspace_id . '/reports/' . $reportId . '/preview?' . http_build_query($queryParams);

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
}