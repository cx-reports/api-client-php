<?php

namespace CxReports\Models;

enum DocumentFileFormat: string
{
    case pdf = 'pdf';
    case docx = 'docx';
    case xlsx = 'xlsx';
    case pptx = 'pptx';
    case html = 'html';
}
