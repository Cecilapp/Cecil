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
use Nette\Schema\Context;
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
 * The structure of maps (`fields`) and of list items (`items`) can be validated recursively.
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

    /** Schema of a user field definition, used to validate the configuration */
    protected static ?NetteSchema $definition = null;

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
        return self::defaults($this->getUserFields($section), $variables);
    }

    /**
     * Returns the schema of a user field definition (a type or a map of rules), used to validate the `pages.schema` configuration.
     *
     * The definition is recursive: `fields` of a map and `items` of a list are field definitions.
     */
    public static function definition(): NetteSchema
    {
        if (self::$definition !== null) {
            return self::$definition;
        }
        // nested definitions are resolved when they are processed, as a schema can't contain itself
        $nested = self::lazy(fn (): NetteSchema => self::definition());

        return self::$definition = Expect::anyOf(
            Expect::anyOf(...self::TYPES),
            Expect::structure([
                'type'     => Expect::anyOf(...self::TYPES),
                'required' => Expect::bool(),
                'enum'     => Expect::list(),
                'pattern'  => Expect::string(),
                'min'      => Expect::type('int|float'),
                'max'      => Expect::type('int|float'),
                'items'    => $nested,
                'fields'   => Expect::arrayOf($nested, Expect::string()),
                'default'  => Expect::mixed(),
            ])->assert(
                fn (object $rules): bool => empty($rules->fields) || ($rules->type ?? null) === 'map',
                'the `fields` rule expects the `map` type'
            )->assert(
                fn (object $rules): bool => empty($rules->fields) || (!isset($rules->min) && !isset($rules->max) && empty($rules->enum) && !isset($rules->pattern)),
                'the `min`, `max`, `enum` and `pattern` rules are not allowed on a map with `fields`'
            )
        );
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
     * Applies the default values of the given fields definitions to the variables, recursively.
     *
     * A nested default value is applied only if its map is defined (a missing map is not created).
     *
     * @param array<int|string, mixed> $fields
     * @param array<int|string, mixed> $variables
     *
     * @return array<int|string, mixed>
     *
     * @phpstan-template TKey of array-key
     *
     * @phpstan-param array<TKey, mixed> $fields
     * @phpstan-param array<TKey, mixed> $variables
     *
     * @phpstan-return array<TKey, mixed>
     */
    protected static function defaults(array $fields, array $variables): array
    {
        foreach ($fields as $name => $field) {
            if (!\is_array($field)) {
                continue;
            }
            if (!\array_key_exists($name, $variables)) {
                if (\array_key_exists('default', $field)) {
                    $variables[$name] = $field['default'];
                }
                continue;
            }
            $variables[$name] = self::nestedDefaults($field, $variables[$name]);
        }

        return $variables;
    }

    /**
     * Applies the default values of the nested fields (`fields` of a map, `fields` of the items of a list) to a value.
     *
     * @param array<string, mixed> $field
     */
    protected static function nestedDefaults(array $field, mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }
        if (!empty($fields = self::fields($field))) {
            return self::defaults($fields, $value);
        }
        if (($field['type'] ?? 'mixed') === 'list' && isset($field['items']) && \is_array($field['items'])) {
            foreach ($value as $key => $item) {
                $value[$key] = self::nestedDefaults($field['items'], $item);
            }
        }

        return $value;
    }

    /**
     * Builds the schema of a user field.
     *
     * A field is defined by its type (`author: string`), or by a map of rules:
     * `type`, `required`, `enum`, `pattern`, `min`, `max`, `fields` (fields of a map),
     * `items` (type or field definition of the items of a list) and `default`.
     *
     * A list item (`$item`) can't be empty (`null`), and the `required` rule doesn't apply to it.
     *
     * @param string|array<string, mixed>|null $field
     */
    protected static function field(string|array|null $field, bool $item = false): NetteSchema
    {
        if (!\is_array($field)) {
            $field = ['type' => $field];
        }
        $type = (string) ($field['type'] ?? 'mixed');
        $required = !$item && (bool) ($field['required'] ?? false);

        // map with fields: an open structure (`min`, `max`, `enum` and `pattern` rules don't apply)
        if (!empty($fields = self::fields($field))) {
            return self::map($fields, $item, $required);
        }

        // `min` and `max` rules apply to the count of items of a list
        $schema = $type === 'list' ? self::list($field) : self::type($type, $field);
        self::range($schema, $field);
        if ($item) {
            return $schema;
        }
        if ($required) {
            return $schema->required();
        }

        // optional fields can be empty
        return $type === 'mixed' ? $schema : $schema->nullable();
    }

    /**
     * Returns the fields definitions of a map field (`fields` rule), or an empty array.
     *
     * @param array<string, mixed> $field
     *
     * @return array<string, mixed>
     */
    protected static function fields(array $field): array
    {
        $fields = $field['fields'] ?? null;

        return ($field['type'] ?? null) === 'map' && \is_array($fields) ? $fields : [];
    }

    /**
     * Builds the schema of a map with fields: an open structure.
     *
     * An optional map can be empty (`null`), a required map or a list item can't.
     *
     * @param array<string, mixed> $fields
     */
    protected static function map(array $fields, bool $item, bool $required): NetteSchema
    {
        $schema = self::open(array_map(fn ($nested) => self::field($nested), $fields));
        if ($item || $required) {
            return self::strict($schema, $required);
        }

        return Expect::anyOf($schema, null);
    }

    /**
     * Builds the schema of a list, with the type or the field definition of its items.
     *
     * @param array<string, mixed> $field
     */
    protected static function list(array $field): SchemaType
    {
        // field definition of items: only its own rules apply to items
        if (\is_array($field['items'] ?? null)) {
            return Expect::listOf(self::field($field['items'], true));
        }
        // type of items: `enum` and `pattern` rules apply to items
        if (isset($field['items'])) {
            return Expect::listOf(self::type((string) $field['items'], array_diff_key($field, ['min' => 0, 'max' => 0])));
        }

        return Expect::list();
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
     * Applies `min` and `max` rules (value of a number, length of a string in characters, count of a list).
     *
     * @param array<string, mixed> $rules
     */
    protected static function range(SchemaType $schema, array $rules): void
    {
        $min = isset($rules['min']) ? (float) $rules['min'] : null;
        $max = isset($rules['max']) ? (float) $rules['max'] : null;
        if ($min === null && $max === null) {
            return;
        }
        // the length of a string is checked in characters (Nette checks it in bytes)
        if (\in_array($rules['type'] ?? null, ['string', 'scalar'], true)) {
            $schema->transform(fn (mixed $value, Context $context): mixed => self::checkRange($value, $min, $max, $context));

            return;
        }
        $schema->min($min);
        $schema->max($max);
    }

    /**
     * Checks that a number is in range, or that the length of a string (in characters) is in range.
     */
    protected static function checkRange(mixed $value, ?float $min, ?float $max, Context $context): mixed
    {
        $inRange = fn (int|float $number): bool => ($min === null || $number >= $min) && ($max === null || $number <= $max);
        $expected = implode('..', [$min, $max]);
        if (\is_string($value) && !$inRange($length = mb_strlen($value))) {
            $context->addError(
                'The length of %label% %path% expects to be in range %expected%, %length% characters given.',
                Message::LengthOutOfRange,
                ['value' => $value, 'length' => $length, 'expected' => $expected]
            );
        }
        if ((\is_int($value) || \is_float($value)) && !$inRange($value)) {
            $context->addError(
                'The %label% %path% expects to be in range %expected%, %value% given.',
                Message::ValueOutOfRange,
                ['value' => $value, 'expected' => $expected]
            );
        }

        return $value;
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
     * Returns a schema that delegates to the schema returned by the resolver when it is processed (allows recursive schemas).
     *
     * @param \Closure(): NetteSchema $resolver
     */
    protected static function lazy(\Closure $resolver): NetteSchema
    {
        return self::delegate($resolver, nullable: true, required: false);
    }

    /**
     * Returns a structure schema that rejects `null` and, if required, a missing value
     * (a structure validates `null` or a missing value as an empty array).
     */
    protected static function strict(Structure $schema, bool $required = false): NetteSchema
    {
        return self::delegate(fn (): NetteSchema => $schema, nullable: false, required: $required);
    }

    /**
     * Returns a schema that delegates to the schema returned by the resolver when it is processed.
     *
     * @param \Closure(): NetteSchema $resolver
     */
    private static function delegate(\Closure $resolver, bool $nullable, bool $required): NetteSchema
    {
        return new class ($resolver, $nullable, $required) implements NetteSchema {
            /**
             * @param \Closure(): NetteSchema $resolver
             */
            public function __construct(private \Closure $resolver, private bool $nullable, private bool $required)
            {
            }

            public function normalize(mixed $value, Context $context): mixed
            {
                return ($this->resolver)()->normalize($value, $context);
            }

            public function merge(mixed $value, mixed $base): mixed
            {
                return ($this->resolver)()->merge($value, $base);
            }

            public function complete(mixed $value, Context $context): mixed
            {
                if ($value === null && !$this->nullable) {
                    $context->addError(
                        'The %label% %path% expects to be %expected%, %value% given.',
                        Message::TypeMismatch,
                        ['value' => $value, 'expected' => 'array']
                    );

                    return null;
                }

                return ($this->resolver)()->complete($value, $context);
            }

            public function completeDefault(Context $context): mixed
            {
                if ($this->required) {
                    $context->addError('The mandatory item %path% is missing.', Message::MissingItem);

                    return null;
                }

                return ($this->resolver)()->completeDefault($context);
            }
        };
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
