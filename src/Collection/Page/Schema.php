<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Collection\Page;

use Cecil\Config;
use Cecil\Exception\FrontmatterException;
use Cecil\Util;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Elements\Type as SchemaType;
use Nette\Schema\Expect;
use Nette\Schema\Message;
use Nette\Schema\Processor;
use Nette\Schema\Schema as NetteSchema;
use Nette\Schema\ValidationException;

/**
 * Front matter schema.
 *
 * Validates the front matter variables of a page against:
 *  - the built-in schema of predefined variables (`title`, `date`, `menu`, `schedule`, etc.);
 *  - the user schemas declared in the configuration (`pages.schema`), by section (`*` for all pages).
 *
 * Schemas are open: variables that are not declared are allowed.
 */
class Schema
{
    /**
     * Allowed types of a user schema field.
     */
    public const array TYPES = ['string', 'int', 'number', 'bool', 'date', 'list', 'map', 'scalar', 'mixed'];

    /**
     * Key of the user schema applied to all pages.
     */
    public const string ALL = '*';

    /** @var array<string, array<string, mixed>> User schemas definitions, indexed by section */
    protected array $definitions;

    /** @var array<string, Structure|null> User schemas cache, indexed by section */
    protected array $schemas = [];

    protected Processor $processor;

    /**
     * @param array<string, array<string, mixed>> $definitions User schemas definitions, indexed by section (`*` for all pages)
     */
    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
        $this->processor = new Processor();
    }

    /**
     * Validates front matter variables and returns them, completed with the default values of the user schema.
     *
     * @param array<string, mixed> $variables
     *
     * @return array<string, mixed>
     *
     * @throws FrontmatterException
     */
    public function validate(array $variables, ?string $section = null): array
    {
        // booleans strings ("true", "yes", etc.) are casted, as when variables are set
        $normalized = $variables;
        array_walk_recursive($normalized, function (&$item) {
            $item = Util\Str::strToBool($item);
        });

        $messages = [];
        foreach ([self::builtin(), $this->getUserSchema($section)] as $schema) {
            if ($schema === null) {
                continue;
            }
            try {
                $this->processor->process($schema, $normalized);
            } catch (ValidationException $e) {
                $messages = array_merge($messages, self::formatMessages($e));
            }
        }
        if (!empty($messages)) {
            throw new FrontmatterException(implode("\n", array_unique($messages)));
        }

        // default values of the user schema
        foreach ($this->getUserFields($section) as $name => $field) {
            if (\is_array($field) && \array_key_exists('default', $field) && !\array_key_exists($name, $variables)) {
                $variables[$name] = $field['default'];
            }
        }

        return $variables;
    }

    /**
     * Returns the built-in schema of predefined variables.
     */
    public static function builtin(): Structure
    {
        static $schema = null;

        return $schema ??= self::open([
            'title'       => Expect::scalar()->nullable(),
            'description' => Expect::scalar()->nullable(),
            'layout'      => Expect::string()->nullable(),
            'date'        => self::date(),
            'updated'     => self::date(),
            'lastmod'     => self::date(),
            'path'        => Expect::scalar()->nullable(),
            'slug'        => Expect::scalar()->nullable(),
            'weight'      => Expect::anyOf(Expect::int(), Expect::float(), Expect::string()->pattern('-?\d+(\.\d+)?'))->nullable(),
            'language'    => Expect::string()->pattern(Config::LANG_CODE_PATTERN)->nullable(),
            'langref'     => Expect::string()->nullable(),
            'published'   => self::bool(),
            'draft'       => self::bool(),
            'excluded'    => self::bool(),
            'exclude'     => self::bool(),
            'uglyurl'     => self::bool(),
            // `menu: <name>`, `menu: [<name>, <name>]` or `menu: {<name>: {weight: <int>, name: <string>}}`
            'menu' => Expect::anyOf(
                Expect::string(),
                Expect::listOf('string'),
                Expect::arrayOf(
                    Expect::anyOf(self::open([
                        'weight' => Expect::anyOf(Expect::int(), Expect::string()->pattern('-?\d+'))->nullable(),
                        'name'   => Expect::scalar()->nullable(),
                    ]), null),
                    Expect::string()
                )
            )->nullable(),
            'schedule' => Expect::anyOf(self::open([
                'publish' => self::date(),
                'expiry'  => self::date(),
            ]), null),
            'redirect' => Expect::string()->nullable(),
            'external' => Expect::string()->nullable(),
            'alias'    => self::strings(),
            'aliases'  => self::strings(),
            'output'   => self::strings(),
        ]);
    }

    /**
     * Returns the user schema of the given section (merged with the schema of all pages), or null if there is none.
     */
    public function getUserSchema(?string $section): ?Structure
    {
        $key = $section ?? self::ALL;
        if (!\array_key_exists($key, $this->schemas)) {
            $fields = $this->getUserFields($section);
            $this->schemas[$key] = empty($fields) ? null : self::open(array_map([self::class, 'field'], $fields));
        }

        return $this->schemas[$key];
    }

    /**
     * Returns the user fields definitions of the given section: fields of all pages (`*`), overridden by the fields of the section.
     *
     * @return array<string, mixed>
     */
    protected function getUserFields(?string $section): array
    {
        $fields = (array) ($this->definitions[self::ALL] ?? []);
        if ($section !== null && $section !== self::ALL) {
            $fields = array_replace($fields, (array) ($this->definitions[$section] ?? []));
        }

        return $fields;
    }

    /**
     * Builds the schema of a user field.
     *
     * A field is defined by its type (`author: string`), or by a map of rules:
     * `type`, `required`, `enum`, `pattern`, `min`, `max`, `items` (type of list items) and `default`.
     *
     * @param string|array<string, mixed>|null $field
     */
    protected static function field(string|array|null $field): NetteSchema
    {
        if (!\is_array($field)) {
            $field = ['type' => $field];
        }
        $type = (string) ($field['type'] ?? 'mixed');
        $required = (bool) ($field['required'] ?? false);

        if ($type === 'list') {
            // `enum` and `pattern` rules apply to items, `min` and `max` rules apply to the count of items
            $schema = isset($field['items'])
                ? Expect::listOf(self::type((string) $field['items'], array_diff_key($field, ['min' => 0, 'max' => 0])))
                : Expect::list();
            self::range($schema, $field);
        } else {
            $schema = self::type($type, $field);
            self::range($schema, $field);
        }
        if ($required) {
            $schema->required();
        }
        // optional fields can be empty
        if (!$required && $type !== 'mixed') {
            $schema->nullable();
        }

        return $schema;
    }

    /**
     * Builds the schema of a type, with the `enum` and `pattern` rules.
     *
     * @param array<string, mixed> $rules
     */
    protected static function type(string $type, array $rules): SchemaType
    {
        $schema = match ($type) {
            'string' => Expect::string(),
            'int'    => Expect::int(),
            'number' => Expect::type('int|float'),
            'bool'   => Expect::bool(),
            'date'   => self::date(),
            'map'    => Expect::type('array'),
            'scalar' => Expect::scalar(),
            'list'   => Expect::list(),
            default  => Expect::mixed(),
        };
        if (!empty($rules['enum']) && \is_array($rules['enum'])) {
            $values = $rules['enum'];
            $schema->assert(
                fn ($value) => \in_array($value, $values, true),
                \sprintf('expects to be one of %s', implode(', ', array_map(fn ($v) => var_export($v, true), $values)))
            );
        }
        if (isset($rules['pattern']) && \in_array($type, ['string', 'scalar'], true)) {
            $schema->pattern((string) $rules['pattern']);
        }

        return $schema;
    }

    /**
     * Applies `min` and `max` rules (value of a number, length of a string, count of a list).
     *
     * @param array<string, mixed> $rules
     */
    protected static function range(SchemaType $schema, array $rules): void
    {
        if (isset($rules['min'])) {
            $schema->min((float) $rules['min']);
        }
        if (isset($rules['max'])) {
            $schema->max((float) $rules['max']);
        }
    }

    /**
     * Returns an open structure schema: declared items are validated, other items are allowed.
     *
     * @param array<string, NetteSchema> $items
     */
    protected static function open(array $items): Structure
    {
        return Expect::structure($items)->otherItems(Expect::mixed());
    }

    /**
     * Schema of a date: a string, a timestamp or a date object that can be converted to a date.
     */
    protected static function date(): SchemaType
    {
        return Expect::type('string|int|DateTimeInterface')->assert(function ($value): bool {
            try {
                Util\Date::toDatetime($value);
            } catch (\Throwable) {
                return false;
            }

            return true;
        }, 'expects to be a valid date');
    }

    /**
     * Schema of a boolean: also accepts INI front matter values ("1" and "").
     */
    protected static function bool(): SchemaType
    {
        return Expect::type('bool|int|string')->assert(
            fn ($value): bool => \is_bool($value) || \in_array($value, [0, 1, '0', '1', ''], true),
            'expects to be a boolean'
        )->nullable();
    }

    /**
     * Schema of a string or a list of strings.
     */
    protected static function strings(): NetteSchema
    {
        return Expect::anyOf(Expect::string(), Expect::listOf('string'))->nullable();
    }

    /**
     * Returns readable validation messages, with the path of each invalid variable.
     *
     * @return string[]
     */
    protected static function formatMessages(ValidationException $e): array
    {
        $messages = [];
        foreach ($e->getMessageObjects() as $message) {
            if ($message->code === Message::FailedAssertion) {
                $messages[] = \sprintf("The variable '%s' %s.", implode("\u{a0}›\u{a0}", $message->path), $message->variables['assertion'] ?? 'is not valid');
                continue;
            }
            $messages[] = str_replace("item '", "variable '", $message->toString());
        }

        return $messages;
    }
}
