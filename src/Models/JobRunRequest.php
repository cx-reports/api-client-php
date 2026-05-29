<?php

namespace CxReports\Models;

class JobRunRequest implements \JsonSerializable
{
    public $params;
    public $data;

    public function __construct($data = [])
    {
        $this->params = $data['params'] ?? null;
        $this->data = $data['data'] ?? null;
    }

    public function jsonSerialize(): \stdClass
    {
        return (object) array_filter(get_object_vars($this), function ($v) {
            return $v !== null;
        });
    }
}
