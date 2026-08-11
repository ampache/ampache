# Podcast Episode Browse

This page lists the filters and sorts the `podcast_episode` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

## API methods using this browse

`browse`, `podcast_episodes`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value          | Description                                                                                                                            |
|-------------------|----------------|----------------------------------------------------------------------------------------------------------------------------------------|
| `add_gt`          | UNIX timestamp | Only episodes added to the catalog at or after this time.                                                                              |
| `add_lt`          | UNIX timestamp | Only episodes added to the catalog at or before this time.                                                                             |
| `alpha_match`     | string         | Alias of `like`.                                                                                                                       |
| `catalog`         | catalog id     | Only episodes in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.                            |
| `catalog_enabled` | none           | Only episodes in an enabled catalog. Needs no value.                                                                                   |
| `equal`           | string         | The episode title is exactly this value. Matching is case insensitive.                                                                 |
| `exact_match`     | string         | Alias of `equal`.                                                                                                                      |
| `id`              | array          | Only these episode ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string         | The episode title contains this value.                                                                                                 |
| `not_like`        | string         | The episode title does not contain this value.                                                                                         |
| `not_starts_with` | string         | The episode title does not start with this value.                                                                                      |
| `podcast`         | podcast id     | Only episodes of this podcast.                                                                                                         |
| `regex_match`     | regex          | The episode title matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                               |
| `regex_not_match` | regex          | The episode title does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                            |
| `starts_with`     | string         | The episode title starts with this value.                                                                                              |
| `unplayed`        | 1              | Only episodes that have never been played. The value must be `1`; anything else, including an empty value, is ignored.                 |
| `user_catalog`    | none           | Only episodes in a catalog the current user is allowed to see. Needs no value.                                                         |
| `user_flag`       | 0 or 1         | Send `1` for episodes the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5         | Only episodes the current user rated this value. Send `0` for episodes they have not rated at all.                                     |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `addition_time`    | When it was added to the catalog.                                                                                        |
| `author`           | Episode author.                                                                                                          |
| `catalog`          | The catalog id it belongs to.                                                                                            |
| `category`         | Episode category.                                                                                                        |
| `id`               | The episode id, which is the order they were created in.                                                                 |
| `last_played`      | When it was last played. Anything never played has no value, so it groups first ascending and last descending.           |
| `name`             | Episode title.                                                                                                           |
| `podcast`          | The podcast id, then publication date.                                                                                   |
| `pubdate`          | When the episode was published.                                                                                          |
| `rand`             | Random order, applied per request. Paging through it repeats and skips episodes, so ask for everything in one call.      |
| `rating`           | Your own rating, then when you set it. Episodes you have not rated group together.                                       |
| `state`            | Processing state of the episode.                                                                                         |
| `time`             | Episode running time in seconds.                                                                                         |
| `title`            | Alias of `name`.                                                                                                         |
| `total_count`      | How many times it has been played.                                                                                       |
| `total_skip`       | How many times it has been skipped.                                                                                      |
| `update_time`      | When its tags were last updated.                                                                                         |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
