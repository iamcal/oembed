#!/bin/bash

# neuron-base puts it in the wrong place, because the repo-name does not inclkude ".com"
mv /var/www/html/oembed /var/www/html/oembed.com

ln -s /var/www/html/oembed.com/site.conf /etc/apache2/sites-available/oembed.com.conf
a2ensite oembed.com

apt-get install -y libyaml-dev
pecl install yaml
echo "extension=yaml.so" >> /etc/php/7.0/apache2/php.ini
echo "extension=yaml.so" >> /etc/php/7.0/cli/php.ini

service apache2 reload
