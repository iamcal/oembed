oEmbed Spec
===========

[![Build Status](https://github.com/iamcal/oembed/actions/workflows/build.yml/badge.svg)](https://github.com/iamcal/oembed/actions)
<span class="badge-npmversion"><a href="https://npmjs.org/package/oembed-providers" title="View this project on NPM"><img src="https://img.shields.io/npm/v/oembed-providers.svg" alt="NPM version" /></a></span>

This repo represents the current oEmbed spec as seen at 
<a href="http://oembed.com">http://oembed.com</a> and any drafts, in the `www` directory.

It also contains configuration information (the registry) for oEmbed providers, as YAML files in the `providers` directory.


## Consuming the provider registry

If you need to use the provider registry directly, you can install this package using NPM:

    npm install https://github.com/iamcal/oembed

That will install the providers file into `node_modules/oembed-providers/providers.json`, where you can ingest it directly.


## Development

The website (`www/index.php`) is rendered by PHP and uses the [`yaml`](https://www.php.net/manual/en/book.yaml.php) extension to count the providers in the registry. The included `Dockerfile` provides a PHP runtime with that extension already set up, so you don't need to install anything locally.

Build the image once:

    docker build -t oembed-site .

Then run it, mounting the repo so edits are picked up live:

    docker run --rm -p 8000:8000 -v "$PWD":/var/www/html oembed-site

Open <http://localhost:8000> to see the site. Editing `www/index.php` and refreshing the page is enough — no rebuild required.


## Maintainers: Publishing to NPM

* Update version in `package.json` to today's date
* `npm login` if you haven't already
* `npm publish`
* Check https://www.npmjs.com/package/oembed-providers

