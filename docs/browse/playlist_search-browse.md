# Playlist Search Browse

This page lists the filters and sorts the `playlist_search` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** This is the combined playlist and smartlist browse used by `playlists`, `index`, `list` and `stats`. Smartlist ids come back prefixed, so search `2256` is returned as `smart_2256`.

**NOTE** It reads a union of the `playlist` and `search` tables, so it only offers the columns both of them have.

## API methods using this browse

`index`, `list`, `playlist_folder_items`, `playlists`, `stats`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter                | Value   | Description                                                                                                                       |
|-----------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`         | string  | Alias of `like`.                                                                                                                  |
| `equal`               | string  | The list name is exactly this value. Matching is case insensitive.                                                                |
| `exact_match`         | string  | Alias of `equal`.                                                                                                                 |
| `hide_dupe_smartlist` | none    | Drop a smartlist when a playlist of the same name belongs to the same user. Needs no value.                                       |
| `id`                  | array   | Only these list ids, including `smart_` prefixed ones. It takes a list, which `cond` cannot send, so `cond=id,1` returns nothing. |
| `like`                | string  | The list name contains this value.                                                                                                |
| `not_like`            | string  | The list name does not contain this value.                                                                                        |
| `not_starts_with`     | string  | The list name does not start with this value.                                                                                     |
| `playlist_open`       | user id | Only lists this user can open: public ones, their own, ones they collaborate on and ones shared with them.                        |
| `playlist_type`       | 0 or 1  | Send `0` for lists owned by the current user, `1` for every list they can open. Sending it twice toggles it.                      |
| `playlist_user`       | user id | Only lists owned by this user.                                                                                                    |
| `regex_match`         | regex   | The list name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                              |
| `regex_not_match`     | regex   | The list name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                           |
| `smartlist`           | none    | Only smartlists, the rows with a `smart_` prefixed id. Needs no value.                                                            |
| `starts_with`         | string  | The list name starts with this value.                                                                                             |
| `user_flag`           | 0 or 1  | Send `1` for lists the current user has favourited, `0` for the ones they have not.                                               |
| `user_rating`         | 0 to 5  | Only lists the current user rated this value. Send `0` for lists they have not rated at all.                                      |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `date`             | When the list was created.                                                                                               |
| `id`               | The list id. Smartlist ids are prefixed with `smart_`, so they sort apart from playlists.                                |
| `last_count`       | How many items the list held when it was last counted.                                                                   |
| `last_update`      | When it was last updated.                                                                                                |
| `name`             | List name, then id, so lists with the same name keep a stable order.                                                     |
| `rand`             | Random order, applied per request. Paging through it repeats and skips lists, so ask for everything in one call.         |
| `rating`           | Your own rating, then when you set it. Lists you have not rated group together.                                          |
| `title`            | Alias of `name`.                                                                                                         |
| `type`             | Visibility: `public` or `private`.                                                                                       |
| `user`             | The id of the user who owns it.                                                                                          |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
| `username`         | The owner's username.                                                                                                    |
