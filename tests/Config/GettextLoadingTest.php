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

namespace Ampache\Config;

use Ampache\Config\Init\InitializationHandlerGetText;
use Gettext\Loader\MoLoader;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class GettextLoadingTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testACatalogueShippedBesideTheBinaryOneIsUsedAsIs(): void
    {
        $mo      = sys_get_temp_dir() . '/ampache-test-' . getmypid() . '.mo';
        $sibling = substr($mo, 0, -3) . '.php';

        copy(__DIR__ . '/../../locale/fr_FR/LC_MESSAGES/messages.mo', $mo);
        file_put_contents($sibling, '<?php return [];');
        touch($sibling, filemtime($mo) + 60);

        try {
            self::assertSame($sibling, compiled_gettext_catalogue($mo));
        } finally {
            unlink($mo);
            unlink($sibling);
        }
    }

    #[RunInSeparateProcess]
    public function testAnUnreadableCatalogueCompilesToNothing(): void
    {
        // every failure has to put the caller back on the binary catalogue rather than break the page
        self::assertNull(compiled_gettext_catalogue('/nonexistent/LC_MESSAGES/messages.mo'));
    }

    #[RunInSeparateProcess]
    public function testCatalogueIsCompiledOnceAndReusedAfterwards(): void
    {
        $mo = __DIR__ . '/../../locale/fr_FR/LC_MESSAGES/messages.mo';

        $first = compiled_gettext_catalogue($mo);

        self::assertIsString($first);
        self::assertFileExists($first);
        self::assertSame($first, compiled_gettext_catalogue($mo), 'the same catalogue asks for the same name');

        $entries = include $first;

        self::assertIsArray($entries);
        self::assertNotEmpty($entries);
    }

    #[RunInSeparateProcess]
    public function testCatalogueIsReadOncePerLanguage(): void
    {
        AmpConfig::set('lang', 'fr_FR', true);
        load_gettext();
        $first = TranslatorFunctions::getTranslator();

        load_gettext();

        self::assertSame($first, TranslatorFunctions::getTranslator());
    }

    #[RunInSeparateProcess]
    public function testChangingLanguageReplacesTheCatalogue(): void
    {
        // someone saving a new language preference calls load_gettext() again mid-request, and the
        // rest of the page has to come out in the language they just picked
        AmpConfig::set('lang', 'fr_FR', true);
        load_gettext();

        self::assertSame('Inconnu (orphelin)', T_('Unknown (Orphaned)'));

        AmpConfig::set('lang', 'de_DE', true);
        load_gettext();

        self::assertSame('Unbekannt (Verwaist)', T_('Unknown (Orphaned)'));
    }

    #[RunInSeparateProcess]
    public function testCompiledCatalogueTranslatesWhatTheBinaryOneDid(): void
    {
        // the point of compiling is speed, so the two have to answer identically
        $mo = __DIR__ . '/../../locale/fr_FR/LC_MESSAGES/messages.mo';

        $fromBinary = Translator::createFromTranslations((new MoLoader())->loadFile($mo));
        $compiled   = compiled_gettext_catalogue($mo);

        self::assertIsString($compiled);

        $fromCompiled = (new Translator())->loadTranslations($compiled);

        foreach (['Unknown (Orphaned)', 'Albums', 'Playlists'] as $msgid) {
            self::assertSame($fromBinary->gettext($msgid), $fromCompiled->gettext($msgid), $msgid);
        }
    }

    #[RunInSeparateProcess]
    public function testFirstTranslationLoadsTheCatalogue(): void
    {
        AmpConfig::set('lang', 'fr_FR', true);

        self::assertFalse(function_exists('__'), 'the fixture starts with no catalogue registered');
        self::assertSame('Inconnu (orphelin)', T_('Unknown (Orphaned)'));
        self::assertTrue(function_exists('__'));
    }

    /**
     * Reading the catalogue costs about nine milliseconds, so a request that renders no text must
     * not pay for it. The handler is left holding only the check that gettext is installed at all.
     */
    #[RunInSeparateProcess]
    public function testInitialisationLeavesTheCatalogueUnread(): void
    {
        self::assertFalse(function_exists('__'), 'the fixture starts with no catalogue registered');

        (new InitializationHandlerGetText())->init();

        self::assertFalse(function_exists('__'));
    }

    #[RunInSeparateProcess]
    public function testSourceLanguageNeedsNoCatalogue(): void
    {
        // english is the source language and ships no .mo, so an english install reads nothing
        // either way. The saving this buys is for everyone else.
        AmpConfig::set('lang', 'en_US', true);

        self::assertSame('Unknown (Orphaned)', T_('Unknown (Orphaned)'));
        self::assertFalse(function_exists('__'));
    }

    #[RunInSeparateProcess]
    public function testStringOutsideTheCatalogueKeepsItsSourceText(): void
    {
        AmpConfig::set('lang', 'fr_FR', true);

        self::assertSame('ALBUM', T_('ALBUM'));
    }

    #[RunInSeparateProcess]
    public function testUncataloguedLanguageKeepsWhateverIsAlreadyLoaded(): void
    {
        // load_gettext() has never unregistered a catalogue, so switching to a language with no
        // .mo behind it leaves the previous one translating. That predates lazy loading and is
        // kept as it was: the language list Ampache offers is built from the locale directory,
        // so every language a listener can actually pick has a catalogue.
        AmpConfig::set('lang', 'fr_FR', true);
        load_gettext();

        AmpConfig::set('lang', 'zz_ZZ', true);
        load_gettext();

        self::assertSame('Inconnu (orphelin)', T_('Unknown (Orphaned)'));
    }
}
