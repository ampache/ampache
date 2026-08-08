# XSS probe

Seeds hostile strings into a dev database, crawls the UI as a logged-in admin, and reports anything
echoed back unescaped.

```sh
python3 resources/scripts/xss-probe/xss_probe.py --base-url http://localhost:8084
```

Exit status is 0 when nothing was found and 1 when something was, so it can gate a branch.

## Why this exists alongside the unit test

`tests/Gui/View/TemplateEscapingTest.php` covers the other half. It walks the php tokens of every
`.phtml` and proves each value a template echoes went through `e()` or `raw()`, resolving the view class
by reflection so a getter returning `int` is not flagged and a getter returning `string` is.

What it cannot do is judge whether a *legitimate* `raw()` is safe, because that depends on the thing
producing the html. Every finding this probe has turned up was of that shape:

- `ShoutRenderer` interpolated the shout body straight into its markup, and the shoutbox templates
  correctly called `raw()` on the result.
- `Art::display()` put the object title into `title=` and `alt=` raw, for all forty-odd call sites.
- `Tag::get_display()` escaped the `title` attribute of a genre link but not its text.
- `Ajax::text()` inserts its text argument raw, so the rightbar leaked the playlist and collection names.
- `header.inc.php` printed the logged-in user's `fullname` raw, on every page in the application.

None of those are visible from a template. Run both.

## How it decides

The payload is `<img src=x onerror=AMPXSSPROBE>"'</title>`, with `<b AMPXSSPROBE>` used where the column
is too narrow. The escaped form contains the marker too, so only the *literal* payload counts as a
finding — that is what keeps a page full of correctly-escaped copies from reporting.

Findings print with the surrounding markup, which is normally enough to name the producer.

## Adding coverage

`TARGETS` is the list of user-settable text columns to poison and `PAGES` the list of urls to crawl.
Both are plain lists at the top of the script. A column the schema does not have is skipped rather than
failing, so the same list works across versions.

Anything reachable only by POST is out of scope.

## Safety

It writes to the database and restores from an in-memory copy taken at the start, so **point it at a
throwaway instance**: an interrupted run leaves the payloads in place. Values round-trip as hex, so a
description holding a tab or a newline survives.
