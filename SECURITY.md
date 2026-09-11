# Security Policy

## Supported Versions

Version 7 is the only version undergoing new development

| Version | Supported          |
|---------|--------------------|
| 7.x.x   | :white_check_mark: |
| 6.x.x   | :x:                |
| 5.x.x   | :x:                |
| <= 4.0  | :x:                |

## Scope

A report is out of scope if reproducing it requires an Administrator (access level 100) account —
whether as the attacker or as the account being targeted — or requires prior access to the
server/host itself (shell access, direct database access, etc). An already-admin account performing
an admin-only action, or an attack that only works once you already control the server, is not a
vulnerability.

## Reporting a Vulnerability

Report all security issues through GitHub's private vulnerability reporting:
https://github.com/ampache/ampache/security/advisories/new

Include a description, execution steps to reproduce, and the affected version or git branch.

All reported issues will be examined and an issue will be created to track against.
