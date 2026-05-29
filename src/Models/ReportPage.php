<?php

namespace CxReports\Models;

class ReportPage
{
    public $id;
    public $name;
    public $type;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->type = isset($data['type']) ? ReportPageType::from($data['type']) : null;
    }
}
