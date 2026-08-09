# Album Browse

This page lists the filters and sorts the `album` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** An album row is per catalog, so the same album found in two catalogs is two albums with two ids.

**NOTE** When the per-user `album_group` preference is off the web interface browses [album disks](https://ampache.org/api/browse/album_disk-browse) instead; the `albums` method never changes shape with that preference.

## API methods using this browse

`albums`, `artist_albums`, `browse`, `genre_albums`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value          | Description                                                                                                                          |
|-------------------|----------------|--------------------------------------------------------------------------------------------------------------------------------------|
| `add_gt`          | UNIX timestamp | Only albums with a song added to the catalog at or after this time.                                                                  |
| `add_lt`          | UNIX timestamp | Only albums with a song added to the catalog at or before this time.                                                                 |
| `album_artist`    | artist id      | Only albums whose album artist is this artist. Send `0` for albums that have no album artist.                                        |
| `alpha_match`     | string         | Alias of `like`.                                                                                                                     |
| `artist`          | artist id      | Only albums this artist appears on, as the album artist or on one of the songs.                                                      |
| `catalog`         | catalog id     | Only albums in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.                            |
| `catalog_enabled` | none           | Only albums in an enabled catalog. Needs no value.                                                                                   |
| `equal`           | string         | The album name is exactly this value. Matching is case insensitive.                                                                  |
| `exact_match`     | string         | Alias of `equal`.                                                                                                                    |
| `genre`           | genre id       | Only albums tagged with this genre.                                                                                                  |
| `id`              | array          | Only these album ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string         | The album name contains this value.                                                                                                  |
| `mood`            | mood id        | Only albums tagged with this mood.                                                                                                   |
| `no_genre`        | none           | Only albums with no genre at all. Needs no value, and clears any `genre` filter.                                                     |
| `no_tag`          | none           | Alias of `no_genre`.                                                                                                                 |
| `not_like`        | string         | The album name does not contain this value.                                                                                          |
| `not_starts_with` | string         | The album name does not start with this value.                                                                                       |
| `regex_match`     | regex          | The album name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                |
| `regex_not_match` | regex          | The album name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                             |
| `song_artist`     | artist id      | Only albums with a song credited to this artist. Send `0` for albums with no such song.                                              |
| `starts_with`     | string         | The album name starts with this value.                                                                                               |
| `tag`             | genre id       | Alias of `genre`.                                                                                                                    |
| `unplayed`        | 1              | Only albums that have never been played. The value must be `1`; anything else, including an empty value, is ignored.                 |
| `update_gt`       | UNIX timestamp | Only albums with a song whose tags were updated at or after this time.                                                               |
| `update_lt`       | UNIX timestamp | Only albums with a song whose tags were updated at or before this time.                                                              |
| `user_catalog`    | none           | Only albums in a catalog the current user is allowed to see. Needs no value.                                                         |
| `user_flag`       | 0 or 1         | Send `1` for albums the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5         | Only albums the current user rated this value. Send `0` for albums they have not rated at all.                                       |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort                      | Description                                                                                                              |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------|
| `addition_time`           | When it was added to the catalog.                                                                                        |
| `album_artist`            | Album artist name.                                                                                                       |
| `album_artist_album_sort` | Album artist name, then whatever the server's `album_sort` setting says, which is album name and year by default.        |
| `album_artist_title`      | Album artist name, then album name.                                                                                      |
| `artist`                  | Artist name.                                                                                                             |
| `barcode`                 | Album barcode.                                                                                                           |
| `catalog`                 | The catalog id it belongs to.                                                                                            |
| `catalog_number`          | Album catalog number.                                                                                                    |
| `disk_count`              | How many disks the album has.                                                                                            |
| `generic_artist`          | Album artist name, falling back to the artist of a song on the album when the album has no album artist.                 |
| `id`                      | The album id, which is the order they were created in.                                                                   |
| `name`                    | Album name.                                                                                                              |
| `name_original_year`      | Album name, then original release year, falling back to the release year.                                                |
| `name_year`               | Album name, then release year.                                                                                           |
| `original_year`           | Original release year, falling back to the release year, then when it was added.                                         |
| `rand`                    | Random order, applied per request. Paging through it repeats and skips albums, so ask for everything in one call.        |
| `rating`                  | Your own rating, then when you set it. Albums you have not rated group together.                                         |
| `release_status`          | Album release status, e.g. `official` or `bootleg`.                                                                      |
| `release_type`            | Album release type, e.g. `album`, `ep` or `single`.                                                                      |
| `song_count`              | How many songs it has.                                                                                                   |
| `subtitle`                | Album subtitle.                                                                                                          |
| `time`                    | Total running time of the album in seconds.                                                                              |
| `title`                   | Alias of `name`.                                                                                                         |
| `total_count`             | How many times it has been played.                                                                                       |
| `user_flag`               | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating`        | When you added it to your favourites, then your rating.                                                                  |
| `userflag`                | Alias of `user_flag`.                                                                                                    |
| `version`                 | Album version.                                                                                                           |
| `year`                    | Release year, then when it was added to the catalog.                                                                     |
