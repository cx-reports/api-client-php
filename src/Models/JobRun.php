<?php

namespace CxReports\Models;

class JobRun
{
    public $jobRunId;

    public function __construct($data)
    {
        $this->jobRunId = $data['jobRunId'] ?? null;
    }
}
