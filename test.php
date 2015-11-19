<?php

$data = yaml_parse_file('providers.yml');

if (!$data) {
    exit(1);
} else {
    exit(0);
}
