# Follower Browse

This page lists the filters and sorts the `follower` browse accepts. Refer to the main [Api Browse methods](https://ampache.org/api/api-browse) page for how to send them.

**NOTE** A row is one follow: `user` is the account being followed and `follow_user` is the account following them.

**NOTE** The `followers` method sets `user` for you, so a `cond` sent with it narrows that list further rather than replacing it.

## API methods using this browse

`followers`

## Available browse filters

Send filters in the `cond` parameter as `filter,value` pairs, separated by `;`

e.g. `cond=user,1`

| Filter        | Value   | Description                                                |
|---------------|---------|------------------------------------------------------------|
| `follow_user` | user id | Only follows made by this user.                            |
| `user`        | user id | Only follows of this user, i.e. the people following them. |

## Available browse sorts

Send a single sort in the `sort` parameter as `name,order`, where order is `ASC` or `DESC`.

e.g. `sort=create_date,DESC`

| Sort          | Description                                         |
|---------------|-----------------------------------------------------|
| `create_date` | When the account doing the following was created.   |
| `follow_date` | When the follow was made.                           |
| `follow_user` | The id of the account doing the following.          |
| `last_seen`   | When the account doing the following was last seen. |
| `user`        | The id of the account being followed.               |
| `username`    | Username of the account doing the following.        |
