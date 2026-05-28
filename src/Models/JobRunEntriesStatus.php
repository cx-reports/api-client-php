<?php

namespace CxReports\Models;

class JobRunEntriesStatus
{
    public $queued;
    public $review;
    public $completed;
    public $errors;

    public function __construct($data)
    {
        $this->queued = $data['queued'] ?? null;
        $this->review = $data['review'] ?? null;
        $this->completed = $data['completed'] ?? null;
        $this->errors = $data['errors'] ?? null;
    }
}
