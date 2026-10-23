# Cecil skeleton

Files used by the `new:site` command to create a new website (see `src/Command/NewSite.php`):

- `base/`: always copied (except `cecil.yml`, used as a template to create the configuration file)
- `demo/`: copied with the `--demo` option
- `deploy/github-pages.yml`: copied to `.github/workflows/build-and-deploy.yml` if a GitHub Pages workflow is requested

Dot files are not embedded in the Phar: `base/gitignore` is renamed to `.gitignore` when copied.
