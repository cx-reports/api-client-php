<?php

namespace CxReports\Models;

class Job
{
    public $id;
    public $name;
    public $description;
    public $code;
    public $reviewRequired;
    public $isActive;
    public $lastRunTime;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->code = $data['code'] ?? null;
        $this->reviewRequired = $data['reviewRequired'] ?? null;
        $this->isActive = $data['isActive'] ?? null;
        $this->lastRunTime = $data['lastRunTime'] ?? null;
    }
}