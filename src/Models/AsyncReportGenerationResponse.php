<?php

namespace CxReports\Models;

class AsyncReportGenerationResponse
{
    public $temporaryFileId;

    public function __construct($data)
    {
        $this->temporaryFileId = $data['temporaryFileId'] ?? null;
    }
}
