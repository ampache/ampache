<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

namespace Ampache\Module\User\Registration;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;

class RegistrationAgreementRendererTest extends TestCase
{
    private vfsStreamFile $vfsStream;

    private RegistrationAgreementRenderer $subject;

    protected function setUp(): void
    {
        $dir = vfsStream::setup('/');

        $this->vfsStream = new vfsStreamFile('snafu');

        $dir->addChild($this->vfsStream);

        $this->subject = new RegistrationAgreementRenderer($this->vfsStream->url());
    }

    public function testRenderFailsIfFileDoesNotExist(): void
    {
        @unlink($this->vfsStream->url());

        static::assertSame(
            '',
            $this->subject->render()
        );
    }

    public function testRenderFailsIfFileIsNotReadable(): void
    {
        chmod($this->vfsStream->url(), 0000);

        static::assertSame(
            '',
            $this->subject->render()
        );
    }

    public function testRenderReturnsFileContent(): void
    {
        $content = 'some-content';

        file_put_contents($this->vfsStream->url(), $content);

        static::assertSame(
            $content,
            $this->subject->render()
        );
    }
}
