<!--
title: "Installation"
description: "Requirements, installation methods (PHAR, standalone binary, package managers, Composer), update and troubleshooting."
date: 2026-10-05
updated: 2026-10-16
-->
# Installation

Cecil is distributed as a single executable file, `cecil.phar`, which runs anywhere PHP is installed, and as [standalone binaries](#standalone-binary) that don't require PHP.

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

## Standalone binary {#standalone-binary}

If PHP is not installed, download the standalone binary for your platform from the [latest GitHub release](https://github.com/Cecilapp/Cecil/releases/latest). It embeds PHP and the required extensions.

| Platform | File |
| -------- | ---- |
| Linux x64 | `cecil-linux-x64` |
| Linux ARM64 | `cecil-linux-arm64` |
| macOS Intel | `cecil-macos-x64` |
| macOS Apple Silicon | `cecil-macos-arm64` |
| Windows x64 | `cecil-windows-x64.exe` |

For example:

:::tabs
#### Linux

```bash
curl -Lo cecil https://github.com/Cecilapp/Cecil/releases/latest/download/cecil-linux-x64
chmod +x cecil
./cecil --version
```

#### macOS

```bash
curl -Lo cecil https://github.com/Cecilapp/Cecil/releases/latest/download/cecil-macos-arm64
chmod +x cecil
xattr -d com.apple.quarantine cecil
./cecil --version
```

Binaries are not signed: the `xattr` command removes the quarantine attribute before the first run.

#### Windows

```powershell
curl.exe -Lo cecil.exe https://github.com/Cecilapp/Cecil/releases/latest/download/cecil-windows-x64.exe
.\cecil.exe --version
```
:::

:::info
SHA-256 checksums are available in the `cecil-binaries.sha256` file of each release, and the build provenance can be verified with `gh attestation verify cecil --repo Cecilapp/Cecil`.
:::

:::important
The binary uses GD for image processing: Imagick and libvips are not available. The `self-update` command is not available either: download the binary again to update it.

The `serve` command relies on the PHP built-in web server, which the binary doesn't embed: it requires a local PHP executable. Without PHP, run the `build` command and serve the output directory with another web server.
:::

## Install globally

Installing Cecil globally allows you to run the `cecil` command from any directory, instead of `php cecil.phar`.

::::tabs
#### macOS and Linux

With [Homebrew](https://brew.sh):

```bash
brew install cecilapp/tap/cecil
```

Or manually, by moving the PHAR in a directory of your `PATH`:

```bash
mv cecil.phar /usr/local/bin/cecil
chmod +x /usr/local/bin/cecil
```

#### Windows

With [Scoop](https://scoop.sh):

```bash
scoop install https://cecil.app/scoop/cecil.json
```

Or manually:

1. Move `cecil.phar` in a dedicated directory, like `C:\bin`
2. Rename it from `cecil.phar` to `cecil`
3. Append `;C:\bin` to your `PATH` environment variable
4. Create a [wrapping batch script](https://raw.githubusercontent.com/Cecilapp/Cecil/main/bin/cecil.bat) next to it

#### PHIVE

With [PHIVE](https://phar.io) (The PHAR Installation and Verification Environment):

```bash
phive install cecil
```

#### Composer {#composer}

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
::::

## Verify the installation

```bash
cecil --version
```

:::info
Run `cecil list` to display the [available commands](../commands/index.md).
:::

## Update

Update Cecil to the latest version according to your installation method:

:::code-group
#### PHAR

```bash
php cecil.phar self-update
```

#### Homebrew

```bash
brew upgrade cecilapp/tap/cecil
```

#### Scoop

```bash
scoop update cecil
```

#### PHIVE

```bash
phive update cecil
```

#### Composer

```bash
composer global update cecil/cecil
```
:::

The [standalone binary](#standalone-binary) is updated by downloading it again.

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
