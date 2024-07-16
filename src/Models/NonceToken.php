<?php

namespace PdfReportClient\Models;

class NonceToken
{
    public $nonce;

    public function __construct($data)
    {
        $this->nonce = $data['nonce'];
    }
}