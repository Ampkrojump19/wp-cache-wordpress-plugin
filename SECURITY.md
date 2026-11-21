# Security policy

## Supported versions

The latest release receives fixes. Older tags are left as published.

## Reporting a vulnerability

Open a private security advisory through the repository's **Security** tab, or email
`the private advisory form in the Security tab`. Please do not open a public issue for anything exploitable.

Include the version, the PHP and WordPress version, and the shortest reproduction you
have. Expect a first reply within 72 hours.

## Scope

In scope: the plugin PHP, the admin screens, the REST endpoints under
`/wp-json/harriercache/v1/`, the WP-CLI commands, and anything that writes to disk.

Out of scope: vulnerabilities in WordPress core or other plugins, shared-hosting
misconfiguration, and issues that require an already-compromised administrator account.

## What the plugin does not do

It does not call `eval`, does not include files from user input, does not write outside
`wp-content/cache/harriercache` and `wp-content/uploads/harrier-backups`, and does not
modify `.htaccess` or `wp-config.php`.
