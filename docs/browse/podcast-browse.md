# Podcast Browse

This page lists the filters and sorts the `podcast` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

## API methods using this browse

`browse`, `podcasts`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a;catalog,2`

| Filter            | Value      | Description                                                                                                                            |
|-------------------|------------|----------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string     | Alias of `like`.                                                                                                                       |
| `catalog`         | catalog id | Only podcasts in this catalog. `0` is ignored rather than matching catalog zero, so it means every catalog.                            |
| `catalog_enabled` | none       | Only podcasts in an enabled catalog. Needs no value.                                                                                   |
| `equal`           | string     | The podcast title is exactly this value. Matching is case insensitive.                                                                 |
| `exact_match`     | string     | Alias of `equal`.                                                                                                                      |
| `id`              | array      | Only these podcast ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string     | The podcast title contains this value.                                                                                                 |
| `not_like`        | string     | The podcast title does not contain this value.                                                                                         |
| `not_starts_with` | string     | The podcast title does not start with this value.                                                                                      |
| `regex_match`     | regex      | The podcast title matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                               |
| `regex_not_match` | regex      | The podcast title does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                            |
| `starts_with`     | string     | The podcast title starts with this value.                                                                                              |
| `unplayed`        | 1          | Only podcasts that have never been played. The value must be `1`; anything else, including an empty value, is ignored.                 |
| `user_catalog`    | none       | Only podcasts in a catalog the current user is allowed to see. Needs no value.                                                         |
| `user_flag`       | 0 or 1     | Send `1` for podcasts the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5     | Only podcasts the current user rated this value. Send `0` for podcasts they have not rated at all.                                     |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `catalog`          | The catalog id it belongs to.                                                                                            |
| `episodes`         | How many episodes the podcast has.                                                                                       |
| `id`               | The podcast id, which is the order they were created in.                                                                 |
| `name`             | Podcast title.                                                                                                           |
| `rand`             | Random order, applied per request. Paging through it repeats and skips podcasts, so ask for everything in one call.      |
| `rating`           | Your own rating, then when you set it. Podcasts you have not rated group together.                                       |
| `title`            | Alias of `name`.                                                                                                         |
| `total_count`      | How many times the podcast's episodes have been played.                                                                  |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
| `website`          | Website address.                                                                                                         |
