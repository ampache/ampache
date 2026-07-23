---
name: database-migrations
description: How to write and change Ampache database migrations under src/Module/System/Update/Migration safely. A failing migration is a showstopper that blocks every user on that version, so this covers the failure model, the edge cases that have actually broken migrations before (unique keys, NULL columns, case-insensitive collation), the Ampache7 downgrade obligation, and how to prove a migration against real data shapes. Use whenever adding, editing or reviewing a migration or the Versions list.
---

# Writing Ampache database migrations

## A failing migration is a showstopper

This is the rule everything else follows from. Verify it before arguing with it:

- `DbaDatabaseConnection::query()` **throws** `QueryFailedException` when a statement fails. Its
  third argument is `$silent`, which only controls whether the error is echoed — **it does not
  suppress the throw**. `AbstractMigration::updateDatabase()` passes `$silent = false`.
- `UpdateRunner::runUpdates()` wraps `$migration->migrate()` in `try { … } catch (Throwable) { throw
  new UpdateFailedException(); }`, and that is **before** `setValue(DB_VERSION, …)`.

Three consequences:

1. **The version is not recorded.** The failed migration will run again on the next attempt.
2. **The whole update aborts.** Every later migration in the loop is skipped, so the install is
   stuck at that version until the migration itself is fixed.
3. **There is no transaction.** Nothing wraps the loop or an individual migration, and MySQL commits
   DDL implicitly anyway. A migration that fails on its third statement has already committed the
   first two.

### So: fix the migration in place. Never chase it with another one.

When a migration is reported broken, edit that migration. Do **not** add a follow-up migration to
repair the damage — the broken one never recorded its version, so every affected install will re-run
the fixed version and come out right. A follow-up migration is dead weight that also has to be
maintained and downgraded.

The one thing to check before editing in place: has the version ever been recorded successfully
anywhere? If a migration succeeds for some databases and fails for others, editing it in place only
helps the ones that failed. The successful ones keep the old behaviour, and *then* a new migration is
the correct remedy. Decide that from the failure model above, not from a guess.

### Consequence 3 means every statement must be idempotent

Because a partly-applied migration re-runs from the top, each statement has to be safe to execute
twice. Idioms already used in this tree:

- `INSERT IGNORE INTO …`
- `DROP TABLE IF EXISTS …` / `CREATE TABLE IF NOT EXISTS …`
- `Dba::write($sql, [], true)` — the trailing `true` is `$silent`, for statements expected to fail
  the second time (e.g. `ALTER TABLE … ADD KEY` when the key is already there)
- `UPDATE … WHERE <the old value>` so the second run matches nothing

## Edge cases that have actually broken migrations here

### Unique keys turn a normalising UPDATE into a duplicate-key error

`image` has `UNIQUE KEY unique_image (width, height, mime, size, object_type, object_id, kind)` —
note it includes `mime`. A migration that rewrote `image/jpg` to `image/jpeg` failed with
`SQLSTATE[23000] … 1062 Duplicate entry` on any library where the same artwork existed twice, once
per spelling (the upload path typed it from the filename, CLI art gathering typed it correctly).

Before writing an UPDATE that normalises a column, check `SHOW CREATE TABLE` for a unique key
containing that column. If there is one, left-join the table to itself and skip the rows that would
collide:

```sql
UPDATE `image` AS `fix`
LEFT JOIN `image` AS `clash`
    ON `clash`.`object_type` = `fix`.`object_type`
   AND `clash`.`object_id`   = `fix`.`object_id`
   AND `clash`.`width`  <=> `fix`.`width`
   AND `clash`.`height` <=> `fix`.`height`
   AND `clash`.`size`   <=> `fix`.`size`
   AND `clash`.`kind`   <=> `fix`.`kind`
   AND `clash`.`mime` = 'image/jpeg'
   AND `clash`.`id` <> `fix`.`id`
SET `fix`.`mime` = 'image/jpeg'
WHERE `fix`.`mime` = 'image/jpg' AND `clash`.`id` IS NULL;
```

### Use `<=>` on nullable columns

In `image`, `width`, `height`, `size`, `kind` and `mime` are all nullable. Joining them with `=`
silently drops every row where the value is NULL, so the migration quietly does nothing for those and
you won't notice in testing unless your fixture has NULLs. `<=>` is the NULL-safe equality operator.

