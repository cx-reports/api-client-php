<?php

namespace PdfReportClient\Models;

class Workspace
{
    public $id;
    public $name;
    public $description;
    public $code;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->code = $data['code'];
    }
}