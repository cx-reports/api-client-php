# Running tests

## Prerequisites

- PHP 8.1+ (the library uses native enums)
- [Composer](https://getcomposer.org/)

In GitHub Codespaces, the default Universal image does not include PHP. Pick a PHP-flavoured devcontainer (e.g. `mcr.microsoft.com/devcontainers/php:8.2`) or install PHP + Composer manually before running the commands below.

## Install dependencies

```bash
composer install
```

This pulls in `guzzlehttp/guzzle` and `phpunit/phpunit` (dev) and generates `vendor/autoload.php`.

## Run the full test suite

```bash
vendor/bin/phpunit --bootstrap vendor/autoload.php tests
```

## Run a single test file

```bash
vendor/bin/phpunit --bootstrap vendor/autoload.php tests/CxReportsClientTest.php
```

## Notes on the live test file

`tests/CxReportsClientTest.php` makes real HTTP calls against a CxReports instance. Its `setUp()` reads `CX_REPORTS_URL`, `CX_REPORTS_WORKSPACE_ID`, and `CX_REPORTS_PAT` from the environment. If any are unset, the live tests are **skipped** (not failed) so the offline suite stays green.

A starter `.env.example` is provided. Copy it and populate the values:

```bash
cp .env.example .env
# then edit .env with your URL, workspace id, and PAT
```

`.env` is gitignored, so credentials won't be committed.

Load the values into your shell before running the live tests:

```bash
set -a && source .env && set +a
vendor/bin/phpunit --bootstrap vendor/autoload.php tests
```

(`set -a` auto-exports every variable assigned while sourcing; `set +a` turns it back off.)

The other two files (`CxReportsClientImportTest.php`, `CxReportsEstablishSimpleTest.php`) are pure unit checks and run offline.
