<?php
	header('Content-type: text/plain; charset=utf-8');


	#
	# gather providers from the various files
	#

	$data = array();

	$dh = opendir(__DIR__.'/../providers');
	while (($file = readdir($dh)) !== false){
		if (preg_match('!\.yml$!', $file)){
			$partial = yaml_parse_file(__DIR__."/../providers/$file");
			foreach ($partial as $row) $data[] = $row;
		}
	}


	#
	# scrub some fields
	#

	foreach ($data as &$provider){
		foreach ($provider['endpoints'] as &$endpoint){
			unset($endpoint['docs_url']);
			unset($endpoint['example_urls']);
			unset($endpoint['notes']);
		}
	}


	#
	# sort and output
	#

	usort($data, 'local_sort');

	function local_sort($a, $b){
		return strcasecmp($a['provider_name'], $b['provider_name']);
	}

	echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
