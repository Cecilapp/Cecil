# Cecil - Agent Instructions

Short, actionable instructions for coding agents working in this repository.

## Start Here

- Project overview and usage: [README.md](README.md)
- Contribution process: [CONTRIBUTING.md](CONTRIBUTING.md)
- Command reference: [docs/5-Commands.md](docs/5-Commands.md)
- Architecture: [docs/9-Architecture.fr.md](docs/9-Architecture.fr.md)

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

## Coding Conventions

- PHP 8.2+, strict types, PSR-12 style
- Use 4 spaces in PHP files
- Prefix PHP native functions with `\` (example: `\count()`)
- Twig/YAML/JS use 2 spaces
- Twig files must not end with a trailing newline
- Markdown trailing spaces can be semantically meaningful; do not trim automatically

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