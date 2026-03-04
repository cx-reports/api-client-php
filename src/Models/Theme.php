<?php

namespace CxReports\Models;

class Theme
{
    public $id;
    public $name;
    public $code;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'] ?? null;
        $this->code = $data['code'] ?? null;
    }
}
