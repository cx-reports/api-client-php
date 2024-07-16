<?php

namespace PdfReportClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PdfReportClient
{
    private $client;
    private $url;
    private $pat;

    public function __construct($url, $pat)
    {
        $this->url = $url;
        $this->pat = $pat;
        $this->client = new Client([
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
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function downloadPdf($reportId, $savePath)
    {
        try {
            $response = $this->client->get('/reports/' . $reportId . '/download', ['sink' => $savePath]);
            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}