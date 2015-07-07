<?php
	header('Content-type: text/plain; charset=utf-8');

	$data = yaml_parse_file('providers.yml');

	foreach ($data as &$provider){
		foreach ($provider['endpoints'] as &$endpoint){
			unset($endpoint['docs_url']);
			unset($endpoint['example_urls']);
			unset($endpoint['notes']);
		}
	}

	echo json_encode($data, JSON_PRETTY_PRINT);
