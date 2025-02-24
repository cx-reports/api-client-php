<?php

namespace CxReports\Models;

class TemporaryDataCreate
{
    public $content;
    public $expiryDate;

    public function __construct($data)
    {
        $this->content = json_decode($data['content']);
        $this->expiryDate = isset($data['expiryDate']) ? new \DateTimeImmutable($data['expiryDate']) : null;
    }
}