# Laravel Jupyter Reports

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creightonfrance/laravel-jupyter-reports.svg?style=flat-square)](https://packagist.org/packages/creightonfrance/laravel-jupyter-reports)
[![GitHub Tests Action Status](https://github.com/creightonfrance/laravel-jupyter-reports/actions/workflows/run-tests.yml/badge.svg)](https://github.com/creightonfrance/laravel-jupyter-reports/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/creightonfrance/laravel-jupyter-reports/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/creightonfrance/laravel-jupyter-reports/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creightonfrance/laravel-jupyter-reports.svg?style=flat-square)](https://packagist.org/packages/creightonfrance/laravel-jupyter-reports)

Run parameterized Jupyter notebooks as Laravel report jobs and collect their output — CSV/TSV data exports or `nbconvert`-style document conversions (HTML, PDF, script, markdown) — behind a single, swappable execution strategy.

> **Status:** this package is under active development and not yet ready for production use. Public API and configuration are still subject to change. Design rationale for the notebook execution strategy is documented as an Architecture Decision Record under [`docs/adr`](docs/adr).

## Installation

You can install the package via composer:

```bash
composer require creightonfrance/laravel-jupyter-reports
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-jupyter-reports-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-jupyter-reports-config"
```

Optionally, you can publish the views using:

```bash
php artisan vendor:publish --tag="laravel-jupyter-reports-views"
```

## Usage

The notebook execution API is still being built out and isn't ready to document with a working example yet. See [`docs/adr`](docs/adr) for the current design direction and the [CHANGELOG](CHANGELOG.md) for what has actually shipped so far — this section will be filled in once the public API stabilizes.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Code of Conduct

Please review our [Code of Conduct](CODE_OF_CONDUCT.md) — it applies to all interactions in this project.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Creighton France](https://github.com/creightonfrance)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
