# User Browse

This page lists the filters and sorts the `user` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** Users have no rating or favourite of their own, so this browse has no `user_flag` or `user_rating` filters.

## API methods using this browse

`users`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value   | Description                                                                                                                         |
|-------------------|---------|-------------------------------------------------------------------------------------------------------------------------------------|
| `access`          | integer | Only users with this access level: `5` guest, `25` user, `50` content manager, `75` catalog manager, `100` admin.                   |
| `alpha_match`     | string  | Alias of `like`.                                                                                                                    |
| `disabled`        | 0 or 1  | Send `1` for disabled accounts, `0` for active ones.                                                                                |
| `equal`           | string  | The full name, username or email is exactly this value.                                                                             |
| `exact_match`     | string  | Alias of `equal`.                                                                                                                   |
| `id`              | array   | Only these user ids. It takes a list, which `cond` cannot send, so `cond=id,1` builds an empty list and the browse returns nothing. |
| `like`            | string  | The full name, username or email contains this value.                                                                               |
| `not_like`        | string  | The full name, username or email does not contain this value.                                                                       |
| `not_starts_with` | string  | None of the full name, username or email starts with this value.                                                                    |
| `regex_match`     | regex   | The full name, username or email matches this MySQL regular expression.                                                             |
| `regex_not_match` | regex   | None of the full name, username or email matches this MySQL regular expression.                                                     |
| `starts_with`     | string  | The full name, username or email starts with this value.                                                                            |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=access,DESC`

| Sort              | Description                                                                                                      |
|-------------------|------------------------------------------------------------------------------------------------------------------|
| `access`          | Access level, lowest first.                                                                                      |
| `city`            | City on the user's profile.                                                                                      |
| `create_date`     | When the account was created.                                                                                    |
| `disabled`        | Disabled accounts group together.                                                                                |
| `email`           | Email address.                                                                                                   |
| `fullname`        | The user's full name.                                                                                            |
| `fullname_public` | Whether the user shows their full name publicly.                                                                 |
| `id`              | The user id, which is the order the accounts were created in.                                                    |
| `last_seen`       | When the user was last seen.                                                                                     |
| `rand`            | Random order, applied per request. Paging through it repeats and skips users, so ask for everything in one call. |
| `state`           | Processing state of the episode.                                                                                 |
| `username`        | The owner's username.                                                                                            |
| `website`         | Website address.                                                                                                 |
