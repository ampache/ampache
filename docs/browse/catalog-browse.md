# Catalog Browse

This page lists the filters and sorts the `catalog` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

## API methods using this browse

`browse`, `catalogs`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value   | Description                                                                                                                            |
|-------------------|---------|----------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string  | Alias of `like`.                                                                                                                       |
| `enabled`         | 0 or 1  | Send `1` for enabled catalogs, `0` for disabled ones.                                                                                  |
| `equal`           | string  | The catalog name is exactly this value. Matching is case insensitive.                                                                  |
| `exact_match`     | string  | Alias of `equal`.                                                                                                                      |
| `gather_type`     | string  | Only catalogs gathering this media type: `music`, `video` or `podcast`.                                                                |
| `gather_types`    | array   | The array form of `gather_type`. `cond` can only send one string, so use `gather_type` from the API.                                   |
| `id`              | array   | Only these catalog ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string  | The catalog name contains this value.                                                                                                  |
| `not_like`        | string  | The catalog name does not contain this value.                                                                                          |
| `not_starts_with` | string  | The catalog name does not start with this value.                                                                                       |
| `regex_match`     | regex   | The catalog name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                |
| `regex_not_match` | regex   | The catalog name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                             |
| `starts_with`     | string  | The catalog name starts with this value.                                                                                               |
| `user`            | user id | Only catalogs this user is allowed to see.                                                                                             |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `catalog_type`     | Catalog backend: `local`, `beets`, `remote`, `seafile`, `subsonic` or `dropbox`.                                         |
| `enabled`          | Enabled catalogs group together.                                                                                         |
| `gather_types`     | The media type the catalog gathers: `music`, `video` or `podcast`.                                                       |
| `id`               | The catalog id, which is the order they were created in.                                                                 |
| `last_add`         | When something was last added to the catalog.                                                                            |
| `last_clean`       | When the catalog was last cleaned.                                                                                       |
| `last_update`      | When it was last updated.                                                                                                |
| `name`             | Catalog name.                                                                                                            |
| `rating`           | Your own rating, then when you set it. Catalogs you have not rated group together.                                       |
| `rename_pattern`   | The catalog's rename pattern.                                                                                            |
| `sort_pattern`     | The catalog's sort pattern.                                                                                              |
| `title`            | Alias of `name`.                                                                                                         |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
