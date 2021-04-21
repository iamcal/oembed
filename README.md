oEmbed Spec
===========

[![Build Status](https://travis-ci.org/iamcal/oembed.svg?branch=master)](https://travis-ci.org/iamcal/oembed)

This repo represents the current oEmbed spec as seen at 
<a href="http://oembed.com">http://oembed.com</a> and any drafts, in the `www` directory.

It also contains configuration information (the registry) for oEmbed providers, as YAML files in the `providers` directory.


## Consuming the provider registry

If you need to use the provider registry directly, you can install this package using NPM:

    npm install https://github.com/iamcal/oembed

That will install the providers file into `node_modules/oembed-providers/providers.json`, where you can ingest it directly.


## Maintainers: Publishing to NPM

* Update version in `package.json` to today's date
* `npm login` if you haven't already
* `npm publish`
* Check https://www.npmjs.com/package/oembed-providers

