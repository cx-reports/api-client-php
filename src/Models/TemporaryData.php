<?php

namespace PdfReportClient\Models;

class TemporaryData
{
    public $tempDataId;
    public $expiryDate;

    public function __construct($data)
    {
        $this->tempDataId = $data['tempDataId'];
        $this->expiryDate = new \DateTimeImmutable($data['expiryDate']);
    }
}