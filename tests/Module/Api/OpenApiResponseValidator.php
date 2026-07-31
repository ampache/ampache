<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Api;

use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;

/**
 * Validates an API response against an OpenAPI 3.0 component schema.
 *
 * Two passes are run because neither alone is sufficient:
 *  - justinrainbow/json-schema checks types, required properties, enums and nested structure.
 *  - a local sweep reports properties the specification does not declare. JSON Schema permits undeclared
 *    properties by default, and `additionalProperties: false` cannot be injected here because the spec composes
 *    responses with `allOf`, where each branch would then reject the sibling branch's properties.
 */
final class OpenApiResponseValidator
{
    /** @var array<string, mixed> */
    private array $spec;

    /**
     * @param array<string, mixed> $spec the decoded OpenAPI document
     */
    public function __construct(array $spec)
    {
        $this->spec = $spec;
    }

    /**
     * Validate a raw JSON response body against a named component schema.
     *
     * The body is taken as a string rather than a decoded array because an associative decode renders `{}` and
     * `[]` identically, which would hide empty-object/empty-array mismatches.
     *
     * @return string[] human readable violations, empty when the response conforms
     */
    public function validate(string $body, string $schemaName): array
    {
        $schema = $this->spec['components']['schemas'][$schemaName] ?? [];

        return array_values(array_unique([
            ...$this->checkAgainstJsonSchema($body, $schemaName),
            ...$this->findUndeclaredProperties(json_decode($body, true), $schema),
        ]));
    }

    /**
     * @return string[]
     */
    private function checkAgainstJsonSchema(string $body, string $schemaName): array
    {
        // Only the schema subtree is registered, normalised from OpenAPI 3.0 to plain JSON Schema. Draft-04
        // reads a bare `id` key as a base URI, and the response examples in docs/openapi.json contain literal
        // `"id"` values that would otherwise be resolved as broken schema references.
        $document = ['components' => ['schemas' => $this->toJsonSchema($this->spec['components']['schemas'] ?? [])]];

        $storage = new SchemaStorage();
        $storage->addSchema('internal://spec', json_decode((string) json_encode($document)));

        // no CHECK_MODE_TYPE_CAST: coercing types here would mask the string/int mismatches this is looking for
        $validator = new Validator(new Factory($storage));
        $subject   = json_decode($body);
        $reference = (object) ['$ref' => 'internal://spec#/components/schemas/' . $schemaName];

        $validator->validate($subject, $reference);

        $errors = [];
        foreach ($validator->getErrors() as $error) {
            $property = ($error['property'] !== '') ? $error['property'] : '(root)';
            $errors[] = sprintf('%s: %s', $property, $error['message']);
        }

        return $errors;
    }

    /**
     * Pick the `oneOf` branch matching the response status so violations are reported against the intended
     * shape rather than whichever branch happens to complain least.
     *
     * @param array<int, array<string, mixed>> $branches
     * @return string[]
     */
    private function descendOneOf(mixed $data, array $branches, string $path): array
    {
        $status = (is_array($data)) ? ($data['status'] ?? null) : null;

        foreach ($branches as $branch) {
            $resolved = $this->resolve($branch);
            $enum     = $resolved['properties']['status']['enum'] ?? null;
            if ($status !== null && is_array($enum) && in_array($status, $enum, true)) {
                return $this->findUndeclaredProperties($data, $branch, $path);
            }
        }

        $best = null;
        foreach ($branches as $branch) {
            $errors = $this->findUndeclaredProperties($data, $branch, $path);
            if ($errors === []) {
                return [];
            }
            if ($best === null || count($errors) < count($best)) {
                $best = $errors;
            }
        }

        return $best ?? [];
    }

    /**
     * Walk the response alongside the schema and report keys the specification does not declare.
     *
     * @param array<string, mixed> $schema
     * @return string[]
     */
    private function findUndeclaredProperties(mixed $data, array $schema, string $path = '$'): array
    {
        $resolved = $this->resolve($schema);

        if (isset($resolved['oneOf'])) {
            return $this->descendOneOf($data, $resolved['oneOf'], $path);
        }

        if (is_array($data) && array_is_list($data)) {
            $items  = $resolved['items'] ?? null;
            $errors = [];
            if (is_array($items)) {
                foreach ($data as $index => $entry) {
                    $errors = [...$errors, ...$this->findUndeclaredProperties($entry, $items, sprintf('%s[%d]', $path, $index))];
                }
            }

            return $errors;
        }

        if (!is_array($data)) {
            return [];
        }

        $properties = $resolved['properties'] ?? [];
        // an object schema declaring no properties is an unconstrained shape in the spec, so treat it as opaque
        if ($properties === []) {
            return [];
        }

        $errors = [];
        foreach ($data as $key => $value) {
            if (!isset($properties[$key])) {
                $errors[] = sprintf('%s.%s: property is not declared in the specification', $path, $key);
                continue;
            }
            $errors = [...$errors, ...$this->findUndeclaredProperties($value, $properties[$key], $path . '.' . $key)];
        }

        return $errors;
    }

    /**
     * Resolve `$ref` pointers and flatten `allOf` composition into a single schema.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function resolve(array $schema): array
    {
        if (isset($schema['$ref'])) {
            $node = $this->spec;
            foreach (explode('/', ltrim((string) $schema['$ref'], '#/')) as $segment) {
                $node = $node[$segment] ?? [];
            }

            return $this->resolve(is_array($node) ? $node : []);
        }

        if (isset($schema['allOf'])) {
            $properties = [];
            $required   = [];
            foreach ($schema['allOf'] as $branch) {
                $resolved   = $this->resolve(is_array($branch) ? $branch : []);
                $properties = array_merge($properties, $resolved['properties'] ?? []);
                $required   = array_merge($required, $resolved['required'] ?? []);
            }
            unset($schema['allOf']);

            return array_merge($schema, ['type' => 'object', 'properties' => $properties, 'required' => $required]);
        }

        return $schema;
    }

    /**
     * Normalise an OpenAPI 3.0 schema tree into plain JSON Schema.
     *
     * `example`/`examples` are documentation rather than schema, and `nullable: true` is an OpenAPI keyword a
     * JSON Schema validator does not understand - it has to become a union with the `null` type or every
     * legitimately null field is reported as a type error.
     *
     * @param array<array-key, mixed> $node
     * @return array<array-key, mixed>
     */
    private function toJsonSchema(array $node): array
    {
        unset($node['example'], $node['examples']);

        if (($node['nullable'] ?? false) === true) {
            unset($node['nullable']);
            if (isset($node['type'])) {
                $node['type'] = array_values(array_unique([...(array) $node['type'], 'null']));
            }
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->toJsonSchema($value);
            }
        }

        return $node;
    }
}
