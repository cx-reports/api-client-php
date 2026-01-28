<?php

namespace CxReports\Models;

class JobRunRequest
{
    public $params;
    public $data;

    public function __construct($data = [])
    {
        $this->params = $data['params'] ?? null;
        $this->data = $data['data'] ?? null;
    }
}