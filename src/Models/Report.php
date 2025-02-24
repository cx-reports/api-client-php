<?php

namespace CxReports\Models;

class Report
{
    public $id;
    public $name;
    public $reportTypeId;
    public $reportTypeName;
    public $reportTemplateName;
    public $previewImage;
    public $themeName;
    public $isDefault;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->reportTypeId = $data['reportTypeId'];
        $this->reportTypeName = $data['reportTypeName'];
        $this->reportTemplateName = $data['reportTemplateName'];
        $this->previewImage = $data['previewImage'] ?? null;
        $this->themeName = $data['themeName'] ?? null;
        $this->isDefault = $data['isDefault'];
    }
}