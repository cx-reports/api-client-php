<?php

namespace CxReports\Models;

class ReportPDF
{
    public $fileName;
    public $pdf;

    public function __construct($data)
    {
        $this->fileName = $data['filename'];
        $this->pdf = $data['pdf'];
    }
}