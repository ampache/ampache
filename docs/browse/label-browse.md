# Label Browse

This page lists the filters and sorts the `label` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

## API methods using this browse

`labels`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value  | Description                                                                                                                          |
|-------------------|--------|--------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string | Alias of `like`.                                                                                                                     |
| `equal`           | string | The label name is exactly this value. Matching is case insensitive.                                                                  |
| `exact_match`     | string | Alias of `equal`.                                                                                                                    |
| `id`              | array  | Only these label ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string | The label name contains this value.                                                                                                  |
| `not_like`        | string | The label name does not contain this value.                                                                                          |
| `not_starts_with` | string | The label name does not start with this value.                                                                                       |
| `regex_match`     | regex  | The label name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                |
| `regex_not_match` | regex  | The label name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                             |
| `starts_with`     | string | The label name starts with this value.                                                                                               |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `active`           | Whether the label is still active.                                                                                       |
| `category`         | Episode category.                                                                                                        |
| `country`          | Label country.                                                                                                           |
| `creation_date`    | When it was created.                                                                                                     |
| `id`               | The label id, which is the order they were created in.                                                                   |
| `mbid`             | MusicBrainz id.                                                                                                          |
| `name`             | Label name.                                                                                                              |
| `rating`           | Your own rating, then when you set it. Labels you have not rated group together.                                         |
| `title`            | Alias of `name`.                                                                                                         |
| `user`             | The id of the user who added the label.                                                                                  |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
