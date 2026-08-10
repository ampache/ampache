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
use SplFileInfo;

/**
 * Guards the escaping seam every `.phtml` template renders through.
 *
 * A template is the last place a value reaches the page, so anything it echoes has to have passed through
 * `e()` (escaped) or `raw()` (deliberately trusted html). This walks the real php tokens rather than
 * grepping, so a value split across a concatenation is checked operand by operand.
 */
class TemplateEscapingTest extends TestCase
{
    /**
     * Templates that legitimately emit a value the seam cannot describe, with the reason.
     *
     * Keep this short: an entry here is a value nothing checks.
     */
    private const array ALLOWED_UNCHECKED = [];

    /**
     * A string reaching the page unescaped is an injection hole, so a getter that returns one must be
     * wrapped. Non-string returns (ids, counts, flags) cannot carry markup and are left alone.
     */
    public function testEchoedViewValuesPassThroughAnEscapingSeam(): void
    {
        $violations = [];
        foreach ($this->getTemplates() as $template) {
            $viewClass = $this->getViewClass($template);
            if ($viewClass === null || !class_exists($viewClass)) {
                continue;
            }

            $relative = $this->getRelativePath($template);
            foreach ($this->getEchoedOperands((string) file_get_contents((string) $template->getRealPath())) as $operand) {
                $chain = $this->getUnwrappedViewCallChain($operand);
                if ($chain === [] || !$this->emitsMarkupCapableValue($viewClass, $chain)) {
                    continue;
                }

                $key = $relative . '::' . implode('()->', $chain);
                if (!in_array($key, self::ALLOWED_UNCHECKED, true)) {
                    $violations[] = $key;
                }
            }
        }

        static::assertSame(
            [],
            array_values(array_unique($violations)),
            'echoed directly instead of through $this->e() or $this->raw()'
        );
    }

    /**
     * The scan reflects the view class to learn return types, so a template that does not name its view
     * would be silently skipped.
     */
    public function testEveryTemplateNamesItsViewClass(): void
    {
        $missing = [];
        foreach ($this->getTemplates() as $template) {
            if ($this->getViewClass($template) === null) {
                $missing[] = $this->getRelativePath($template);
            }
        }

        static::assertSame([], $missing, 'templates with no `@var <class> $this` docblock');
    }

    /**
     * Walks the chain through the return types, so `$this->getFoo()->getName()` is judged on getName().
     *
     * @param class-string $viewClass
     * @param list<string> $chain
     */
    private function emitsMarkupCapableValue(string $viewClass, array $chain): bool
    {
        $current = $viewClass;
        foreach ($chain as $index => $method) {
            if (!method_exists($current, $method)) {
                return false;
            }

            $returnType = (new ReflectionMethod($current, $method))->getReturnType();
            if (!$returnType instanceof ReflectionNamedType) {
                // an untyped or union return could be anything, so it has to go through the seam
                return true;
            }

            $name = $returnType->getName();
            if ($index === array_key_last($chain)) {
                return !in_array($name, ['int', 'float', 'bool', 'void', 'never'], true);
            }

            if (!class_exists($name) && !interface_exists($name)) {
                return true;
            }

            $current = $name;
        }

        return true;
    }

    /**
     * Splits every echoed expression into its concatenation operands.
     *
     * @return list<string>
     */
    private function getEchoedOperands(string $contents): array
    {
        $operands = [];
        $tokens   = token_get_all($contents);
        $count    = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];
            if (!is_array($token) || !in_array($token[0], [T_ECHO, T_OPEN_TAG_WITH_ECHO, T_PRINT], true)) {
                continue;
            }

            $depth   = 0;
            $current = '';
            for ($cursor = $index + 1; $cursor < $count; $cursor++) {
                $inner = $tokens[$cursor];
                $text  = is_array($inner) ? $inner[1] : $inner;

                if (is_array($inner) && $inner[0] === T_CLOSE_TAG) {
                    break;
                }

                if ($depth === 0 && ($text === ';' || $text === ',')) {
                    break;
                }

                if (in_array($text, ['(', '['], true)) {
                    $depth++;
                }

                if (in_array($text, [')', ']'], true)) {
                    $depth--;
                    // the expression ended inside an enclosing call, e.g. an echo used as an argument
                    if ($depth < 0) {
                        break;
                    }
                }

                // only a top-level dot separates operands; one inside a call belongs to that call
                if ($depth === 0 && $text === '.') {
                    $operands[] = trim($current);
                    $current    = '';

                    continue;
                }

                $current .= $text;
            }

            if (trim($current) !== '') {
                $operands[] = trim($current);
            }
        }

        return $operands;
    }

    private function getRelativePath(SplFileInfo $file): string
    {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', (string) $file->getRealPath());
        $at   = strpos($path, 'resources/templates/');

        return ($at === false) ? $path : substr($path, $at);
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
     * The call chain when the operand is a bare `$this->a()->b()` expression, empty when it is wrapped in
     * a seam or is anything else. The whole chain matters because only its last return type is printed.
     *
     * @return list<string>
     */
    private function getUnwrappedViewCallChain(string $operand): array
    {
        $operand = trim($operand);
        if (preg_match('/^\$this->[A-Za-z0-9_]+\(/', $operand) !== 1) {
            return [];
        }

        // arguments may themselves contain calls, so they are collapsed away before the chain is read
        $flattened = $operand;
        while (true) {
            $reduced = (string) preg_replace('/\([^()]*\)/', '()', $flattened);
            if ($reduced === $flattened) {
                break;
            }

            $flattened = $reduced;
        }

        if (preg_match('/^\$this((?:->[A-Za-z0-9_]+\(\))+)$/', $flattened, $matches) !== 1) {
            return [];
        }

        preg_match_all('/->([A-Za-z0-9_]+)\(\)/', $matches[1], $calls);

        return (in_array($calls[1][0], ['e', 'raw'], true)) ? [] : array_values($calls[1]);
    }

    /**
     * @return class-string|null
     */
    private function getViewClass(SplFileInfo $file): ?string
    {
        $contents = (string) file_get_contents((string) $file->getRealPath());
        if (preg_match('#@var\s+(\\\\?[A-Za-z0-9_\\\\]+)\s+\$this\b#', $contents, $matches) !== 1) {
            return null;
        }

        /** @var class-string $class */
        $class = ltrim($matches[1], '\\');

        return $class;
    }
}
