<!--
title: "Installation"
description: "Requirements, installation methods (PHAR, package managers, Composer), update and troubleshooting."
date: 2026-10-05
updated: 2026-10-05
-->
# Installation

Cecil is distributed as a single executable file, `cecil.phar`, which runs anywhere PHP is installed.

## Requirements

- [PHP](https://www.php.net/manual/install.php) 8.3+
- PHP extensions: `fileinfo`, `gd` and `mbstring`

Check your PHP version and loaded extensions:

```bash
php -v
php -m
```

### Optional extensions

| Extension | Usage |
| --------- | ----- |
| [`intl`](https://www.php.net/manual/book.intl.php) | Dates [localization](../templates/5-localization.md) with other locales than `en` (improves performance otherwise). |
| [`imagick`](https://www.php.net/manual/book.imagick.php) | Image processing, preferred over GD when available. |
| [`ffi`](https://www.php.net/manual/book.ffi.php) | Image processing with [libvips](https://www.libvips.org/) (requires [Composer installation](#composer)). |

## Download the PHAR

Download `cecil.phar` from your terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

Then run it with PHP:

```bash
php cecil.phar --version
```

:::info
You can also download a [specific version or the preview version](/download/).
:::

## Install globally

Installing Cecil globally allows you to run the `cecil` command from any directory, instead of `php cecil.phar`.

### macOS and Linux

With [Homebrew](https://brew.sh):

```bash
brew install cecilapp/tap/cecil
```

Or manually, by moving the PHAR in a directory of your `PATH`:

```bash
mv cecil.phar /usr/local/bin/cecil
chmod +x /usr/local/bin/cecil
```

### Windows

With [Scoop](https://scoop.sh):

```bash
scoop install https://cecil.app/scoop/cecil.json
```

Or manually:

1. Move `cecil.phar` in a dedicated directory, like `C:\bin`
2. Rename it from `cecil.phar` to `cecil`
3. Append `;C:\bin` to your `PATH` environment variable
4. Create a [wrapping batch script](https://raw.githubusercontent.com/Cecilapp/Cecil/main/bin/cecil.bat) next to it

### PHIVE

With [PHIVE](https://phar.io) (The PHAR Installation and Verification Environment):

```bash
phive install cecil
```

### Composer {#composer}

With [Composer](https://getcomposer.org):

```bash
composer global require cecil/cecil
```

:::important
Make sure Composer's global binaries directory is in your `PATH`. Run `composer global config bin-dir --absolute` to get it.
:::

:::tip
To use Cecil as a dependency of a PHP project, see [Library](../developers/2-library.md).
:::

## Verify the installation

```bash
cecil --version
```

:::info
Run `cecil list` to display the [available commands](../commands/index.md).
:::

## Update

Update Cecil to the latest version according to your installation method:

```bash
# PHAR
php cecil.phar self-update
# Homebrew
brew upgrade cecilapp/tap/cecil
# Scoop
scoop update cecil
# PHIVE
phive update cecil
# Composer
composer global update cecil/cecil
```

The `self-update` command (PHAR only) also accepts the following options:

- `--rollback`: reverts to the previous installed version
- `--stable`: forces an update to the last stable version
- `--preview`: forces an update to the last unstable version

:::info
The changelog of each release is available on [GitHub](https://github.com/Cecilapp/Cecil/releases).
:::

## Troubleshooting

### `command not found: cecil`

The installation directory is not in your `PATH`: add it, or run Cecil with `php cecil.phar` from the directory containing the PHAR.

### PHP version or extension error

Your PHP CLI may differ from the one you expect (several versions installed): check it with `php -v` and `php --ini`, then enable missing extensions in the loaded `php.ini` file.

### Diagnose a website

Once a website is created, run the [`doctor`](../commands/5-doctor.md) command to diagnose its configuration.
