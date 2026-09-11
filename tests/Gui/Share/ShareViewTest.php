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
 */

namespace Ampache\Gui\Share;

use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Repository\Model\Share;
use PHPUnit\Framework\TestCase;

class ShareViewTest extends TestCase
{
    public function testAnUnknownObjectIsAnnouncedAsAPlainPage(): void
    {
        self::assertSame('website', $this->ogType('podcast_episode'));
        self::assertSame('website', $this->ogType(''));
    }

    public function testTheOpenGraphTypeFollowsTheSharedObject(): void
    {
        // a share used to announce every object as a song, so an album preview once claimed to be one too
        self::assertSame('music.album', $this->ogType('album'));
        self::assertSame('music.album', $this->ogType('album_disk'));
        self::assertSame('profile', $this->ogType('artist'));
        self::assertSame('music.playlist', $this->ogType('playlist'));
        self::assertSame('music.playlist', $this->ogType('search'));
        self::assertSame('music.song', $this->ogType('song'));
        self::assertSame('video.other', $this->ogType('video'));
    }

    private function ogType(string $objectType): string
    {
        $share              = $this->createMock(Share::class);
        $share->object_type = $objectType;

        return new ShareView('', $this->createMock(AjaxUriRetrieverInterface::class), $share)->getOgType();
    }
}
