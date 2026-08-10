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

namespace Ampache\Gui\View;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use SplFileInfo;
use Throwable;

/**
 * The other half of the escaping seam: a value that must not be escaped, escaped anyway.
 *
 * Two shapes reach the page the same way, as text where markup was meant. A value escaped twice, because
 * several model getters `scrub_out()` internally (`playlist_object::getFullname()`,
 * `PrivateMsg::getSubjectFormatted()`, `Podcast_Episode::getCategory()`), so `Rock & Roll` renders as
 * `Rock &amp;amp; Roll`. And markup escaped once, so an icon prints its own `<svg>` tags.
 * `TemplateEscapingTest` sees neither, because a wrapped value is exactly what it asks for.
 *
 * Both resolve the receiver's declared type and follow the delegation, so a view getter that merely
 * returns a pre-escaping model getter, or builds an icon of its own, is caught too.
 */
class TemplateDoubleEscapingTest extends TestCase
{
    /**
     * Escaping a value twice is always a display bug, so there is no legitimate entry here.
     *
     * A getter that deliberately pre-escapes belongs behind `raw()`, not behind a second escape.
     */
    private const array ALLOWED_DOUBLE_ESCAPE = [];

    /** @var string[] Helpers whose return value is markup, so escaping it prints the tags */
    private const array MARKUP_PRODUCERS = [
        'Ajax::button(',
        'Ajax::observe(',
        'Ajax::text(',
        'Art::display(',
        'Ui::get_icon(',
        'Ui::get_image(',
        'Ui::get_material_symbol(',
    ];

    /** @var array<string, bool> */
    private array $preEscapes = [];

    public function testNoTemplateEscapesAnAlreadyEscapedValue(): void
    {
        $violations = [];
        foreach ($this->getTemplates() as $template) {
            $contents  = (string) file_get_contents((string) $template->getRealPath());
            $viewClass = $this->getViewClass($contents);
            if ($viewClass === null || !class_exists($viewClass)) {
                continue;
            }

            $locals   = $this->getLocalTypes($viewClass, $contents);
            $relative = $this->getRelativePath($template);

            foreach (['scrub_out(', '->e('] as $wrapper) {
                foreach ($this->getWrappedArguments($contents, $wrapper) as $argument) {
                    foreach ($this->getCalls($argument) as [$receiver, $method]) {
                        $class = ($receiver === 'this') ? $viewClass : ($locals[$receiver] ?? null);
                        if ($class === null || !$this->preEscapes($class, $method)) {
                            continue;
                        }

                        $key = $relative . '::' . $class . '::' . $method . '()';
                        if (!in_array($key, self::ALLOWED_DOUBLE_ESCAPE, true)) {
                            $violations[] = $key;
                        }
                    }
                }
            }
        }

        static::assertSame(
            [],
            array_values(array_unique($violations)),
            'already escaped by the getter, so the template must use raw() or an unescaped getter'
        );
    }

    public function testNoTemplateEscapesMarkup(): void
    {
        $violations = [];
        foreach ($this->getTemplates() as $template) {
            $contents  = (string) file_get_contents((string) $template->getRealPath());
            $viewClass = $this->getViewClass($contents);
            $relative  = $this->getRelativePath($template);

            foreach (['scrub_out(', '->e('] as $wrapper) {
                foreach ($this->getWrappedArguments($contents, $wrapper) as $argument) {
                    // the helper called inside the escape
                    foreach (self::MARKUP_PRODUCERS as $producer) {
                        if (str_contains($argument, $producer)) {
                            $violations[] = $relative . ': ' . rtrim($producer, '(') . '()';
                        }
                    }

                    // a getter of the view's own that builds markup
                    if ($viewClass === null || !class_exists($viewClass)) {
                        continue;
                    }

                    foreach ($this->getCalls($argument) as [$receiver, $method]) {
                        if ($receiver === 'this' && $this->buildsMarkup($viewClass, $method)) {
                            $violations[] = $relative . '::' . $viewClass . '::' . $method . '()';
                        }
                    }
                }
            }
        }

        static::assertSame(
            [],
            array_values(array_unique($violations)),
            'markup escaped into text, so the template must use raw()'
        );
    }

