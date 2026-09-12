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

namespace Ampache\Module\Catalog;

use Ampache\MockeryTestCase;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\License;
use Psr\Container\ContainerInterface;

/**
 * `license.name` is a varchar(80) meant to hold a label such as `CC BY SA`, with the url next to it in
 * `external_link`. A tag is free to carry either, and the url form used to be written straight into the name.
 */
class CatalogLicenseTagTest extends MockeryTestCase
{
    private const BMI_LINK = 'http://repertoire.bmi.com/title.asp?blnWriter=True&blnPublisher=True&blnArtist=True&keyID=3738380&ShowNbr=0&ShowSeqNbr=0&querytype=WorkID';

    /** @var array<string, mixed> what a scan hands the filter, minus the licence under test */
    private const TAGS = [
        'catalog' => 1,
        'file' => '/music/track.flac',
        'title' => 'track',
        'year' => 2026,
        'disk' => 1,
        'disksubtitle' => null,
        'mode' => 'vbr',
        'time' => 100,
        'track' => 1,
        'artist' => 'an artist',
        'album' => 'an album',
        'albumartist' => 'an album artist',
        'release_type' => 'album',
        'mood' => [],
        'replaygain_track_gain' => null,
        'replaygain_track_peak' => null,
        'replaygain_album_gain' => null,
        'replaygain_album_peak' => null,
        'r128_track_gain' => null,
        'r128_album_gain' => null,
        'barcode' => null,
        'catalog_number' => null,
        'version' => null,
    ];

    public function testALicenceNameTooLongForTheColumnIsCut(): void
    {
        $license = $this->licenseFromTag(str_repeat('a', 200));

        self::assertSame(80, strlen($license->getName()));
        self::assertSame('', $license->getExternalLink());
    }

    public function testAPlainLicenceNameStaysTheName(): void
    {
        $license = $this->licenseFromTag('CC BY SA');

        self::assertSame('CC BY SA', $license->getName());
        self::assertSame('', $license->getExternalLink());
    }

    /**
     * The whole point: a url is what external_link is for, and the row has to stay findable next scan
     */
    public function testAUrlGoesToTheLinkAndLeavesAReadableName(): void
    {
        $license = $this->licenseFromTag(self::BMI_LINK);

        self::assertSame(self::BMI_LINK, $license->getExternalLink());
        self::assertSame('repertoire.bmi.com', $license->getName());
    }

    /**
     * A name held html escaped never equals the tag it came from, so `find()` misses and the scan creates the
     * same licence again, every single pass
     */
    public function testTheNameIsStoredAsTheTagWroteIt(): void
    {
        $license = $this->licenseFromTag('Rock & Roll Licence');

        self::assertSame('Rock & Roll Licence', $license->getName());
    }

    /**
     * Runs the tag through the catalog filter and hands back the licence it built
     */
    private function licenseFromTag(string $tagValue): License
    {
        $created = null;

        $licenseRepository = $this->mock(LicenseRepositoryInterface::class);
        $licenseRepository->shouldReceive('find')->andReturnNull();
        $licenseRepository->shouldReceive('prototype')->andReturnUsing(
            function () use (&$created): License {
                $repository = $this->mock(LicenseRepositoryInterface::class);
                $repository->shouldReceive('persist')->andReturn(1);

                return $created = new License($repository);
            }
        );

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->with(LicenseRepositoryInterface::class)->andReturn($licenseRepository);
        $GLOBALS['dic'] = $dic;

        Catalog::filter_tag_results(self::TAGS + ['license' => $tagValue]);

        self::assertInstanceOf(License::class, $created);

        return $created;
    }
}
