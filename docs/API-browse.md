# API Browse methods

A browse method returns many items that can be filtered and sorted further, so `artists` is a browse
and `artist` is not. Browse methods use the Ampache Browse class, which lets a client ask for a
narrower or differently ordered list without running a search or post-processing the response.

Every browse method takes two extra parameters on top of its own: `cond` and `sort`.

## cond

Filter the objects the browse returns.

Send comma separated filter and value pairs, and split additional filters with `;`

e.g. `&cond=artist,1240;catalog,2`

A filter a browse does not list is ignored, so check the page for the type you are browsing.

Example:

* The `songs` method uses a song browse to return `song` objects.
* Filtering it by `genre` returns every song with that genre.

e.g. `https://music.com.au/server/json.server.php?action=songs&auth=eeb9f1b6056246a7d563f479f518bb34&cond=genre,111`

A method that already sets a filter of its own is overwritten by the same filter in `cond`, which can
change the response in ways the method never intended. `genre_artists?filter=215&cond=tag,111` returns
the artists for genre 111, not 215.

These filters carry no value, so `filter,` with an empty value is enough: `catalog_enabled`, `hide_dupe_smartlist`, `no_genre`, `no_tag`, `smartlist`, `user_catalog`

Every other filter needs one. `unplayed` in particular only works as `cond=unplayed,1`; an empty value is read as `0` and the filter is dropped.

## sort

Change the order of the response.

Send the sort name and the direction, `ASC` or `DESC`.

e.g. `https://music.com.au/server/json.server.php?action=users&auth=f57766d256df0ad5e5ec163d35f05a21&sort=username,DESC`

**NOTE** Only one sort is applied to a browse. Sending a second replaces the first.

The default sort is usually `name`, ascending. Each method's `sort` docstring names its own default.

A sort the browse does not list is ignored and the default is kept, so nothing tells the client that
the order it asked for was refused.

## Browse types and available methods

Each page lists every filter and sort that browse accepts, and what each one does.

