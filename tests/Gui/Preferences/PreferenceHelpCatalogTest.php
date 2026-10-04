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

namespace Ampache\Gui\Preferences;

use Ampache\Module\System\Preference;
use PHPUnit\Framework\TestCase;

class PreferenceHelpCatalogTest extends TestCase
{
    private PreferenceHelpCatalog $subject;

    public function testEveryEntryCarriesTextAndAnOptionalAbsoluteUrl(): void
    {
        foreach ($this->subject->all() as $name => $help) {
            $this->assertNotSame('', $help->text, sprintf('`%s` has empty help text', $name));
            if ($help->docUrl !== '') {
                $this->assertStringStartsWith('https://', $help->docUrl, sprintf('`%s` has a non-absolute doc url', $name));
            }
        }
    }

    public function testEveryEntryNamesAnExistingPreference(): void
    {
        foreach (array_keys($this->subject->all()) as $name) {
            $this->assertArrayHasKey(
                $name,
                Preference::DEFAULTS,
                sprintf('help is attached to `%s`, which no install would ever have', $name)
            );
        }
    }

    /**
     * A string handed to `T_()` through a variable never reaches the translators, so every text has to be
     * a literal argument where it is written.
     */
    public function testEveryTextIsWrittenSoGettextCanFindIt(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../../../src/Gui/Preferences/PreferenceHelpCatalog.php');

        $this->assertSame(
            substr_count($source, 'new PreferenceHelp('),
            substr_count($source, "new PreferenceHelp(T_('"),
            'a help text that is not a literal T_() argument is invisible to xgettext'
        );
    }

    public function testFindBuildsTheHelpObject(): void
    {
        $help = $this->subject->find('subsonic_backend');

        $this->assertInstanceOf(PreferenceHelp::class, $help);
        $this->assertSame('https://ampache.org/api/subsonic', $help->docUrl);
    }

    public function testFindReturnsNullForAPreferenceWithoutHelp(): void
    {
        $this->assertNull($this->subject->find('lastfm_challenge'));
    }

    public function testTheListIsBuiltOnceAndHandedBackIdentical(): void
    {
        $this->assertSame($this->subject->find('transcode'), $this->subject->find('transcode'));
    }

    public function testThereIsHelpForTheObscurePreferences(): void
    {
        $this->assertGreaterThan(100, count($this->subject->all()), 'the catalogue is the point of the feature');
    }

    protected function setUp(): void
    {
        $this->subject = new PreferenceHelpCatalog();
    }
}
