# Cecil documentation

Source files of the Cecil documentation, published at <https://cecil.app/documentation/>.

## Sections

- [Getting started](getting-started/index.md)
- [Content](content/index.md)
- [Templates](templates/index.md)
- [Assets](assets/index.md)
- [Configuration](configuration/index.md)
- [Commands](commands/index.md)
- [Deploy](deploy/index.md)
- [Developers](developers/index.md)
- [AI](ai/index.md)

The PHP API reference, generated with [phpDocumentor](https://phpdoc.org), lives in [api/](api/).

## Conventions

- Each section is a directory with an `index.md` and pages named `<n>-<name>.md`, where `<n>` sets the order.
- Every English page `*.md` has a French counterpart `*.fr.md`: keep both in sync.
- Each page starts with a front matter (HTML comment) defining `title`, `description`, `date` and `updated`; section `index.md` pages also define `weight` and `sortby`. Use `alias` to keep an old URL working.
- When editing a page, update the `updated` date in its front matter.
- The French page keeps the same `date` as the English page.
- When the French slug differs from the English one, set it with a `path` variable in the French front matter (e.g.: `path: documentation/developpeurs/bibliotheque`); otherwise the English file name is used.

## Publishing

On each push to `main`, the [Documentation workflow](../.github/workflows/documentation.yml) copies this directory to the [Cecilapp/website](https://github.com/Cecilapp/website) repository (see [`scripts/deploy-documentation.sh`](../scripts/deploy-documentation.sh)). This `README.md` is not published.
