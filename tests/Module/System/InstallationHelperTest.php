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

namespace Ampache\Module\System;

use Ampache\MockeryTestCase;
use Ampache\Module\System\Update\UpdaterInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Override;

class InstallationHelperTest extends MockeryTestCase
{
    private string $file = '';
    private InstallationHelper $subject;

    public function testAnAlreadyPrefixedTargetIsNotPrefixedTwice(): void
    {
        $rules = 'RewriteRule ^image\.php$ /ampache/image.php [R=302,L]';

        self::assertSame($rules, $this->subject->install_check_rewrite_rules($this->writeRules($rules), '/ampache', true));
    }

    /**
     * A '-' substitution means the rule only sets flags. Prefixing it produces '/ampache-', which is
     * a path no request matches, so the rule silently stops applying on a subdirectory install.
     */
    public function testFlagOnlyRulesKeepTheirSubstitution(): void
    {
        $rules = implode("\n", [
            'RewriteRule ^(bin|config|vendor)(/|$) - [F,L]',
            'RewriteRule (^|/)\. - [F,L]',
            'RewriteRule (.*) - [R=400,L]',
        ]);

        self::assertSame(
            $rules,
            $this->subject->install_check_rewrite_rules($this->writeRules($rules), '/ampache', true)
        );
    }

    public function testRealTargetsGainTheWebPath(): void
    {
        self::assertSame(
            'RewriteRule ^image\.php$ /ampache/image.php?action=show_user_avatar [R=302,L]',
            $this->subject->install_check_rewrite_rules(
                $this->writeRules('RewriteRule ^image\.php$ /image.php?action=show_user_avatar [R=302,L]'),
                '/ampache',
                true
            )
        );
    }

    public function testShippedRestRulesValidateAgainstTheirOwnDist(): void
    {
        $dist = __DIR__ . '/../../../public/rest/.htaccess.dist';
        $file = $this->writeRules((string) file_get_contents($dist));
        copy($dist, $file . '.dist');

        self::assertTrue($this->subject->install_check_rewrite_rules($file, ''));
    }

    /**
     * A file left over from an older release keeps the right web path on the rules it does have, so the prefix
     * test alone reports it as configured while every rule added since is missing.
     */
    public function testValidationFailsForAFileMissingRulesTheDistDeclares(): void
    {
        $file = $this->writeRules('RewriteRule ^old\.php$ /ampache/old.php [L]');
        file_put_contents(
            $file . '.dist',
            implode("\n", [
                'RewriteRule ^old\.php$ /old.php [L]',
                'RewriteRule ^new\.php$ /new.php [L]',
            ])
        );

        self::assertFalse($this->subject->install_check_rewrite_rules($file, '/ampache'));
    }

    public function testValidationFailsForAnUnprefixedTarget(): void
    {
        self::assertFalse(
            $this->subject->install_check_rewrite_rules($this->writeRules('RewriteRule ^image\.php$ /image.php [R=302,L]'), '/ampache')
        );
    }

    public function testValidationFailsWhenTheFileHasNotBeenWrittenYet(): void
    {
        self::assertFalse(
            $this->subject->install_check_rewrite_rules(sys_get_temp_dir() . '/nonexistent-htaccess', '')
        );
    }

    public function testValidationPassesForAFileCarryingEveryDistRule(): void
    {
        $file = $this->writeRules(
            implode("\n", [
                'RewriteRule ^old\.php$ /ampache/old.php [L]',
                'RewriteRule ^new\.php$ /ampache/new.php [L]',
            ])
        );
        file_put_contents(
            $file . '.dist',
            implode("\n", [
                'RewriteRule ^old\.php$ /old.php [L]',
                'RewriteRule ^new\.php$ /new.php [L]',
            ])
        );

        self::assertTrue($this->subject->install_check_rewrite_rules($file, '/ampache'));
    }

    /**
     * The same rules read in validation mode, which is what the install page reports as "configured?"
     */
    public function testValidationPassesForFlagOnlyRules(): void
    {
        self::assertTrue(
            $this->subject->install_check_rewrite_rules($this->writeRules('RewriteRule (.*) - [R=400,L]'), '/ampache')
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->subject = new InstallationHelper(
            $this->mock(UpdaterInterface::class),
            $this->mock(UpdateInfoRepositoryInterface::class),
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach ([$this->file, $this->file . '.dist'] as $path) {
            if ($this->file !== '' && file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function writeRules(string $rules): string
    {
        $this->file = (string) tempnam(sys_get_temp_dir(), 'htaccess');
        file_put_contents($this->file, $rules);

        return $this->file;
    }
}
