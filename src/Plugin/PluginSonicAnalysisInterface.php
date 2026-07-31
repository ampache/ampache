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

namespace Ampache\Plugin;

use Ampache\Repository\Model\Song;

/**
 * Similarity derived from analysing the audio itself — embeddings or fingerprints — rather than from metadata.
 *
 * This is what the OpenSubsonic `sonicSimilarity` extension means by similar, and it is deliberately a separate
 * capability from the metadata similarity `Recommendation` provides through last.fm: the two answer different
 * questions and must not stand in for one another. The extension is only advertised while a plugin of this type is
 * installed and enabled, so a server without an analysis backend reports it as unsupported instead of guessing.
 *
 * https://opensubsonic.netlify.app/docs/extensions/sonicsimilarity/
 */
interface PluginSonicAnalysisInterface extends AmpachePluginInterface
{
    /**
     * get_sonic_path
     *
     * A route of songs leading from one recording to another through similarity space, ordered start to end. Each
     * step's `similarity` is measured against the *start* song, per the spec, not against the previous step, and is
     * -1.0 when the backend only scores the route as a whole rather than each hop.
     *
     * Returns an empty list when the backend cannot connect the two.
     *
     * @return list<array{'id': int, 'similarity': float}>
     */
    public function get_sonic_path(Song $start, Song $end, int $limit): array;

    /**
     * get_sonic_similar_songs
     *
     * Songs that sound like the given one, most similar first. `similarity` is normalised to [0,1] where 1.0 is the
     * same recording; a plugin whose backend scores on another scale must map it before returning. Return -1.0 when
     * the backend gives no comparable score, which is the value the spec reserves for exactly that.
     *
     * @return list<array{'id': int, 'similarity': float}>
     */
    public function get_sonic_similar_songs(Song $song, int $limit): array;
}
