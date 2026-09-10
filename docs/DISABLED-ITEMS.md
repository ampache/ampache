# Disabled items

`enabled` answers one question for an album, an artist, a song, a video and a podcast episode: may this be
listed and played? A row with `enabled = 0` has been withdrawn. It is not deleted, so the playlist entries,
ratings and play history that point at it survive, which is why a takedown request has an answer that does
not destroy anything.

Withdrawing sits with **catalog managers** (access level 75), the same people who can already disable a
song. Everyone below that level is told the item does not exist.

## What each level sees

Measured on an item with `enabled = 0`. The control in every case is the same item with `enabled = 1`,
which stays visible to everyone.

| Surface | 25 listener | 50 content manager | 75 catalog manager |
| --- | --- | --- | --- |
| Browse, sidebar, home widgets | absent | absent | listed, with a marker |
| Search and smart lists | absent | absent | listed; the `Enabled` rule finds them |
| Object page reached by a direct link | `does not exist` | `does not exist` | shown, with a banner |
| API `album` `artist` `song` by id | refused | refused | returned |
| API `albums` `artists` `index` `list` `stats` `advanced_search` | absent | absent | returned |
| API `album_songs` `artist_songs` `artist_albums` `browse` | absent | absent | returned |
| Subsonic, OpenSubsonic, UPnP, DAAP | absent | absent | absent |
| Playback (`play/index.php`) | `404 File disabled` | `404 File disabled` | `404 File disabled` |
| A public share link | refused | refused | refused |
| Editing the state | refused | refused | allowed |

Three rows are not level-aware on purpose. The device protocols have no logged-in user to hold a level, so
they filter unconditionally. Playback answers to `song`.`enabled` alone, whoever asks: a manager can see a
withdrawn track, not stream it. A share is a public link with nobody in particular on the other end, so it
stops working when what it points at is withdrawn, whoever created it.

Asking for withdrawn items below level 75 returns nothing rather than an error. The browse carries both
`enabled = 1` and whatever the caller asked for, so a request for `enabled = 0` simply matches no row.

## How the state travels

Disabling an artist disables its albums and their songs; disabling an album disables its songs. Enabling
carries back the same way.

The cascade runs **when the state is written**, and nothing reads it afterwards: every listing consults the
flag on the row it is listing and never walks up the tree. Three consequences follow, all deliberate.

- Enabling an album under a disabled artist puts that album back in the listings, while the artist stays
  out of them.
- A single song can be turned back on inside a withdrawn album, which is the only way to publish one track
  of a release that is otherwise off the shelves. The next change to the album's own state writes over it.
- Counting is binary. An item counts if it is enabled, and the state of its parent never enters into it, so
  a withdrawn album whose song was turned back on reports one song, and counts as no album itself.

Enabling an item never re-enables what it did not disable in the same breath: the write that disabled it
had already overwritten whatever a song had set for its own reasons, so refusing to restore would preserve
nothing and leave a manager clicking through a catalogue.

## Where it is enforced

`Query::_get_filter_sql()` adds the condition to every browse of an `album`, `album_disk`, `artist` or
`song` for anyone below level 75, next to the catalog filter and for the same reason: one place, so no
caller has to remember. `album_disk` carries no flag of its own and reads the one on its album.

Smart lists reach their rows without a browse and carry the same condition in `AlbumSearch`,
`AlbumDiskSearch`, `ArtistSearch` and `SongSearch`. The repository methods the device protocols read -
`ArtistRepository::getRowsByCatalogs()`, `AlbumRepository::getIdsByCatalogs()` and `getSongs()`,
`SongRepository::getEnabledIds()`, `getEnabledIdsByCatalog()` and `getEnabledByArtist()` - filter without
asking anyone's level. `getAllByArtist()` deliberately does not: re-reading the tags of a withdrawn file is
still a thing a catalogue has to do.

Anything that resolves an item by id - the object pages and the `album`, `artist` and `song` API methods -
answers with the response an id that was never there produces, so nothing tells the caller the item exists.
The tab title and `Share::is_valid()` follow the same rule, through `VisibleItemInterface`, which a private
list implements for the same reason a withdrawn release does.

## Database

`album`.`enabled` and `artist`.`enabled` arrive with database version **810011**, as
`tinyint(1) unsigned NOT NULL DEFAULT 1` plus an index, alongside the `song`, `video` and `podcast_episode`
columns of the same name that predate it.
