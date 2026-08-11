# Artist Browse

This page lists the filters and sorts the `artist` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** The `album_artist` and `song_artist` browses are this browse with the matching filter already applied, so they take the same filters and sorts.

**NOTE** An artist is shared across catalogs (there is no artist catalog column), so the catalog filters resolve through `catalog_map`.

## API methods using this browse

`artists`, `browse`, `genre_artists`, `label_artists`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value          | Description                                                                                                                           |
|-------------------|----------------|---------------------------------------------------------------------------------------------------------------------------------------|
| `add_gt`          | UNIX timestamp | Only artists with a song added to the catalog at or after this time.                                                                  |
| `add_lt`          | UNIX timestamp | Only artists with a song added to the catalog at or before this time.                                                                 |
| `album_artist`    | 0 or 1         | Send `1` for artists credited as an album artist, `0` for artists never credited as one.                                              |
| `alpha_match`     | string         | Alias of `like`.                                                                                                                      |
| `catalog`         | catalog id     | Only artists with something in this catalog, resolved through `catalog_map`. `0` means every catalog.                                 |
| `catalog_enabled` | none           | Only artists in an enabled catalog. Needs no value.                                                                                   |
| `equal`           | string         | The artist name is exactly this value. Matching is case insensitive.                                                                  |
| `exact_match`     | string         | Alias of `equal`.                                                                                                                     |
| `genre`           | genre id       | Only artists tagged with this genre.                                                                                                  |
| `id`              | array          | Only these artist ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `label`           | label id       | Only artists signed to this label.                                                                                                    |
| `like`            | string         | The artist name contains this value.                                                                                                  |
| `mood`            | mood id        | Only artists tagged with this mood.                                                                                                   |
| `no_genre`        | none           | Only artists with no genre at all. Needs no value, and clears any `genre` filter.                                                     |
| `no_tag`          | none           | Alias of `no_genre`.                                                                                                                  |
| `not_like`        | string         | The artist name does not contain this value.                                                                                          |
| `not_starts_with` | string         | The artist name does not start with this value.                                                                                       |
| `regex_match`     | regex          | The artist name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                |
| `regex_not_match` | regex          | The artist name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                             |
| `song_artist`     | 0 or 1         | Send `1` for artists credited on a song, `0` for artists never credited on one.                                                       |
| `starts_with`     | string         | The artist name starts with this value.                                                                                               |
| `tag`             | genre id       | Alias of `genre`.                                                                                                                     |
| `unplayed`        | 1              | Only artists that have never been played. The value must be `1`; anything else, including an empty value, is ignored.                 |
| `update_gt`       | UNIX timestamp | Only artists with a song whose tags were updated at or after this time.                                                               |
| `update_lt`       | UNIX timestamp | Only artists with a song whose tags were updated at or before this time.                                                              |
| `user_catalog`    | none           | Only artists with something in a catalog the current user can see. Needs no value.                                                    |
| `user_flag`       | 0 or 1         | Send `1` for artists the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5         | Only artists the current user rated this value. Send `0` for artists they have not rated at all.                                      |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `addition_time`    | When it was added to the catalog.                                                                                        |
| `album_count`      | How many albums the artist has.                                                                                          |
| `id`               | The artist id, which is the order they were created in.                                                                  |
| `last_played`      | When the artist's songs were last played.                                                                                |
| `name`             | Artist name.                                                                                                             |
| `placeformed`      | Where the artist was formed.                                                                                             |
| `rand`             | Random order, applied per request. Paging through it repeats and skips artists, so ask for everything in one call.       |
| `rating`           | Your own rating, then when you set it. Artists you have not rated group together.                                        |
| `song_count`       | How many songs it has.                                                                                                   |
| `time`             | Total running time of the artist's songs in seconds.                                                                     |
| `title`            | Alias of `name`.                                                                                                         |
| `total_count`      | How many times the artist's songs have been played.                                                                      |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
| `yearformed`       | The year the artist formed.                                                                                              |
