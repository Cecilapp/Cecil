# Cecil - Agent Instructions

Short, actionable instructions for coding agents working in this repository.

## Start Here

- Project overview and usage: [README.md](README.md)
- Contribution process: [CONTRIBUTING.md](CONTRIBUTING.md)
- Command reference: [docs/5-Commands.md](docs/5-Commands.md)
- Architecture: [docs/9-Architecture.fr.md](docs/9-Architecture.fr.md)
- If any of the linked reference files are missing or unreadable, stop and notify the user before proceeding, as the missing document may contain requirements critical to the task.

## Critical Commands

- Install dependencies: `composer install`
- Run full quality checks: `composer code`
- Static analysis: `composer code:analyse`
- Style checks: `composer code:style`
- Integration tests: `composer test`
- CLI tests: `composer test:cli`

## Architecture At A Glance

- Main flow: Builder -> Steps -> Generators -> Renderer -> Output
- Core entry points:
  - `src/Builder.php`
  - `src/Application.php`
- Pipeline steps live in `src/Step/`
- Page generators live in `src/Generator/` (priority-based execution)
- Rendering stack lives in `src/Renderer/`
- When adding a new Generator, Step, or Command, add a corresponding integration test under the relevant test directory and verify it passes with `composer test`. CLI-facing behavior must also be covered by `composer test:cli`.

## Coding Conventions

- PHP 8.3+, strict types, PSR-12 style
- Use 4 spaces in PHP files
- Prefix PHP native functions with `\` (example: `\count()`)
- Twig/YAML/JS use 2 spaces
- All Twig template files (*.twig) anywhere in the repository must not end with a trailing newline. Remove any trailing newline when creating or editing these files.
- Markdown trailing spaces are semantically meaningful (they produce line breaks). Never remove trailing spaces from Markdown files, even when reformatting or cleaning up content, unless the user explicitly confirms the spaces are unintentional.

## Framework Conventions

- New commands should extend `AbstractCommand`
- New generators should extend `AbstractGenerator`
- New steps should extend `AbstractStep`
- Use PSR-3 `LoggerInterface` for logging
- Use PSR-16 `SimpleCacheInterface` for caching
- Exceptions should implement/extend `Cecil\Exception\ExceptionInterface`

## Pitfalls

- Generator ordering is priority-sensitive. Keep configured generator priorities coherent with expected extraction order.
- Page section assignment relies on original `filepath`, not transformed page path.
- Keep docs updated when behavior or architecture changes, especially in [docs/](docs/) and [README.md](README.md).
- When updating documentation in [docs/](docs/), always keep both English and French versions aligned (for example, `.md` and `.fr.md` counterparts).
- When editing a documentation file with frontmatter, always update the `updated` date to reflect the change.
- When updating code in `src/`, update API documentation with `php phpdoc` command (download `phpdoc` binary with `curl -Lo phpdoc https://phpdoc.org/phpDocumentor.phar` if necessary).
