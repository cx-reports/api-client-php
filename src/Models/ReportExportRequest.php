<?php

namespace CxReports\Models;

class ReportExportRequest implements \JsonSerializable
{
    public $params;
    public $data;
    public $lang;
    public $timezone;
    public $format;
    public $includeAttachments;
    public $theme;
    public $template;

    public function __construct($data = [])
    {
        $this->params = $data['params'] ?? null;
        $this->data = $data['data'] ?? null;
        $this->lang = $data['lang'] ?? null;
        $this->timezone = $data['timezone'] ?? null;
        $this->format = $data['format'] ?? null;
        $this->includeAttachments = $data['includeAttachments'] ?? null;
        $this->theme = $data['theme'] ?? null;
        $this->template = $data['template'] ?? null;
    }

    public function jsonSerialize(): \stdClass
    {
        return (object) array_filter(get_object_vars($this), function ($v) {
            return $v !== null;
        });
    }
}
