[![Cecil's logo, created by Cécile Ricordeau](https://cecil.app/images/logo-cecil.png)](https://cecil.app)

A simple and powerful content-driven static site generator.

[![Latest stable version](https://poser.pugx.org/cecil/cecil/v/stable)](https://github.com/Cecilapp/Cecil/releases/latest)
[![License](https://poser.pugx.org/cecil/cecil/license)](https://github.com/Cecilapp/Cecil/blob/master/LICENSE)  

Cecil is a CLI application that merges plain text files (written in [Markdown](http://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

📄[Documentation](https://cecil.app/documentation) &middot; 💻[Demo](https://the-butler-demo.cecil.app) &middot; 🐛[Issues tracker](https://github.com/Cecilapp/Cecil/issues) &middot; 💬[Discussions](https://github.com/Cecilapp/Cecil/discussions)

![Cecil CLI animated demo](docs/cecil-cli-demo.gif "Cecil CLI demo")

[![Continuous Integration status](https://github.com/Cecilapp/Cecil/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/Cecilapp/Cecil/actions/workflows/continuous-integration.yml)
[![Release status](https://github.com/Cecilapp/Cecil/actions/workflows/release.yml/badge.svg)](https://github.com/Cecilapp/Cecil/actions/workflows/release.yml)
[![Documentation status](https://github.com/Cecilapp/Cecil/actions/workflows/documentation.yml/badge.svg)](https://github.com/Cecilapp/Cecil/actions/workflows/documentation.yml)  
[![Scrutinizer score](https://scrutinizer-ci.com/g/Cecilapp/Cecil/badges/quality-score.png)](https://scrutinizer-ci.com/g/Cecilapp/Cecil/)
[![Codacy badge](https://app.codacy.com/project/badge/Grade/07232d3c7ff34f3da5abdac8f3ad2cee)](https://app.codacy.com/gh/Cecilapp/Cecil/dashboard)
[![Coverage score](https://coveralls.io/repos/github/Cecilapp/Cecil/badge.svg?branch=master)](https://coveralls.io/github/Cecilapp/Cecil?branch=master)
[![StyleCI status](https://github.styleci.io/repos/7548986/shield?style=plastic)](https://styleci.io/repos/12738012)
[![SymfonyInsight badge](https://insight.symfony.com/projects/ada27715-6342-43f8-a1e7-4d5a8fe78e62/mini.svg)](https://insight.symfony.com/projects/ada27715-6342-43f8-a1e7-4d5a8fe78e62)

## Quick Start

Read the [Quick Start](https://cecil.app/documentation/quick-start/) documentation page.

Create and deploy a blog site:  
[![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg "Deploy to Netlify")](https://cecil.app/hosting/netlify/deploy/) [![Deploy with Vercel](https://vercel.com/button/default.svg "Deploy with Vercel")](https://cecil.app/hosting/vercel/deploy/)

## Features

- No database, no server, no dependency: performance and security
- Your pages are stored in [Markdown](https://cecil.app/documentation/content/#body) flat files with a [YAML front matter](https://cecil.app/documentation/content/#front-matter)
- Powered by [Twig](https://cecil.app/documentation/templates/), a flexible template engine, with [themes](https://cecil.app/themes) support
- Pagination, sitemap, redirections, robots.txt, taxonomies, RSS are generated automatically
- Handles and optimizes assets for you
- [Download one file](https://github.com/Cecilapp/Cecil/releases/latest/download/cecil.phar) and run it
- Easy to deploy

## Installation

[Download `cecil.phar`](https://github.com/Cecilapp/Cecil/releases/latest/download/cecil.phar) from your browser or from your terminal:

```bash
curl -LO https://github.com/Cecilapp/Cecil/releases/latest/download/cecil.phar
```

> [!IMPORTANT]
> [PHP](https://www.php.net) 8.1+ is required.

## Usage

- Get help: `php cecil.phar help`
- Create a new website: `php cecil.phar new:site`
- Preview your website: `php cecil.phar serve`

## Contributing

See [Contributing](CONTRIBUTING.md).

Thanks goes to these wonderful people ([emoji key](https://allcontributors.org/docs/en/emoji-key)):

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore-start -->
<!-- markdownlint-disable -->
<table>
  <tbody>
    <tr>
      <td align="center" valign="top" width="25%"><a href="https://ligny.fr"><img src="https://avatars.githubusercontent.com/u/80580?v=4?s=100" width="100px;" alt="Arnaud Ligny"/><br /><sub><b>Arnaud Ligny</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3AArnaudLigny" title="Bug reports">🐛</a> <a href="https://github.com/Cecilapp/Cecil/commits?author=ArnaudLigny" title="Documentation">📖</a> <a href="#ideas-ArnaudLigny" title="Ideas, Planning, & Feedback">🤔</a> <a href="#maintenance-ArnaudLigny" title="Maintenance">🚧</a> <a href="#promotion-ArnaudLigny" title="Promotion">📣</a> <a href="#question-ArnaudLigny" title="Answering Questions">💬</a> <a href="https://github.com/Cecilapp/Cecil/pulls?q=is%3Apr+reviewed-by%3AArnaudLigny" title="Reviewed Pull Requests">👀</a> <a href="#translation-ArnaudLigny" title="Translation">🌍</a> <a href="#talk-ArnaudLigny" title="Talks">📢</a></td>
      <td align="center" valign="top" width="25%"><a href="https://frank.taillandier.me"><img src="https://avatars.githubusercontent.com/u/103008?v=4?s=100" width="100px;" alt="Frank Taillandier"/><br /><sub><b>Frank Taillandier</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/commits?author=DirtyF" title="Documentation">📖</a> <a href="#ideas-DirtyF" title="Ideas, Planning, & Feedback">🤔</a> <a href="#promotion-DirtyF" title="Promotion">📣</a> <a href="#translation-DirtyF" title="Translation">🌍</a> <a href="#mentoring-DirtyF" title="Mentoring">🧑‍🏫</a></td>
      <td align="center" valign="top" width="25%"><a href="https://mirell.com"><img src="https://avatars.githubusercontent.com/u/1871867?v=4?s=100" width="100px;" alt="Martin Szulecki"/><br /><sub><b>Martin Szulecki</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3AFunkyM" title="Bug reports">🐛</a> <a href="https://github.com/Cecilapp/Cecil/commits?author=FunkyM" title="Code">💻</a> <a href="#ideas-FunkyM" title="Ideas, Planning, & Feedback">🤔</a></td>
      <td align="center" valign="top" width="25%"><a href="https://www.magentix.fr"><img src="https://avatars.githubusercontent.com/u/346889?v=4?s=100" width="100px;" alt="Matthieu Vion"/><br /><sub><b>Matthieu Vion</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3Amagentix" title="Bug reports">🐛</a> <a href="https://github.com/Cecilapp/Cecil/commits?author=magentix" title="Code">💻</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="25%"><a href="https://github.com/peter279k"><img src="https://avatars.githubusercontent.com/u/9021747?v=4?s=100" width="100px;" alt="Chun-Sheng, Li"/><br /><sub><b>Chun-Sheng, Li</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/commits?author=peter279k" title="Code">💻</a> <a href="#security-peter279k" title="Security">🛡️</a></td>
      <td align="center" valign="top" width="25%"><a href="https://www.benjaminhirsch.net"><img src="https://avatars.githubusercontent.com/u/2293943?v=4?s=100" width="100px;" alt="Benjamin Hirsch"/><br /><sub><b>Benjamin Hirsch</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3Abenjaminhirsch" title="Bug reports">🐛</a> <a href="https://github.com/Cecilapp/Cecil/commits?author=benjaminhirsch" title="Code">💻</a></td>
      <td align="center" valign="top" width="25%"><a href="http://kavlak.uk/@ahnlak"><img src="https://avatars.githubusercontent.com/u/730245?v=4?s=100" width="100px;" alt="Pete Favelle"/><br /><sub><b>Pete Favelle</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3Aahnlak" title="Bug reports">🐛</a> <a href="https://github.com/Cecilapp/Cecil/commits?author=ahnlak" title="Code">💻</a> <a href="#ideas-ahnlak" title="Ideas, Planning, & Feedback">🤔</a></td>
      <td align="center" valign="top" width="25%"><a href="https://backendtea.com"><img src="https://avatars.githubusercontent.com/u/14289961?v=4?s=100" width="100px;" alt="Gert de Pagter"/><br /><sub><b>Gert de Pagter</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3ABackEndTea" title="Bug reports">🐛</a> <a href="#infra-BackEndTea" title="Infrastructure (Hosting, Build-Tools, etc)">🚇</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="25%"><a href="https://aboutweb.dev"><img src="https://avatars.githubusercontent.com/u/1137938?v=4?s=100" width="100px;" alt="Joe Vallender"/><br /><sub><b>Joe Vallender</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3Ajoevallender" title="Bug reports">🐛</a></td>
      <td align="center" valign="top" width="25%"><a href="https://jawira.com/"><img src="https://avatars.githubusercontent.com/u/496541?v=4?s=100" width="100px;" alt="Jawira Portugal"/><br /><sub><b>Jawira Portugal</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/issues?q=author%3Ajawira" title="Bug reports">🐛</a></td>
      <td align="center" valign="top" width="25%"><a href="https://ouuan.moe/about"><img src="https://avatars.githubusercontent.com/u/30581822?v=4?s=100" width="100px;" alt="Yufan You"/><br /><sub><b>Yufan You</b></sub></a><br /><a href="#security-ouuan" title="Security">🛡️</a></td>
      <td align="center" valign="top" width="25%"><a href="https://blog.welcomattic.com"><img src="https://avatars.githubusercontent.com/u/773875?v=4?s=100" width="100px;" alt="Mathieu Santostefano"/><br /><sub><b>Mathieu Santostefano</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/commits?author=welcoMattic" title="Documentation">📖</a> <a href="https://github.com/Cecilapp/Cecil/issues?q=author%3AwelcoMattic" title="Bug reports">🐛</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="25%"><a href="https://github.com/maxalmonte14"><img src="https://avatars.githubusercontent.com/u/12385704?v=4?s=100" width="100px;" alt="Max"/><br /><sub><b>Max</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/commits?author=maxalmonte14" title="Documentation">📖</a></td>
      <td align="center" valign="top" width="25%"><a href="https://lefevre.dev"><img src="https://avatars.githubusercontent.com/u/1533248?v=4?s=100" width="100px;" alt="Progi1984"/><br /><sub><b>Progi1984</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/commits?author=Progi1984" title="Code">💻</a> <a href="#ideas-Progi1984" title="Ideas, Planning, & Feedback">🤔</a></td>
      <td align="center" valign="top" width="25%"><a href="https://franck.matsos.fr"><img src="https://avatars.githubusercontent.com/u/805227?v=4?s=100" width="100px;" alt="Franck Matsos"/><br /><sub><b>Franck Matsos</b></sub></a><br /><a href="https://github.com/Cecilapp/Cecil/commits?author=fmatsos" title="Code">💻</a></td>
    </tr>
  </tbody>
</table>

<!-- markdownlint-restore -->
<!-- prettier-ignore-end -->

<!-- ALL-CONTRIBUTORS-LIST:END -->

This project follows the [all-contributors](https://github.com/all-contributors/all-contributors) specification. Contributions of any kind welcome!

```bash
npx all-contributors add
npx all-contributors generate
```

## Development

### Build binary

Build the `cecil.phar` binary with [Box](https://github.com/box-project/box/):

```bash
# Install Box globally
composer global require humbug/box
# Add Box to your PATH
export PATH=~/.composer/vendor/bin:$PATH
# Build the phar file
composer build
# Check the phar file
php dist/cecil.phar about
```

### Build API documentation

Build the API documentation with [phpDocumentor](https://www.phpdoc.org):

```bash
# Install phpDocumentor globally
curl -Lo phpdoc https://phpdoc.org/phpDocumentor.phar
# Build the API documentation
php phpdoc
```

## Sponsors

<!--[![Aperture Lab](https://avatars.githubusercontent.com/u/10225022?s=100)](https://aperturelab.fr)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
[![studio cecillie](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/cecillie.png)](https://studio.cecillie.fr#gh-light-mode-only)[![studio cecillie](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/cecillie-dark.png)](https://studio.cecillie.fr#gh-dark-mode-only)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[![Netlify](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/netlify.png)](https://www.netlify.com#gh-light-mode-only)[![Netlify](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/netlify-dark.png)](https://www.netlify.com#gh-dark-mode-only)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<!--[![Vercel](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/vercel.png)](https://vercel.com/?utm_source=cecil&utm_campaign=oss#gh-light-mode-only)[![ Vercel](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/vercel-dark.png)](https://vercel.com/?utm_source=cecil&utm_campaign=oss#gh-dark-mode-only)-->

## License

Cecil is a free software distributed under the terms of the MIT license.

Cecil © [Arnaud Ligny](https://arnaudligny.fr)  
Logo © [Cécile Ricordeau](https://www.cecillie.fr)
