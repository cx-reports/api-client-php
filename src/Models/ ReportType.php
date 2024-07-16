<?php

namespace PdfReportClient\Models;

class ReportType
{
    public $id;
    public $name;
    public $description;
    public $code;
    public $defaultReportId;
    public $defaultReportName;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->code = $data['code'];
        $this->defaultReportId = $data['defaultReportId'] ?? null;
        $this->defaultReportName = $data['defaultReportName'] ?? null;
    }
}