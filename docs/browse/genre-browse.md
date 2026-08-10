# Genre Browse

This page lists the filters and sorts the `tag` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** Genres are stored as tags, so the browse type is `tag` and the `tag` filters and sorts are aliases of the `genre` ones.

## API methods using this browse

`genres`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value    | Description                                                                                                                                                             |
|-------------------|----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string   | Alias of `like`.                                                                                                                                                        |
| `equal`           | string   | The genre name is exactly this value. Matching is case insensitive.                                                                                                     |
| `exact_match`     | string   | Alias of `equal`.                                                                                                                                                       |
| `genre`           | genre id | Only this genre id.                                                                                                                                                     |
| `hidden`          | 0 or 1   | Send `1` for hidden genres, `0` for visible ones.                                                                                                                       |
| `id`              | array    | Only these genre ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing.                                    |
| `like`            | string   | The genre name contains this value.                                                                                                                                     |
| `not_like`        | string   | The genre name does not contain this value.                                                                                                                             |
| `not_starts_with` | string   | The genre name does not start with this value.                                                                                                                          |
| `object_type`     | string   | Only genres applied to this object type: `song`, `album`, `artist` or `video`. The tag map join is not deduplicated, so a genre comes back once per object carrying it. |
| `regex_match`     | regex    | The genre name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                                                   |
| `regex_not_match` | regex    | The genre name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                                                                |
| `starts_with`     | string   | The genre name starts with this value.                                                                                                                                  |
| `tag`             | genre id | Alias of `genre`.                                                                                                                                                       |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `album`            | How many albums carry this genre.                                                                                        |
| `artist`           | How many artists carry this genre.                                                                                       |
| `genre`            | Alias of `id`.                                                                                                           |
| `id`               | The genre id, which is the order they were first seen in.                                                                |
| `name`             | Genre name.                                                                                                              |
| `rand`             | Random order, applied per request. Paging through it repeats and skips genres, so ask for everything in one call.        |
| `rating`           | Your own rating, then when you set it. Genres you have not rated group together.                                         |
| `song`             | How many songs carry this genre.                                                                                         |
| `tag`              | Alias of `id`.                                                                                                           |
| `title`            | Alias of `name`.                                                                                                         |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
| `video`            | How many videos carry this genre.                                                                                        |
