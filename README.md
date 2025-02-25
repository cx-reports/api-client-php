# CxReports PHP Library

## Overview
The `cx-reports/api-client` provides an easy-to-use interface for interacting with the CxReports API. This library allows developers to preview reports, download reports as PDFs, retrieve workspace information, and more.

## Features
- Authentication with API tokens
- Preview reports by ID
- Download reports as PDF
- Fetch workspaces
- Retrieve report types

## Installation
You can install the library via Composer:

```sh
composer require cx-reports/api-client
```

## Usage

### Initializing the Client

```php
require 'vendor/autoload.php';

use CxReports\Client\CxReportsClient;

$url = "";
$workspace_id = 0;
$pat = "";
$client = new CxReportsClient($url, $workspace_id, $pat);
```

### Downloading a Report as PDF

```php
$response = $client->downloadPdf("149", [], 26);
$response = $client->downloadPdf("149", [], 26);
```

### Fetching Workspaces

```php
$workspaces = $client->getWorkspaces();
```

### Fetching Report Types

```php
$reportTypes = $client->getReportTypes();
```

## Error Handling
The library provides built-in error handling:

```php
try {
    $report = $client->getReport("invalid-id");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## License
This library is licensed under the MIT License.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue on GitHub.

## Support
For any issues or questions, contact [support@cx-reports.com](mailto:support@cx-reports.com).
