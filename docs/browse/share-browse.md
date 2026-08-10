# Share Browse

This page lists the filters and sorts the `share` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** A share has no name of its own, so the text filters and the `name` sort reach through to the album, artist, playlist, podcast, episode, smartlist, song or video it points at.

**NOTE** Users only ever see their own shares unless they are an admin.

## API methods using this browse

`shares`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value   | Description                                                                                                                              |
|-------------------|---------|------------------------------------------------------------------------------------------------------------------------------------------|
| `alpha_match`     | string  | Alias of `like`.                                                                                                                         |
| `equal`           | string  | The title of the shared object is exactly this value. Matching is case insensitive.                                                      |
| `exact_match`     | string  | Alias of `equal`.                                                                                                                        |
| `like`            | string  | The title of the shared object contains this value.                                                                                      |
| `not_like`        | string  | The title of the shared object does not contain this value.                                                                              |
| `not_starts_with` | string  | The title of the shared object does not start with this value.                                                                           |
| `object_type`     | string  | Only shares of this object type: `album`, `album_disk`, `artist`, `playlist`, `podcast`, `podcast_episode`, `search`, `song` or `video`. |
| `regex_match`     | regex   | The title of the shared object matches this MySQL regular expression. Setting it clears any `regex_not_match` filter.                    |
| `regex_not_match` | regex   | The title of the shared object does not match this MySQL regular expression. Setting it clears any `regex_match` filter.                 |
| `starts_with`     | string  | The title of the shared object starts with this value.                                                                                   |
| `user`            | user id | Only shares created by this user.                                                                                                        |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort             | Description                                                                                                  |
|------------------|--------------------------------------------------------------------------------------------------------------|
| `allow_download` | Whether the share allows downloads.                                                                          |
| `allow_stream`   | Whether the share allows streaming.                                                                          |
| `counter`        | How many times the share has been used.                                                                      |
| `creation_date`  | When it was created.                                                                                         |
| `expire`         | How many days the share lasts.                                                                               |
| `lastvisit_date` | When the share was last opened.                                                                              |
| `max_counter`    | The share's maximum allowed uses.                                                                            |
| `name`           | The name or title of the shared object, then its type and id so shares of the same name keep a stable order. |
| `object`         | Shared object type, then object id.                                                                          |
| `object_type`    | The type of object the row points at.                                                                        |
| `title`          | Alias of `name`.                                                                                             |
| `user`           | The id of the user who created the share.                                                                    |
