<!--
title: "Override configuration"
description: "Override the configuration with environment variables or a CLI option."
date: 2021-05-07
updated: 2026-10-10
-->
# Override configuration

The configuration can be overridden without editing the configuration file, through environment variables or a CLI option.

## Environment variables

The configuration can be overridden through [environment variables](https://en.wikipedia.org/wiki/Environment_variable).

At startup, Cecil also attempts to load a `.env` file from the current site path (the current working directory, or the `<path>` argument if provided).

- If the `.env` file does not exist, Cecil continues normally.
- Variables already defined by the shell/system are preserved.

Each environment variable name must be prefixed with `CECIL_` and the configuration key must be set in uppercase.

For example, the following command set the website’s `baseurl`:

```bash
export CECIL_BASEURL="https://example.com/"
```

You can store the same value in a `.env` file at your project root:

```dotenv
CECIL_BASEURL="https://example.com/"
CECIL_TITLE="My Cecil site"
```

## CLI option

You can combine multiple configuration files, with the `--config` option (left-to-right precedence):

```bash
php cecil.phar --config config-1.yml,config-2.yml
```
