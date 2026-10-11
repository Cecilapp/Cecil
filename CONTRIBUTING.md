# Contributing

Thank you for your interest in Cecil! Contributions of all kinds are welcome: bug reports, feature ideas, documentation improvements, translations, themes and code.

Before contributing, please read our [code of conduct](https://github.com/Cecilapp/Cecil/blob/main/CODE_OF_CONDUCT.md) and follow it in all your interactions with the project.

## Ways to contribute

- **Ask a question**: use [GitHub Discussions](https://github.com/Cecilapp/Cecil/discussions) (please don't open an issue for support questions).
- **Report a bug**: open an [issue](https://github.com/Cecilapp/Cecil/issues/new/choose) using the *Bug report* template, with the Cecil version (`cecil --version`), your PHP version and the steps to reproduce.
- **Suggest a feature**: open an [issue](https://github.com/Cecilapp/Cecil/issues/new/choose) using the *Feature request* template, describing the use case before the solution.
- **Improve the documentation**: documentation lives in [docs/](docs/) and is published on [cecil.app](https://cecil.app/documentation/).
- **Submit code**: fix a bug or implement a feature through a Pull Request (see below).

When contributing a non-trivial change, please first discuss it via an [issue](https://github.com/Cecilapp/Cecil/issues) or a [discussion](https://github.com/Cecilapp/Cecil/discussions), so we can agree on the approach before you invest time in it.

> [!IMPORTANT]
> Do not report security vulnerabilities through public issues: follow the [security policy](SECURITY.md) instead.

## Development setup

### Requirements

- PHP 8.3 or higher, with the extensions listed in [composer.json](composer.json)
- [Composer](https://getcomposer.org)
- Git

### Installation

1. [Fork](https://github.com/Cecilapp/Cecil/fork) the repository and clone your fork:

   ```bash
   git clone https://github.com/<your-username>/Cecil.git
   cd Cecil
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Run Cecil from the sources:

   ```bash
   php bin/cecil --version
   ```

### Project structure

- `src/`: Cecil source code (main entry points: `src/Builder.php` and `src/Application.php`)
  - `src/Step/`: build pipeline steps
  - `src/Generator/`: virtual pages generators
  - `src/Renderer/`: rendering stack (Twig)
  - `src/Command/`: CLI commands
- `resources/`: built-in templates, translations and assets
- `tests/`: unit tests (`tests/Unit/`), integration tests and fixtures (`tests/fixtures/`)
- `docs/`: documentation (English `*.md` and French `*.fr.md`)
- `skills/`: agent skills (`cecil`, `cecil-theme`)

See the [architecture documentation](docs/developers/3-architecture.md) for an overview of the build process (Builder → Steps → Generators → Renderer → Output).

## Coding guidelines

- PHP code must be compatible with PHP 8.3+, use strict types and follow [PSR-12](https://www.php-fig.org/psr/psr-12/).
- Indentation: 4 spaces in PHP files, 2 spaces in Twig, YAML and JavaScript files.
- Twig templates (`*.twig`) must not end with a trailing newline.
- New commands extend `AbstractCommand`, new generators extend `AbstractGenerator` and new steps extend `AbstractStep`.
- Use PSR-3 `LoggerInterface` for logging and PSR-16 `CacheInterface` for caching.
- Exceptions must implement `Cecil\Exception\ExceptionInterface`.

### Quality checks

Run all code quality checks (PHPStan, PHP Mess Detector, PHP CS Fixer, PHP CodeSniffer and Twig CS Fixer) with:

```bash
composer code
```

Or run them individually:

| Command                 | Description                                  |
| ----------------------- | -------------------------------------------- |
| `composer code:analyse` | Static analysis with PHPStan (level 8)       |
| `composer code:md`      | Potential problems with PHP Mess Detector    |
| `composer code:fix`     | Fix code style with PHP CS Fixer             |
| `composer code:style`   | Check coding standard with PHP CodeSniffer   |
| `composer code:twig`    | Lint Twig templates with Twig CS Fixer       |

## Tests

Run unit and integration tests with:

```bash
composer test
```

| Command                     | Description                                      |
| --------------------------- | ------------------------------------------------ |
| `composer test:unit`        | Unit tests only                                  |
| `composer test:integration` | Integration tests only (build of a test website) |
| `composer test:cli`         | CLI integration tests                            |
| `composer test:bin`         | Create and build a demo site with `bin/cecil`    |

Any bug fix or new feature should come with tests:

- unit tests go in `tests/Unit/` (`*Test.php` files);
- integration tests rely on the website in `tests/fixtures/website/`;
- CLI-facing behavior must be covered by `composer test:cli`.

## Documentation

- Keep the documentation up to date when behavior changes, in [docs/](docs/) and [README.md](README.md).
- Each English documentation file (`*.md`) has a French counterpart (`*.fr.md`): please update both (or mention it in your Pull Request if you can't write the translation).
- When editing a documentation file with front matter, update its `updated` date.
- Trailing spaces in Markdown files are meaningful (line breaks): don't remove them.

## Commit messages

Commit messages follow the [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) specification, with a first line of 72 characters or less:

```text
<type>(<scope>): <description>
```

Examples:

```text
feat(generator): page navigation follows the sections tree
fix(image): dedupe missing variant warnings per build
docs(readme): add quick start
```

Common types: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `perf`, `style`.

## Pull Request process

1. Create a dedicated branch from `main` in your fork (e.g. `fix/image-variants` or `feat/new-generator`).
2. Ensure dependencies are updated: run `composer update`.
3. Make your changes, with tests and documentation.
4. Ensure `composer code` and `composer test` pass locally: the same checks run in [continuous integration](https://github.com/Cecilapp/Cecil/actions/workflows/continuous-integration.yml) on every supported PHP version.
5. Open a Pull Request against the `main` branch, fill in the template and reference the related issue (e.g. `Fixes #123`).
6. Keep your Pull Request focused on a single topic: smaller changes are easier to review and merge.
7. Address review feedback by pushing new commits to your branch.
8. You may request the main reviewer to merge the Pull Request for you.

The [changelog](CHANGELOG.md) is generated automatically: you don't need to edit it.

## License

By contributing to Cecil, you agree that your contributions will be licensed under the [MIT License](LICENSE).