### The default collation is case insensitive

`utf8mb4_unicode_ci` means `'image/JPG' = 'image/jpg'` is **true**, and `LIKE` matches the same way.
So a `WHERE mime = 'image/jpg'` already catches the upper case variant — and a separate
case-normalising clause is redundant. It also means two rows differing only in case cannot both exist
under a unique key, so they are never the source of a collision.

### Never delete user data to resolve a conflict

When rows collide, leave them rather than deleting one. Artwork, playlists and preferences are the
user's; an upgrade is not the moment to throw one away because two rows disagree. Skip the row,
document the choice in the migration docblock and in the changelog entry, and let the user decide.

### Removing a preference

Delete the `user_preference` rows first, then the `preference` row, matching `Migration800020`:

```php
$this->updateDatabase("DELETE FROM `user_preference` WHERE `preference` IN (SELECT `id` FROM `preference` WHERE `name` = 'x');");
$this->updateDatabase("DELETE FROM `preference` WHERE `name` = 'x';");
```

Then strip every reference to it: `Preference::SYSTEM_LIST`, `is_boolean()`, `set_defaults()`,
`translate_db()`, the `set_level()` name lists, `Ui::createPreferenceInput()`'s boolean `case` list,
and any `AmpConfig::get()` call sites. Grep for the name and expect to find it in more places than
you assumed.

## Every migration owes Ampache7 a downgrade decision

`ampache-develop` (Ampache7) caps at `Versions::MAXIMUM_UPDATABLE_VERSION = 794004` and rolls an
Ampache8 database back through explicit blocks in `UpdateRunner::runRollback()`, in **descending**
version order:

```php
if ($currentVersion >= 800022) {
    // Migration\V8\Migration800022 (restore the preference deleted by the migration)
    if (!Preference::insert('ajax_load', 'Ajax page load', '1', AccessLevelEnum::USER->value, 'boolean', 'interface')) {
        throw new UpdateFailedException();
    }
}
```

For each new migration, decide one of:

- **Undo it** — add a block. Removing a preference Ampache7 still reads means restoring it, or the
  downgraded install loses whatever it gated.
- **Nothing to undo** — say so in a comment where the block would go, so nobody later "fills the
  gap" and reintroduces a bug. A data correction Ampache7 also benefits from (a mime type it would
  serve correctly) needs no undo.

Ampache7 is a separate line, not downstream of develop8: its changes are made directly in that tree
and logged in its own changelog.

## Checklist

1. New file `src/Module/System/Update/Migration/V8/Migration<N>.php`, one-line `$changelog`, a
   docblock saying *why*.
2. Register in `Versions::$versions` **and** bump `Versions::MAXIMUM_UPDATABLE_VERSION`.
3. Every statement idempotent (the migration may re-run after a partial failure).
4. `SHOW CREATE TABLE` for any table you UPDATE — check unique keys and column nullability.
5. Add the Ampache7 rollback block, or the comment explaining why none is needed.
6. `docs/CHANGELOG.md` entry under the matching section, headed `Database <version>`.
7. Do **not** touch `resources/sql/ampache.sql` — migrations are the source of truth until release,
   the seed is regenerated then.

## Prove it against the failure, not just the happy path

A migration that works on your dev database proves very little — dev databases are small and clean.
Construct the edge case and watch the old statement fail before you accept that the new one passes:

```bash
# reproduce: make the collision the bug report describes
docker exec ampache-db mariadb -uampache -pampache ampache -e "INSERT INTO image (…) SELECT … 'image/jpg' …;"

# the old statement must fail here, with the reported error
# the new statement must succeed and leave the collided row alone

# then run it the way a user will, from the version they are actually on
docker exec ampache-db mariadb -uampache -pampache ampache -e "UPDATE update_info SET value='<previous>' WHERE \`key\`='db_version';"
MSYS_NO_PATHCONV=1 docker exec ampache php /var/www/html/bin/cli admin:updateDatabase -e

# clean up the synthetic rows afterwards
```

Check the recorded version afterwards (`SELECT value FROM update_info WHERE \`key\`='db_version'`) —
that is what proves the migration was accepted rather than swallowed.
