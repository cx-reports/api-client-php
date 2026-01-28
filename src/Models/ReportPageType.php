<?php
namespace CxReports\Models;

enum ReportPageType: string
{
    case Page = 'page';
    case Subreport = 'subreport';
    case DocumentMerge = 'document-merge';
}