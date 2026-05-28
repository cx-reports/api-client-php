<?php

namespace CxReports\Models;

class AsyncReportGenerationRequest
{
    public $params;
    public $data;
    public $lang;
    public $timezone;
    public $format;
    public $includeAttachments;
    public $excludePages;
    public $tempDataId;
    public $theme;
    public $template;

    public function __construct($data = [])
    {
        $this->params = $data['params'] ?? null;
        $this->data = $data['data'] ?? null;
        $this->lang = $data['lang'] ?? null;
        $this->timezone = $data['timezone'] ?? null;
        $this->format = $data['format'] ?? null;
        $this->includeAttachments = $data['includeAttachments'] ?? null;
        $this->excludePages = $data['excludePages'] ?? null;
        $this->tempDataId = $data['tempDataId'] ?? null;
        $this->theme = $data['theme'] ?? null;
        $this->template = $data['template'] ?? null;
    }
}
