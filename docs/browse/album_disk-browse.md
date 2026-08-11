# Album Disk Browse

This page lists the filters and sorts the `album_disk` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** **API8 only.** API3 to API6 have no album disk methods, so a client that wants disks has to ask for them by name.

**NOTE** A disk has no name, dates or tags of its own; text filters, genre filters and the date filters all reach through to its album and songs.

## API methods using this browse

`album_disks`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value          | Description                                                                                                                               |
|-------------------|----------------|-------------------------------------------------------------------------------------------------------------------------------------------|
| `add_gt`          | UNIX timestamp | Only disks with a song added to the catalog at or after this time.                                                                        |
| `add_lt`          | UNIX timestamp | Only disks with a song added to the catalog at or before this time.                                                                       |
| `album`           | album id       | Only the disks of this album.                                                                                                             |
| `album_artist`    | artist id      | Only album disks whose album artist is this artist. Send `0` for album disks that have no album artist.                                   |
| `alpha_match`     | string         | Alias of `like`.                                                                                                                          |
| `artist`          | artist id      | Only disks whose album has this artist on it, as the album artist or on a song.                                                           |
| `catalog`         | catalog id     | Only album disks in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.                            |
| `catalog_enabled` | none           | Only album disks in an enabled catalog. Needs no value.                                                                                   |
| `equal`           | string         | The album name is exactly this value. Matching is case insensitive.                                                                       |
| `exact_match`     | string         | Alias of `equal`.                                                                                                                         |
| `genre`           | genre id       | Only disks whose album is tagged with this genre.                                                                                         |
| `id`              | array          | Only these album disk ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string         | The album name contains this value.                                                                                                       |
| `no_genre`        | none           | Only disks whose album has no genre at all. Needs no value.                                                                               |
| `no_tag`          | none           | Alias of `no_genre`.                                                                                                                      |
| `not_like`        | string         | The album name does not contain this value.                                                                                               |
| `not_starts_with` | string         | The album name does not start with this value.                                                                                            |
| `regex_match`     | regex          | The album name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                     |
| `regex_not_match` | regex          | The album name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                                  |
| `song_artist`     | artist id      | Only album disks with a song credited to this artist. Send `0` for album disks with no such song.                                         |
| `starts_with`     | string         | The album name starts with this value.                                                                                                    |
| `tag`             | genre id       | Alias of `genre`.                                                                                                                         |
| `unplayed`        | 1              | Only album disks that have never been played. The value must be `1`; anything else, including an empty value, is ignored.                 |
| `update_gt`       | UNIX timestamp | Only disks with a song whose tags were updated at or after this time.                                                                     |
| `update_lt`       | UNIX timestamp | Only disks with a song whose tags were updated at or before this time.                                                                    |
| `user_catalog`    | none           | Only album disks in a catalog the current user is allowed to see. Needs no value.                                                         |
| `user_flag`       | 0 or 1         | Send `1` for album disks the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5         | Only album disks the current user rated this value. Send `0` for album disks they have not rated at all.                                  |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort                      | Description                                                                                                              |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------|
| `addition_time`           | When the album was added to the catalog. A disk has no addition time of its own.                                         |
| `album_artist`            | Album artist name.                                                                                                       |
| `album_artist_album_sort` | Album artist name, then whatever the server's `album_sort` setting says, then disk number.                               |
| `album_artist_title`      | Album artist name, then album name and disk number.                                                                      |
| `album_id`                | The id of the album the disk belongs to.                                                                                 |
| `artist`                  | Artist name.                                                                                                             |
| `barcode`                 | Album barcode. Taken from the album, not the disk.                                                                       |
| `catalog`                 | The catalog id, then album name and disk number.                                                                         |
| `catalog_number`          | Album catalog number. Taken from the album, not the disk.                                                                |
| `disk`                    | Disk number, then album name and disk number again.                                                                      |
| `disk_count`              | How many disks the album has, then album name and disk number.                                                           |
| `disksubtitle`            | Disk subtitle, then album name and disk number.                                                                          |
| `generic_artist`          | Album artist name, falling back to the artist of a song on the album when the album has no album artist.                 |
| `id`                      | The album disk id, which is the order they were created in.                                                              |
| `last_played`             | When the disk was last played, then album name and disk number.                                                          |
| `name`                    | Album name, then original release year and disk number.                                                                  |
| `name_original_year`      | Album name, then original release year and disk number.                                                                  |
| `name_year`               | Album name, then release year and disk number.                                                                           |
| `original_year`           | Original release year, falling back to the release year, then when it was added.                                         |
| `rand`                    | Random order, applied per request. Paging through it repeats and skips album disks, so ask for everything in one call.   |
| `rating`                  | Your own rating, then when you set it. Album disks you have not rated group together.                                    |
| `release_status`          | Album release status. Taken from the album, not the disk.                                                                |
| `release_type`            | Album release type. Taken from the album, not the disk.                                                                  |
| `song_count`              | How many songs the disk has, then album name and disk number.                                                            |
| `subtitle`                | Album subtitle. Taken from the album, not the disk.                                                                      |
| `time`                    | Running time of the disk in seconds, then album name and disk number.                                                    |
| `title`                   | Alias of `name`.                                                                                                         |
| `total_count`             | How many times the disk has been played, then album name and disk number.                                                |
| `user_flag`               | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating`        | When you added it to your favourites, then your rating.                                                                  |
| `userflag`                | Alias of `user_flag`.                                                                                                    |
| `version`                 | Album version. Taken from the album, not the disk.                                                                       |
| `year`                    | Release year, then when the album was added to the catalog.                                                              |
