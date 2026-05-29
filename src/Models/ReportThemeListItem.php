<?php

namespace CxReports\Models;

class ReportThemeListItem
{
    public $id;
    public $name;
    public $code;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->code = $data['code'] ?? null;
    }
}