| Browse                                                                          | Type              | API methods                                                                                          |
|---------------------------------------------------------------------------------|-------------------|------------------------------------------------------------------------------------------------------|
| [Album Browse](https://ampache.org/api/browse/album-browse)                     | `album`           | `albums`, `artist_albums`, `browse`, `genre_albums`                                                  |
| [Album Disk Browse](https://ampache.org/api/browse/album_disk-browse)           | `album_disk`      | `album_disks`                                                                                        |
| [Artist Browse](https://ampache.org/api/browse/artist-browse)                   | `artist`          | `artists`, `browse`, `genre_artists`, `label_artists`                                                |
| [Catalog Browse](https://ampache.org/api/browse/catalog-browse)                 | `catalog`         | `browse`, `catalogs`                                                                                 |
| [Folder Browse](https://ampache.org/api/browse/folder-browse)                   | `folder`          | `folders`                                                                                            |
| [Follower Browse](https://ampache.org/api/browse/follower-browse)               | `follower`        | `followers`                                                                                          |
| [Genre Browse](https://ampache.org/api/browse/genre-browse)                     | `tag`             | `genres`                                                                                             |
| [Label Browse](https://ampache.org/api/browse/label-browse)                     | `label`           | `labels`                                                                                             |
| [License Browse](https://ampache.org/api/browse/license-browse)                 | `license`         | `licenses`                                                                                           |
| [Live Stream Browse](https://ampache.org/api/browse/live_stream-browse)         | `live_stream`     | `live_streams`                                                                                       |
| [Playlist Browse](https://ampache.org/api/browse/playlist-browse)               | `playlist`        | `playlists`, `user_playlists`                                                                        |
| [Playlist Search Browse](https://ampache.org/api/browse/playlist_search-browse) | `playlist_search` | `index`, `list`, `playlist_folder_items`, `playlists`, `stats`                                       |
| [Podcast Browse](https://ampache.org/api/browse/podcast-browse)                 | `podcast`         | `browse`, `podcasts`                                                                                 |
| [Podcast Episode Browse](https://ampache.org/api/browse/podcast_episode-browse) | `podcast_episode` | `browse`, `podcast_episodes`                                                                         |
| [Share Browse](https://ampache.org/api/browse/share-browse)                     | `share`           | `shares`                                                                                             |
| [Smartlist Browse](https://ampache.org/api/browse/smartplaylist-browse)         | `smartplaylist`   | `smartlists`, `user_smartlists`                                                                      |
| [Song Browse](https://ampache.org/api/browse/song-browse)                       | `song`            | `album_disk_songs`, `album_songs`, `artist_songs`, `browse`, `genre_songs`, `license_songs`, `songs` |
| [User Browse](https://ampache.org/api/browse/user-browse)                       | `user`            | `users`                                                                                              |
| [Video Browse](https://ampache.org/api/browse/video-browse)                     | `video`           | `browse`, `videos`                                                                                   |

**NOTE** A browse usually maps to one database table. `playlist_search` is the exception: it reads
`playlist` and `search` together so playlists and smartlists arrive as one list, with smartlist ids
prefixed, so search `2256` is returned as `smart_2256`.

**NOTE** `album_artist` and `song_artist` are the artist browse with that filter already applied, so
they take the artist filters and sorts.

**NOTE (API8)** `catalog` is an optional filter on the `album_artist`, `artist`, `album`, `album_disk`
and `podcast` browse types instead of a required parameter. Send it to restrict the children to one
catalog, or leave it out to get them from every catalog you can see. An album, disk or podcast belongs
to a single catalog and an artist reaches its catalogs through `catalog_map`, so the parent object
never needed a catalog to be addressed. API6 keeps the parameter mandatory, because Ampache7 serves
that version too.

## Which browse takes which filter

A filter sent to a browse that does not list it is ignored, and logged as an unknown filter.

| Filter                | Browse types                                                                                                                                                                                                     |
|-----------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `access`              | `user`                                                                                                                                                                                                           |
| `add_gt`              | `album`, `album_disk`, `artist`, `podcast_episode`, `song`, `video`                                                                                                                                              |
| `add_lt`              | `album`, `album_disk`, `artist`, `podcast_episode`, `song`, `video`                                                                                                                                              |
| `album`               | `album_disk`, `song`                                                                                                                                                                                             |
| `album_artist`        | `album`, `album_disk`, `artist`                                                                                                                                                                                  |
| `album_disk`          | `song`                                                                                                                                                                                                           |
| `alpha_match`         | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `artist`              | `album`, `album_disk`, `song`                                                                                                                                                                                    |
| `catalog`             | `album`, `album_disk`, `artist`, `live_stream`, `podcast`, `podcast_episode`, `song`, `video`                                                                                                                    |
| `catalog_enabled`     | `album`, `album_disk`, `artist`, `live_stream`, `podcast`, `podcast_episode`, `song`, `video`                                                                                                                    |
| `disabled`            | `user`                                                                                                                                                                                                           |
| `disk`                | `song`                                                                                                                                                                                                           |
| `email`               | `user`                                                                                                                                                                                                           |
| `enabled`             | `catalog`, `song`                                                                                                                                                                                                |
| `equal`               | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `exact_match`         | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `follow_user`         | `follower`                                                                                                                                                                                                       |
| `fullname`            | `user`                                                                                                                                                                                                           |
| `gather_type`         | `catalog`                                                                                                                                                                                                        |
| `gather_types`        | `catalog`                                                                                                                                                                                                        |
| `genre`               | `album`, `album_disk`, `artist`, `genre`, `song`, `video`                                                                                                                                                        |
| `hidden`              | `genre`, `license`                                                                                                                                                                                               |
| `hide_dupe_smartlist` | `playlist_search`                                                                                                                                                                                                |
| `id`                  | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `smartplaylist`, `song`, `user`, `video`          |
| `int_id`              | `folder`                                                                                                                                                                                                         |
| `label`               | `artist`                                                                                                                                                                                                         |
| `license`             | `song`                                                                                                                                                                                                           |
| `like`                | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `mood`                | `album`, `artist`, `song`, `video`                                                                                                                                                                               |
| `name`                | `user`                                                                                                                                                                                                           |
| `no_genre`            | `album`, `album_disk`, `artist`, `song`, `video`                                                                                                                                                                 |
| `no_tag`              | `album`, `album_disk`, `artist`, `song`, `video`                                                                                                                                                                 |
| `not_like`            | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `not_starts_with`     | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `object_type`         | `genre`, `share`                                                                                                                                                                                                 |
| `playlist_open`       | `playlist`, `playlist_search`, `smartplaylist`                                                                                                                                                                   |
| `playlist_type`       | `playlist`, `playlist_search`, `smartplaylist`                                                                                                                                                                   |
| `playlist_user`       | `playlist`, `playlist_search`, `smartplaylist`                                                                                                                                                                   |
| `podcast`             | `podcast_episode`                                                                                                                                                                                                |
| `regex_match`         | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `regex_not_match`     | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `smartlist`           | `playlist_search`                                                                                                                                                                                                |
| `song_artist`         | `album`, `album_disk`, `artist`                                                                                                                                                                                  |
| `starts_with`         | `album`, `album_disk`, `artist`, `catalog`, `folder`, `genre`, `label`, `license`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `share`, `smartplaylist`, `song`, `user`, `video` |
| `tag`                 | `album`, `album_disk`, `artist`, `genre`, `song`, `video`                                                                                                                                                        |
| `top50`               | `song`                                                                                                                                                                                                           |
| `unplayed`            | `album`, `album_disk`, `artist`, `podcast`, `podcast_episode`, `song`                                                                                                                                            |
| `update_gt`           | `album`, `album_disk`, `artist`, `song`, `video`                                                                                                                                                                 |
| `update_lt`           | `album`, `album_disk`, `artist`, `song`, `video`                                                                                                                                                                 |
| `user`                | `catalog`, `follower`, `share`                                                                                                                                                                                   |
| `user_catalog`        | `album`, `album_disk`, `artist`, `live_stream`, `podcast`, `podcast_episode`, `song`, `video`                                                                                                                    |
| `user_flag`           | `album`, `album_disk`, `artist`, `folder`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `smartplaylist`, `song`, `video`                                                          |
| `user_rating`         | `album`, `album_disk`, `artist`, `folder`, `live_stream`, `playlist`, `playlist_search`, `podcast`, `podcast_episode`, `smartplaylist`, `song`, `video`                                                          |
| `username`            | `user`                                                                                                                                                                                                           |
