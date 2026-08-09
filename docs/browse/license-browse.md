# License Browse

This page lists the filters and sorts the `license` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** Licenses have no rating or favourite of their own, so this browse has none of the `rating` or `user_flag` filters and sorts the other types carry.

## API methods using this browse

`licenses`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value  | Description                                                                                                                            |
|-------------------|--------|----------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string | Alias of `like`.                                                                                                                       |
| `equal`           | string | The license name is exactly this value. Matching is case insensitive.                                                                  |
| `exact_match`     | string | Alias of `equal`.                                                                                                                      |
| `hidden`          | 0 or 1 | Send `1` for licenses hidden from the edit menus (sort order `0`), `0` for the ones offered there.                                     |
| `id`              | array  | Only these license ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string | The license name contains this value.                                                                                                  |
| `not_like`        | string | The license name does not contain this value.                                                                                          |
| `not_starts_with` | string | The license name does not start with this value.                                                                                       |
| `regex_match`     | regex  | The license name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                |
| `regex_not_match` | regex  | The license name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                             |
| `starts_with`     | string | The license name starts with this value.                                                                                               |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort            | Description                                                          |
|-----------------|----------------------------------------------------------------------|
| `external_link` | The license URL.                                                     |
| `id`            | The license id, which is the order they were created in.             |
| `name`          | License name.                                                        |
| `order`         | The sort order set on the license. `0` hides it from the edit menus. |
| `title`         | Alias of `name`.                                                     |
