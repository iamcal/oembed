FROM php:8.3-cli

# The site (www/index.php) parses provider YAML files via yaml_parse_file(),
# which requires the PECL yaml extension.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libyaml-dev \
    && pecl install yaml \
    && docker-php-ext-enable yaml \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Serve www/ as the docroot; index.php reads ../providers relative to it.
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/html/www"]
