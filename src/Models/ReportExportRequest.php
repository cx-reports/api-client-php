<?php

namespace CxReports\Models;

class ReportExportRequest
{
    public $params;
    public $data;
    public $lang;
    public $timezone;
    public $format;
    public $includeAttachments;

    public function __construct($data = [])
    {
        $this->params = $data['params'] ?? null;
        $this->data = $data['data'] ?? null;
        $this->lang = $data['lang'] ?? null;
        $this->timezone = $data['timezone'] ?? null;
        $this->format = $data['format'] ?? null;
        $this->includeAttachments = $data['includeAttachments'] ?? null;
    }
}