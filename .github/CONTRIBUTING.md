# Contributing to ampache

Ampache is an open source project that loves to receive contributions from our community — you!
There are many ways to contribute, from writing tutorials or blog posts, improving the documentation,
submitting bug reports and feature requests or writing code which can be incorporated into Ampache itself.

Anyone can take part in our community and it there are no rules or requirements stopping you from joining.
Hopefully this document will help you make the jump!

Please read [Development section](https://github.com/ampache/ampache/wiki#development).

## Bug report

Anyone can take part in our community and it there are no rules or requirements stopping you from joining.
Hopefully this document will help you make the jump!

Be sure the bug is not already fixed in `develop` branch or already reported in current open issues.
Please add [some logs](https://github.com/ampache/ampache/wiki/Troubleshooting#enable-logging) with your new issue.

## Translations

The official way to send in translations is via [Transifex](https://www.transifex.com/ampache/ampache/dashboard/).

* The official source language of Ampache is US English.
* Strings should only be translated where they differ from the source language.
* If a translation is not available, Ampache will fall back to US English.

Ampache uses gettext to handle the translation between different languages.
If you are interested in translating Ampache into a new language or updating
an existing translation please join us on Transifex.

Benifits to using the Transifex platform include:

* Everything is managed in a central location.
* Translations are updated in a single commit without conflicts.
* The current translation state is available to the team to understand the status of each language.

If you have further questions about translations, please feel free to open an issue and ask for @Psy-Virus - The Translation Guy.

## Bug reports

If you think you have found a bug in Ampache, first make sure that you are testing against the latest [development](https://github.com/ampache/ampache/tree/develop) version.
Your issue may already have been fixed. If not, search our [issues list](https://github.com/ampache/ampache/issues) on GitHub in case a similar issue has already been opened.

A good tip when searching is to use in:title, in:body or in:comments when searching, especially for specific issues.

* ```warning in:title``` matches issues with "warning" in their title.
* ```error in:title,body``` matches issues with "error" in their title or body.
* ```shipit in:comments``` matches issues mentioning "shipit" in their comments.

Check out [docs.github.com](https://docs.github.com/en/github/searching-for-information-on-github/searching-issues-and-pull-requests) for more info about searching.

It is **very** helpful if you can prepare a reproduction of the bug. We have templates available which will help you when making your report.

* [Bug report](https://raw.githubusercontent.com/ampache/ampache/develop/.github/ISSUE_TEMPLATE/bug_report.md)
* [Security Policy](https://github.com/ampache/ampache/security/policy)

The easier it is for us to recreate your problem, the faster it is likely to be fixed.

## Feature requests

If you find yourself wishing for a feature that doesn't exist in Ampache, you are probably not alone.
While Ampache tries to cover as many people as possible there are always going to be features and wants that haven't made it yet.

If you can't find an existing issue, open a new one on the [issues list](https://github.com/ampache/ampache/issues) on GitHub.

Describes the feature you would like to see, why you need it, and how it should work making sure you follow our [Feature request](https://raw.githubusercontent.com/ampache/ampache/develop/.github/ISSUE_TEMPLATE/feature_request.md) template.

## Contributing code and documentation changes

If you would like to contribute a new feature or a bug fix to Ampache,
please discuss your idea first on the Github issue. If there is no Github issue
for your idea, please open one. It may be that somebody is already working on
it, or that there are particular complexities that you should know about before
starting the implementation. There are often a number of ways to fix a problem
and it is important to find the right approach before spending time on a PR
that cannot be merged.

The process for contributing to any of the [Ampache repositories](https://github.com/ampache/) is similar.
While they are similar, this document is specifically for contributing the the main Ampache repository.

### Fork and clone the repository

You will need to fork the main Ampache code or documentation repository and clone it to your local machine.
See the [github help page](https://help.github.com/articles/fork-a-repo) for help.

Further instructions for specific projects are given below.

### Tips for code changes

Following these tips prior to raising a pull request will speed up the review cycle.

* Make sure the code you add follows project coding standards and passes all tests before submitting
* Lines that are not part of your change should not be edited
  * e.g. don't format unchanged lines, don't reorder existing imports

* Add the appropriate [license headers](#license-headers) to any new files
* Make your own branch for your changes based on the develop branch. (e.g. my-patch-branch)

### Coding standards and principles

* We use PSR12 code style
* We follow the [`SOLID`](https://en.wikipedia.org/wiki/SOLID) principles

### Submitting your changes

Once your changes are ready to submit for review you need to:

#### Test your changes

Run the test scripts to make sure that nothing is broken.
Please consider adding unit-tests for you newly written code.

```bash
composer qa
```

#### Rebase your changes

Update your local repository with the most recent code from the Ampache repository using the latest develop branch.

#### Submit a pull request

Push your local changes to your forked copy of the repository and [submit a pull request](https://help.github.com/articles/using-pull-requests). In the pull request, choose a title which sums up the changes that you have made, and in the body provide more details about what your changes do. Also mention the number of the issue where discussion has taken place, eg "Closes #123".

Then sit back and wait. There will probably be discussion about the pull request and, if any changes are needed, we would love to work with you to get your pull request merged into Ampache.

The Ampache project love to recognize their contributors and will go to every effort to help make your pull requests meet the standards to merge.

### License Headers

Ampache requires license headers on all PHP files.
All contributed code should have the following
license header unless instructed otherwise:

```php
<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
```

### Project layout

This repository is split into many top level directories.

#### `bin`

Contains the server CLI applications

#### `config`

Where your Ampache config file resides (ampache.cfg.php)

#### `docs`

Documentation for the project.

#### `locale`

Translations are stored here.

#### `public`

This is the public web root for Amapche and where your webserver should point to.

#### `resources`

Fonts, scripts, templates and non-code resources that are required by Ampache.

#### `src`

Most of the logic resides within the Module folder. The model-files (in `Repository`) may also
contain application logic, this logic fragments will be migrated into their corresponding domains in `Module`

##### src->Application (deprecated)

Api-related code which didn't fit into existing domains within the Module folder yet.

##### src->Config

Application bootstrapping and config initialization related code.

##### src->Gui (deprecated)

Contains code related to the upcoming templating system. This namespace is deprecated, the code
will be merged into domains within the Module folder.

##### src->Module

Contains the complete business logic of Ampache, divided into separate domains.

##### src->Plugin

Ampache plugins are placed here.

##### src->Repository

Contains repository classes for database access as well as the ORM model classes.

#### `tests`

Tests for Ampache using phpunit. The folder structure mirrors the structures within `src`.

#### `vendor`

Third-Party composer requirements that are not maintained by Ampache.

## Reviewing and accepting your contribution

We review every contribution carefully to ensure that the change is of high
quality and fits well with the rest of the Ampache codebase. ourselves.

We really appreciate everyone who is interested in contributing to
Ampache and regret that we sometimes have to reject contributions even
when they might appear to make genuine improvements to the system.

Please discuss your change in a Github issue before spending much time on its
implementation. We sometimes have to reject contributions that duplicate other
efforts, take the wrong approach to solving a problem, or solve a problem which
does not need solving. An up-front discussion often saves a good deal of wasted
time in these cases.

We expect you to follow up on review comments somewhat promptly, but recognise
that everyone has many priorities for their time and may not be able to respond
for several days. We will understand if you find yourself without the time to
complete your contribution, but please let us know that you have stopped
working on it. We may close your PR if you do not respond for too long.

If your contribution is rejected we will close the pull request with a comment
explaining why. if you feel we have misunderstood your intended change
or otherwise think that we should reconsider then please continue the conversation
and we'll do our best to address any further points you raise.
