---
applyTo: "src/**/*.php"
description: "PHP source rules for Cecil. Use when editing PHP files in src/, enforcing PSR-12, strict_types, required Cecil header, and native function prefixing."
---

# PHP Source Rules for Cecil

Apply these rules for every PHP file under `src/`.

## Mandatory

- Keep `declare(strict_types=1);` in every PHP file.
- Use PSR-12 formatting and 4-space indentation.
- Prefix native PHP functions with `\\` when calling them (`\\count()`, `\\array_map()`, `\\is_string()`, etc.).
- Preserve nullable parameter style with `?Type` for nullable arguments.

## Required File Header

Every PHP file must start with the Cecil project header:

```php
<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```

## When Modifying Existing Files

- Do not reformat unrelated lines.
- Keep public APIs and behavior unchanged unless explicitly requested.
- If a file is missing mandatory header or strict types and your change touches that file, add/fix them unless the user asks otherwise.

## Validation

Before finishing PHP changes, prefer this order when relevant:

1. `composer code:style`
2. `composer code:analyse`
3. `composer test`
