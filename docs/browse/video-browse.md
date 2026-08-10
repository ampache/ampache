# Video Browse

This page lists the filters and sorts the `video` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

## API methods using this browse

`browse`, `videos`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value          | Description                                                                                                                          |
|-------------------|----------------|--------------------------------------------------------------------------------------------------------------------------------------|
| `add_gt`          | UNIX timestamp | Only videos added to the catalog at or after this time.                                                                              |
| `add_lt`          | UNIX timestamp | Only videos added to the catalog at or before this time.                                                                             |
| `alpha_match`     | string         | Alias of `like`.                                                                                                                     |
| `catalog`         | catalog id     | Only videos in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.                            |
| `catalog_enabled` | none           | Only videos in an enabled catalog. Needs no value.                                                                                   |
| `equal`           | string         | The video title is exactly this value. Matching is case insensitive.                                                                 |
| `exact_match`     | string         | Alias of `equal`.                                                                                                                    |
| `genre`           | genre id       | Only videos tagged with this genre.                                                                                                  |
| `id`              | array          | Only these video ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string         | The video title contains this value.                                                                                                 |
| `mood`            | mood id        | Only videos tagged with this mood.                                                                                                   |
| `no_genre`        | none           | Only videos with no genre at all. Needs no value, and clears any `genre` filter.                                                     |
| `no_tag`          | none           | Alias of `no_genre`.                                                                                                                 |
| `not_like`        | string         | The video title does not contain this value.                                                                                         |
| `not_starts_with` | string         | The video title does not start with this value.                                                                                      |
| `regex_match`     | regex          | The video title matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                               |
| `regex_not_match` | regex          | The video title does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                            |
| `starts_with`     | string         | The video title starts with this value.                                                                                              |
| `tag`             | genre id       | Alias of `genre`.                                                                                                                    |
| `update_gt`       | UNIX timestamp | Only videos whose tags were last updated at or after this time.                                                                      |
| `update_lt`       | UNIX timestamp | Only videos whose tags were last updated at or before this time.                                                                     |
| `user_catalog`    | none           | Only videos in a catalog the current user is allowed to see. Needs no value.                                                         |
| `user_flag`       | 0 or 1         | Send `1` for videos the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5         | Only videos the current user rated this value. Send `0` for videos they have not rated at all.                                       |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `addition_time`    | When it was added to the catalog.                                                                                        |
| `catalog`          | The catalog id it belongs to.                                                                                            |
| `codec`            | Video codec.                                                                                                             |
| `id`               | The video id, which is the order they were created in.                                                                   |
| `length`           | Video length in seconds.                                                                                                 |
| `name`             | Video title.                                                                                                             |
| `rand`             | Random order, applied per request. Paging through it repeats and skips videos, so ask for everything in one call.        |
| `rating`           | Your own rating, then when you set it. Videos you have not rated group together.                                         |
| `resolution`       | Video width in pixels.                                                                                                   |
| `title`            | Alias of `name`.                                                                                                         |
| `total_count`      | How many times it has been played.                                                                                       |
| `total_skip`       | How many times it has been skipped.                                                                                      |
| `update_time`      | When its tags were last updated.                                                                                         |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |

**NOTE** These sorts are listed by the browse but have no implementation, so the rows come back in the default order: `userflag`
