# Folder Browse

This page lists the filters and sorts the `folder` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** **API8 only.** The rows come from `folder_map`, so a row is either a folder or a media file inside one and the `id` is a `type-id` string like `folder-12` or `song-2280`.

**NOTE** `int_id` is the plain numeric id of the object the row points at.

**NOTE** The `date`, `last_update`, `object_count`, `last_count`, `total_count` and `user` sorts read the folder's own columns, never the media inside it, so a media row has no value for them and every one of them keeps folders above files.

## API methods using this browse

`folders`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=starts_with,a`

| Filter            | Value   | Description                                                                                      |
|-------------------|---------|--------------------------------------------------------------------------------------------------|
| `alpha_match`     | string  | Alias of `like`.                                                                                 |
| `equal`           | string  | The folder name or its full path is exactly this value.                                          |
| `exact_match`     | string  | Alias of `equal`.                                                                                |
| `id`              | string  | Only the row with this `type-id` id, e.g. `folder-12`.                                           |
| `int_id`          | integer | Only the row for this numeric object id. Send `-1` to match nothing.                             |
| `like`            | string  | The folder name or its full path contains this value.                                            |
| `not_like`        | string  | Neither the folder name nor its full path contains this value.                                   |
| `not_starts_with` | string  | Neither the folder name nor its full path starts with this value.                                |
| `regex_match`     | regex   | The folder name or its full path matches this MySQL regular expression.                          |
| `regex_not_match` | regex   | Neither the folder name nor its full path matches this MySQL regular expression.                 |
| `starts_with`     | string  | The folder name or its full path starts with this value.                                         |
| `user_flag`       | 0 or 1  | Send `1` for folders the current user has favourited, `0` for the ones they have not.            |
| `user_rating`     | 0 to 5  | Only folders the current user rated this value. Send `0` for folders they have not rated at all. |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=name,DESC`

| Sort               | Description                                                                                                              |
|--------------------|--------------------------------------------------------------------------------------------------------------------------|
| `date`             | Folders first, then when the folder was added to the catalog.                                                            |
| `id`               | The `type-id` id string, which groups folders and files apart.                                                           |
| `int_id`           | The numeric id of the object the row points at.                                                                          |
| `last_count`       | Alias of `object_count`.                                                                                                 |
| `last_update`      | Folders first, then when the folder was last updated.                                                                    |
| `name`             | Folders first, then name.                                                                                                |
| `object_count`     | Folders first, then how many items the folder holds.                                                                     |
| `object_type`      | The type of object the row points at, so folders group apart from the files in them.                                     |
| `rand`             | Random order, applied per request. Paging through it repeats and skips folders, so ask for everything in one call.       |
| `rating`           | Your own rating, then when you set it. Folders you have not rated group together.                                        |
| `title`            | Alias of `name`.                                                                                                         |
| `total_count`      | Folders first, then how many times the folder has been played.                                                           |
| `type`             | Alias of `object_type`.                                                                                                  |
| `user`             | Folders first, then the id of the user who owns the folder.                                                              |
| `user_flag`        | When you added it to your favourites. This is a date, not a flag, so everything you have not favourited groups together. |
| `user_flag_rating` | When you added it to your favourites, then your rating.                                                                  |
| `userflag`         | Alias of `user_flag`.                                                                                                    |
