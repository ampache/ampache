# Live Stream Browse

This page lists the filters and sorts the `live_stream` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

## API methods using this browse

`live_streams`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value      | Description                                                                                                                                |
|-------------------|------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string     | Alias of `like`.                                                                                                                           |
| `catalog`         | catalog id | Only live streams in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.                            |
| `catalog_enabled` | none       | Only live streams in an enabled catalog. Needs no value.                                                                                   |
| `equal`           | string     | The live stream name is exactly this value. Matching is case insensitive.                                                                  |
| `exact_match`     | string     | Alias of `equal`.                                                                                                                          |
| `id`              | array      | Only these live stream ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string     | The live stream name contains this value.                                                                                                  |
| `not_like`        | string     | The live stream name does not contain this value.                                                                                          |
| `not_starts_with` | string     | The live stream name does not start with this value.                                                                                       |
| `regex_match`     | regex      | The live stream name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                |
| `regex_not_match` | regex      | The live stream name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                             |
| `starts_with`     | string     | The live stream name starts with this value.                                                                                               |
| `user_catalog`    | none       | Only live streams in a catalog the current user is allowed to see. Needs no value.                                                         |
| `user_flag`       | 0 or 1     | Send `1` for live streams the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5     | Only live streams the current user rated this value. Send `0` for live streams they have not rated at all.                                 |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `catalog`          | The catalog id it belongs to.                                                                                            |
| `codec`            | Stream codec.                                                                                                            |
| `genre`            | Genre string on the stream.                                                                                              |
| `id`               | The live stream id, which is the order they were created in.                                                             |
| `name`             | Stream name.                                                                                                             |
| `rating`           | Your own rating, then when you set it. Live streams you have not rated group together.                                   |
| `site_url`         | The stream's home page.                                                                                                  |
| `title`            | Alias of `name`.                                                                                                         |
| `url`              | The stream URL.                                                                                                          |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
