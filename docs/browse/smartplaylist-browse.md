# Smartlist Browse

This page lists the filters and sorts the `smartplaylist` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** Smartlists are saved searches (the `search` table), so this browse can sort on the search's own `limit` and `random` settings.

**NOTE** A saved smartlist loaded by id is always a song search; the object type it was built for is not stored.

## API methods using this browse

`smartlists`, `user_smartlists`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value   | Description                                                                                                                              |
|-------------------|---------|------------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string  | Alias of `like`.                                                                                                                         |
| `equal`           | string  | The smartlist name is exactly this value. Matching is case insensitive.                                                                  |
| `exact_match`     | string  | Alias of `equal`.                                                                                                                        |
| `id`              | array   | Only these smartlist ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string  | The smartlist name contains this value.                                                                                                  |
| `not_like`        | string  | The smartlist name does not contain this value.                                                                                          |
| `not_starts_with` | string  | The smartlist name does not start with this value.                                                                                       |
| `playlist_open`   | user id | Only lists this user can open: public ones, their own, ones they collaborate on and ones shared with them.                               |
| `playlist_type`   | 0 or 1  | Send `0` for lists owned by the current user, `1` for every list they can open. Sending it twice toggles it.                             |
| `playlist_user`   | user id | Only lists owned by this user.                                                                                                           |
| `regex_match`     | regex   | The smartlist name matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                                |
| `regex_not_match` | regex   | The smartlist name does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                             |
| `starts_with`     | string  | The smartlist name starts with this value.                                                                                               |
| `user_flag`       | 0 or 1  | Send `1` for smartlists the current user has favourited, `0` for the ones they have not.                                                 |
| `user_rating`     | 0 to 5  | Only smartlists the current user rated this value. Send `0` for smartlists they have not rated at all.                                   |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `date`             | When the smartlist was created.                                                                                          |
| `id`               | The smartlist id, which is the order they were created in.                                                               |
| `last_count`       | How many items the smartlist returned when it was last counted.                                                          |
| `last_update`      | When it was last updated.                                                                                                |
| `limit`            | The row limit saved on the smartlist.                                                                                    |
| `name`             | Smartlist name, then id, so smartlists with the same name keep a stable order.                                           |
| `rand`             | Random order, applied per request. Paging through it repeats and skips smartlists, so ask for everything in one call.    |
| `random`           | Whether the smartlist returns its rows randomly.                                                                         |
| `rating`           | Your own rating, then when you set it. Smartlists you have not rated group together.                                     |
| `title`            | Alias of `name`.                                                                                                         |
| `type`             | Visibility: `public` or `private`.                                                                                       |
| `user`             | The id of the user who owns it.                                                                                          |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
| `username`         | The owner's username.                                                                                                    |
