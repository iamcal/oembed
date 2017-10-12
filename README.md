oEmbed Spec
===========

[![Build Status](https://travis-ci.org/iamcal/oembed.svg?branch=master)](https://travis-ci.org/iamcal/oembed)

These files represent the current oEmbed spec as seen at 
<a href="http://oembed.com">http://oembed.com</a> and any drafts.


## Installation

    cd /var/www/html
    git clone git@github.com:iamcal/oembed.git oembed.com
    ln -s /var/www/html/oembed.com/site.conf /etc/apache2/sites-available/oembed.com.conf
    a2ensite oembed.com
    
    apt-get install -y php-pear php5-dev libyaml-dev
    pecl install yaml
    echo "extension=yaml.so" >> /etc/php5/apache2/php.ini
    
    service apache2 reload


## Publishing to NPM

* Update version in `package.json` to today's date
* `npm login` if you haven't already
* `npm publish`
* Check https://www.npmjs.com/package/oembed-providers

