# Contributing

Contributions are welcome and will be fully credited.

We accept contributions via pull requests on GitHub. Before you start, please review these guidelines.

## Reporting bugs and requesting features

- Bugs: please open an issue using the bug report template. Include the package version, PHP version, Laravel version, and clear steps to reproduce.
- Feature requests and questions: please use [GitHub Discussions](../../discussions) rather than an issue.
- Security vulnerabilities: please do **not** open a public issue — see [SECURITY.md](SECURITY.md).

## Pull requests

- **One feature/fix per PR** — if you have multiple unrelated changes, please open separate PRs so each can be reviewed on its own merits.
- **Add tests** for any behavior you add or change. PRs that reduce test coverage without a good reason won't be merged.
- **Keep style consistent** — run `composer format` (Pint) before committing; CI will also auto-fix style issues on push where possible.
- **Static analysis must pass** — run `composer analyse` (PHPStan/Larastan) locally.
- **Update the docs** if you're changing public behavior (README, config comments, or an ADR under `docs/adr` if the change affects the execution architecture).
- You don't need to update `CHANGELOG.md` yourself — releases are tagged and the changelog is generated from merged PRs/commits.

### Local setup

```bash
git clone git@github.com:<your-fork>/laravel-jupyter-reports.git
cd laravel-jupyter-reports
composer install
```

Useful scripts (see `composer.json`):

```bash
composer test           # run the test suite (Pest)
composer test-coverage  # run the test suite with coverage
composer analyse         # run PHPStan/Larastan
composer format          # fix code style with Pint
```

### Commit messages

Write commit messages that explain *why* a change was made, not just what changed. There's no required commit format, but please keep individual commits focused and reviewable.

## Code of Conduct

Please note that this project is released with a [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you're expected to uphold it.

**Happy coding**!
