# CxReports PHP Library

A PHP client for the [CxReports](https://cx-reports.com) API. Generate reports synchronously or asynchronously, manage themes and templates, drive job runs, and embed previews — all behind a small typed surface backed by Guzzle.

## Features

- Bearer-token authentication
- List reports, report types, report pages, themes, templates, workspaces
- Synchronous PDF download (query params or JSON body)
- Asynchronous export flow (start → poll status → download content) with multiple document formats: PDF, DOCX, XLSX, PPTX, HTML
- Job runs: start, get status, generate review documents, deliver entries
- Nonce-protected preview URLs for iframe embedding
- Temporary data upload for large parameter payloads
- Consistent error model — every method throws `\Exception` with the full server response body on failure

## Requirements

- PHP 8.1 or newer (the library uses native enums)
- [Composer](https://getcomposer.org/)

## Installation

```sh
composer require cx-reports/api-client
```

## Quickstart

```php
require 'vendor/autoload.php';

use CxReports\Client\CxReportsClient;

$client = new CxReportsClient(
    'https://your-tenant.cx-reports.app',
    'your-workspace-id-or-code',
    'your-personal-access-token'
);
```

The third argument is a Personal Access Token. Generate one from your CxReports account settings.

## Usage

### List reports

```php
use CxReports\Models\Report;

$reports = $client->listReports('invoice');
foreach ($reports as $report) {
    echo $report->name . "\n";   // $report is a Report instance
}
```

### Download a PDF (GET — small payloads via query string)

```php
$result = $client->downloadPdf('497', [
    'params' => ['number' => 123],
    'timezone' => 'Europe/Belgrade',
]);

file_put_contents($result->fileName, $result->pdf);
```

You can also target a different workspace per call:

```php
$result = $client->downloadPdf('149', [], 'another-workspace');
```

### Download a PDF (POST — large or sensitive payloads)

```php
use CxReports\Models\ReportExportRequest;

$body = new ReportExportRequest([
    'params' => ['number' => 123],
    'data'   => $largeArrayOrObject,
    'theme'  => 'corporate',
]);

$result = $client->downloadPdfWithData('497', $body);
```

### Asynchronous export

For long-running reports or non-PDF formats, kick off an async generation, poll status, then download the file:

```php
use CxReports\Models\AsyncReportGenerationRequest;
use CxReports\Models\DocumentFileFormat;

$start = $client->startExport('497', new AsyncReportGenerationRequest([
    'params' => ['number' => 123],
    'format' => DocumentFileFormat::xlsx,
]));

$tempFileId = $start->temporaryFileId;

do {
    sleep(1);
    $status = $client->getAsyncExportStatus($tempFileId);
    if ($status->status === 'Failed') {
        throw new \RuntimeException($status->errorMessage ?? 'Async export failed');
    }
} while (!$status->isReady);

$file = $client->getAsyncExportContent($tempFileId);
file_put_contents($file->fileName, $file->pdf);
```

### Listing report pages

```php
use CxReports\Models\ReportPageType;

$pages = $client->listReportPages('497');
foreach ($pages as $page) {
    if ($page->type === ReportPageType::Subreport) {
        // handle subreport
    }
}
```

### Embedded preview URLs

```php
$url = $client->getReportPreviewURL('497', [
    'params' => ['number' => 123],
    'data'   => $jsonPayload,     // converted to a tempDataId automatically
]);

// drop $url into an <iframe>
```

### Themes and templates

```php
$themes    = $client->getThemes();      // ReportThemeListItem[]
$templates = $client->getTemplates();   // ReportTemplate[]
```

### Job runs

```php
use CxReports\Models\JobRunRequest;

$jobs = $client->listJobs();

$run = $client->startNewJobRun('daily-invoices', new JobRunRequest([
    'params' => ['date' => '2026-05-29'],
]));

$status = $client->getJobRunStatus('daily-invoices', $run->jobRunId);

if ($status->finished) {
    $review = $client->generateJobRunReviewDocument('daily-invoices', $run->jobRunId);
    $client->deliverAllJobRunEntries('daily-invoices', $run->jobRunId);
}
```

### Workspaces

```php
$workspaces = $client->getWorkspaces();
```

## Error handling

Every client method throws `\Exception` on failure. The message includes the HTTP error and — when present — the full unredacted server response body, which is invaluable for debugging validation errors:

```php
try {
    $client->downloadPdf('does-not-exist');
} catch (\Exception $e) {
    error_log($e->getMessage());
    // "Error downloading PDF: Client error: ... 404 Not Found
    //  Response body: {"type":"...","title":"Report not found",...}"
}
```

## Tests

See [TESTS.md](TESTS.md). The test suite includes one offline unit check and a live integration set that is gated on environment variables — copy `.env.example` to `.env` and fill in your tenant before running.

## License

MIT — see [LICENSE](LICENSE).

## Support

Open an issue on GitHub or email [support@cx-reports.com](mailto:support@cx-reports.com).