    /**
     * Whether the method hands back markup rather than a value, so escaping it would print the tags.
     *
     * @param class-string $class
     */
    private function buildsMarkup(string $class, string $method): bool
    {
        foreach ($this->getReturnedExpressions($class, $method) as $expression) {
            foreach (self::MARKUP_PRODUCERS as $producer) {
                if (str_contains($expression, $producer)) {
                    return true;
                }
            }

            // a tag opened in a literal, which is how the smaller builders assemble their markup
            if (preg_match('/[\'"]\s*<[a-z]/i', $expression) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every `$receiver->method()` pair in an expression, receivers named without their sigil.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function getCalls(string $expression): array
    {
        preg_match_all('/\$([A-Za-z0-9_]+)->([A-Za-z0-9_]+)\s*\(\s*\)/', $expression, $matches, PREG_SET_ORDER);

        return array_map(static fn(array $match): array => [$match[1], $match[2]], $matches);
    }

    /**
     * @param class-string $interface
     * @return list<class-string>
     */
    private function getImplementations(string $interface): array
    {
        $implementations = [];
        foreach (get_declared_classes() as $candidate) {
            if (in_array($interface, class_implements($candidate) ?: [], true)) {
                $implementations[] = $candidate;
            }
        }

        return $implementations;
    }

    /**
     * Local variables a template assigns or iterates out of its own view, mapped to their class.
     *
     * @param class-string $viewClass
     * @return array<string, class-string>
     */
    private function getLocalTypes(string $viewClass, string $contents): array
    {
        $locals = [];

        preg_match_all('/\$([A-Za-z0-9_]+)\s*=\s*\$this->([A-Za-z0-9_]+)\s*\(\s*\)/', $contents, $assignments, PREG_SET_ORDER);
        foreach ($assignments as $assignment) {
            $type = $this->getYieldedClass($viewClass, $assignment[2]);
            if ($type !== null) {
                $locals[$assignment[1]] = $type;
            }
        }

        preg_match_all('/foreach\s*\(\s*\$this->([A-Za-z0-9_]+)\s*\(\s*\)\s+as\s+\$([A-Za-z0-9_]+)/', $contents, $loops, PREG_SET_ORDER);
        foreach ($loops as $loop) {
            $type = $this->getYieldedClass($viewClass, $loop[1]);
            if ($type !== null) {
                $locals[$loop[2]] = $type;
            }
        }

        return $locals;
    }

    /**
     * @param class-string $class
     * @return class-string|null
     */
    private function getPropertyClass(string $class, string $property): ?string
    {
        try {
            $type = (new ReflectionProperty($class, $property))->getType();
        } catch (Throwable) {
            return null;
        }

        if (!$type instanceof ReflectionNamedType) {
            return null;
        }

        $name = $type->getName();
        if (!class_exists($name) && !interface_exists($name)) {
            return null;
        }

        /** @var class-string $name */
        return $name;
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', (string) $file->getRealPath());
        $at   = strpos($path, 'resources/templates/');

        return ($at === false) ? $path : substr($path, $at);
    }

    /**
     * The expressions a method hands back, counting `echo` for the output-buffered idiom some views use.
     *
     * @param class-string $class
     * @return list<string>
     */
    private function getReturnedExpressions(string $class, string $method): array
    {
        try {
            $reflection = new ReflectionMethod($class, $method);
        } catch (Throwable) {
            return [];
        }

        $file = $reflection->getFileName();
        if ($file === false || $reflection->isAbstract()) {
            return [];
        }

        $lines = file($file);
        if ($lines === false) {
            return [];
        }

        $body = implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
        preg_match_all('/(?:return|echo)\s+([^;]+);/s', $body, $matches);

        return $matches[1];
    }

    /**
     * @return list<SplFileInfo>
     */
    private function getTemplates(): array
    {
        $templates = [];
        $iterator  = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../../../resources/templates', FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'phtml') {
                $templates[] = $file;
            }
        }

        usort($templates, static fn(SplFileInfo $a, SplFileInfo $b): int => strcmp((string) $a->getRealPath(), (string) $b->getRealPath()));

        return $templates;
    }

    /**
     * @return class-string|null
     */
    private function getViewClass(string $contents): ?string
    {
        if (preg_match('#@var\s+(\\\\?[A-Za-z0-9_\\\\]+)\s+\$this\b#', $contents, $matches) !== 1) {
            return null;
        }

        /** @var class-string $class */
        $class = ltrim($matches[1], '\\');

        return $class;
    }

    /**
     * Balanced-paren extraction of every argument passed to the given wrapper.
     *
     * @return list<string>
     */
    private function getWrappedArguments(string $contents, string $wrapper): array
    {
        $arguments = [];
        $offset    = 0;
        $length    = strlen($contents);

        while (($position = strpos($contents, $wrapper, $offset)) !== false) {
            $offset = $position + strlen($wrapper);
            $depth  = 1;
            $cursor = $offset;
            while ($cursor < $length && $depth > 0) {
                if ($contents[$cursor] === '(') {
                    $depth++;
                }

                if ($contents[$cursor] === ')') {
                    $depth--;
                }

                $cursor++;
            }

            $arguments[] = substr($contents, $offset, $cursor - $offset - 1);
        }

        return $arguments;
    }

    /**
     * The class a call yields, unwrapping a collection through its `@return` tag.
     *
     * @param class-string $class
     * @return class-string|null
     */
    private function getYieldedClass(string $class, string $method): ?string
    {
        if (!method_exists($class, $method)) {
            return null;
        }

        try {
            $reflection = new ReflectionMethod($class, $method);
        } catch (Throwable) {
            return null;
        }

        $returnType = $reflection->getReturnType();
        if ($returnType instanceof ReflectionNamedType) {
            $name = $returnType->getName();
            if (class_exists($name) || interface_exists($name)) {
                /** @var class-string $name */
                return $name;
            }
        }

        $docComment = $reflection->getDocComment();
        if ($docComment === false) {
            return null;
        }

        $pattern = '/@return\s+(?:array|iterable|list)<(?:[^,>]*,\s*)?([A-Za-z0-9_\\\\]+)>|@return\s+([A-Za-z0-9_\\\\]+)\[\]/';
        if (preg_match($pattern, $docComment, $matches) !== 1) {
            return null;
        }

        $name = ltrim(($matches[1] !== '') ? $matches[1] : ($matches[2] ?? ''), '\\');
        if ($name === '') {
            return null;
        }

        $candidates = [$name, $reflection->getDeclaringClass()->getNamespaceName() . '\\' . $name, 'Ampache\\Repository\\Model\\' . $name];
        foreach ($candidates as $candidate) {
            if (class_exists($candidate) || interface_exists($candidate)) {
                /** @var class-string $candidate */
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Whether the method hands back a value it has already escaped, following one level of delegation.
     *
     * An interface declares no body, so it is resolved to the implementations the container would supply.
     *
     * @param class-string $class
     */
    private function preEscapes(string $class, string $method, int $depth = 0): bool
    {
        $key = $class . '::' . $method;
        if (array_key_exists($key, $this->preEscapes)) {
            return $this->preEscapes[$key];
        }

        if ($depth > 5 || !method_exists($class, $method)) {
            return false;
        }

        // seeded false so a getter that delegates in a cycle terminates instead of recursing forever
        $this->preEscapes[$key] = false;

        if (interface_exists($class)) {
            foreach ($this->getImplementations($class) as $implementation) {
                if ($this->preEscapes($implementation, $method, $depth + 1)) {
                    return $this->preEscapes[$key] = true;
                }
            }

            return false;
        }

        foreach ($this->getReturnedExpressions($class, $method) as $expression) {
            if (str_contains($expression, 'scrub_out(')) {
                return $this->preEscapes[$key] = true;
            }

            if (preg_match('/\$this->([A-Za-z0-9_]+)->([A-Za-z0-9_]+)\s*\(/', $expression, $matches) === 1) {
                $type = $this->getPropertyClass($class, $matches[1]);
                if ($type !== null && $this->preEscapes($type, $matches[2], $depth + 1)) {
                    return $this->preEscapes[$key] = true;
                }
            }

            if (preg_match('/^\s*\$this->([A-Za-z0-9_]+)\s*\(/', $expression, $matches) === 1 && $this->preEscapes($class, $matches[1], $depth + 1)) {
                return $this->preEscapes[$key] = true;
            }
        }

        return false;
    }
}
