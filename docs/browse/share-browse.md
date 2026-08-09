# Share Browse

This page lists the filters and sorts the `share` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** The share browse accepts no filters at all, so `cond` is ignored by the `shares` method. Users only ever see their own shares unless they are an admin.

## API methods using this browse

`shares`

## Available browse filters

This browse takes no filters, so a `cond` parameter sent with it is ignored.

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=allow_download,DESC`

| Sort             | Description                               |
|------------------|-------------------------------------------|
| `allow_download` | Whether the share allows downloads.       |
| `allow_stream`   | Whether the share allows streaming.       |
| `counter`        | How many times the share has been used.   |
| `creation_date`  | When it was created.                      |
| `expire`         | How many days the share lasts.            |
| `lastvisit_date` | When the share was last opened.           |
| `max_counter`    | The share's maximum allowed uses.         |
| `object`         | Shared object type, then object id.       |
| `object_type`    | The type of object the row points at.     |
| `user`           | The id of the user who created the share. |
