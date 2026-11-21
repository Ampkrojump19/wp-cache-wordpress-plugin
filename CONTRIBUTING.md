# Contributing

Thanks for looking. This is a small plugin and it is kept small on purpose.

## Before you start

- Open an issue describing the problem or the change.
- Keep pull requests focused: one behaviour per PR.
- Match the existing style: WordPress Coding Standards, tabs, Yoda conditions off,
  docblocks on public methods.

## Local setup

```bash
git clone https://github.com/Ampkrojump19/wp-cache-wordpress-plugin.git
cd harriercache
composer install   # optional, lint only
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

A local WordPress install with the plugin symlinked into `wp-content/plugins/harriercache`
is enough to test everything. `wp harriercache cache purge` is handy while iterating.

## Translation

The plugin ships with `languages/harriercache.pot`. Send `.po` files as a PR or add them
on translate.wordpress.org once the text domain is registered.

## Code of conduct

Be decent. Disagree with the code, not the person.
