<?php

namespace CxReports\Models;

class TemporaryFileStatusResponse
{
    public $id;
    public $status;
    public $isReady;
    public $errorMessage;
    public $expiryTime;
    public $name;
    public $contentSize;
    public $contentType;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->isReady = $data['isReady'] ?? null;
        $this->errorMessage = $data['errorMessage'] ?? null;
        $this->expiryTime = $data['expiryTime'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->contentSize = $data['contentSize'] ?? null;
        $this->contentType = $data['contentType'] ?? null;
    }
}
