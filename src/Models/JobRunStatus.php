<?php

namespace CxReports\Models;

class JobRunStatus
{
    public $finished;
    public $entries;
    public $status;

    public function __construct($data)
    {
        $this->finished = $data['finished'];
        $this->entries = $data['entries'];
        $this->status = new JobRunEntriesStatus($data['status']);
    }
}
