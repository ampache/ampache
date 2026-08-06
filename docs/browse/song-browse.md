# Song Browse

This page lists the filters and sorts the `song` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

## API methods using this browse

`album_disk_songs`, `album_songs`, `artist_songs`, `browse`, `genre_songs`, `license_songs`, `songs`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value          | Description                                                                                                                         |
|-------------------|----------------|-------------------------------------------------------------------------------------------------------------------------------------|
| `add_gt`          | UNIX timestamp | Only songs added to the catalog at or after this time.                                                                              |
| `add_lt`          | UNIX timestamp | Only songs added to the catalog at or before this time.                                                                             |
| `album`           | album id       | Only songs from this album.                                                                                                         |
| `album_disk`      | album disk id  | Only songs on this album disk.                                                                                                      |
| `alpha_match`     | string         | Alias of `like`.                                                                                                                    |
| `artist`          | artist id      | Only songs this artist is credited on, through `artist_map`.                                                                        |
| `catalog`         | catalog id     | Only songs in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.                            |
| `catalog_enabled` | none           | Only songs in an enabled catalog. Needs no value.                                                                                   |
| `disk`            | integer        | Only songs on this disk number of their album.                                                                                      |
| `enabled`         | 0 or 1         | Send `1` for enabled songs, `0` for songs disabled by a catalog clean.                                                              |
| `equal`           | string         | The song title is exactly this value. Matching is case insensitive.                                                                 |
| `exact_match`     | string         | Alias of `equal`.                                                                                                                   |
| `genre`           | genre id       | Only songs tagged with this genre.                                                                                                  |
| `id`              | array          | Only these song ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `license`         | license id     | Only songs released under this license.                                                                                             |
| `like`            | string         | The song title contains this value.                                                                                                 |
| `no_genre`        | none           | Only songs with no genre at all. Needs no value, and clears any `genre` filter.                                                     |
| `no_tag`          | none           | Alias of `no_genre`.                                                                                                                |
| `not_like`        | string         | The song title does not contain this value.                                                                                         |
| `not_starts_with` | string         | The song title does not start with this value.                                                                                      |
| `regex_match`     | regex          | The song title matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                               |
| `regex_not_match` | regex          | The song title does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                            |
| `starts_with`     | string         | The song title starts with this value.                                                                                              |
| `tag`             | genre id       | Alias of `genre`.                                                                                                                   |
| `top50`           | artist id      | Only songs credited to this artist, joined to their play counts so a `object_count` sort can build a top 50.                        |
| `unplayed`        | 1              | Only songs that have never been played. The value must be `1`; anything else, including an empty value, is ignored.                 |
| `update_gt`       | UNIX timestamp | Only songs whose tags were last updated at or after this time.                                                                      |
| `update_lt`       | UNIX timestamp | Only songs whose tags were last updated at or before this time.                                                                     |
| `user_catalog`    | none           | Only songs in a catalog the current user is allowed to see. Needs no value.                                                         |
| `user_flag`       | 0 or 1         | Send `1` for songs the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5         | Only songs the current user rated this value. Send `0` for songs they have not rated at all.                                        |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `addition_time`    | When it was added to the catalog.                                                                                        |
| `album`            | Album name, then the song's own disk and track number.                                                                   |
| `album_disk`       | Album name, then the album disk's number and the track number.                                                           |
| `artist`           | Artist name. This is the song artist, not the album artist.                                                              |
| `catalog`          | The catalog id it belongs to.                                                                                            |
| `composer`         | Composer tag.                                                                                                            |
| `id`               | The song id, which is the order they were created in.                                                                    |
| `name`             | Song title.                                                                                                              |
| `object_count`     | How many times the songs have been played, counted per song.                                                             |
| `rand`             | Random order, applied per request. Paging through it repeats and skips songs, so ask for everything in one call.         |
| `rating`           | Your own rating, then when you set it. Songs you have not rated group together.                                          |
| `time`             | Song length in seconds.                                                                                                  |
| `title`            | Alias of `name`.                                                                                                         |
| `total_count`      | How many times it has been played.                                                                                       |
| `total_skip`       | How many times it has been skipped.                                                                                      |
| `track`            | Track number.                                                                                                            |
| `update_time`      | When its tags were last updated.                                                                                         |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
| `year`             | Release year on the song tag.                                                                                            |
