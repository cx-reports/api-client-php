<?php

namespace CxReports\Models;

class ReportPage
{
    public $id;
    public $name;
    public $type;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->type = $data['type'];
    }
}