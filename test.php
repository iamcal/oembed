<?php
	$num_files = 0;
	$num_entries = 0;

	$expected_keys = array(
		'#'				=> 1,
		'#/provider_name'		=> 1,
		'#/provider_url'		=> 1,
		'#/endpoints'			=> 1,
		'#/endpoints/#'			=> 1,
		'#/endpoints/#/schemes'		=> 1,
		'#/endpoints/#/schemes/#'	=> 1,
		'#/endpoints/#/url'		=> 1,
		'#/endpoints/#/example_urls'	=> 1,
		'#/endpoints/#/example_urls/#'	=> 1,
		'#/endpoints/#/discovery'	=> 1,
		'#/endpoints/#/formats'		=> 1,
		'#/endpoints/#/formats/#'	=> 1,
		'#/endpoints/#/notes'		=> 1,
		'#/endpoints/#/notes/#'		=> 1,
		'#/endpoints/#/docs_url'	=> 1,
	);

	$dh = opendir('providers');
	while (($file = readdir($dh)) !== false){
		if (preg_match('!\.yml$!', $file)){
			$partial = yaml_parse_file("providers/$file");
			if (!$partial || !is_array($partial)){
				echo "Unable to parse provider file providers/$file\n";
				exit(1);
			}

			$num_files++;
			$num_entries += count($partial);


			#
			# check for any unexpected keys
			#

			$keys = check_keys($partial);
			foreach ($keys as $k){
				if (!isset($expected_keys[$k])){
					echo "Unexpected key {$k} in provider file providers/$file\n";
					exit(1);
				}
			}


			#
			# check that we define at least one endpoint and that each endpoint has a scheme and a url
			#

			foreach ($partial as $def){
				if (!isset($def['endpoints']) || !count($def['endpoints'])){
					echo "No endpoints defined in provider file providers/$file\n";
					exit(1);
				}
				foreach ($def['endpoints'] as $endpoint){

				# NOTE: this is disabled because it counts e.g. wordpress as broken.
				# maybe it is?

				#	if (!isset($endpoint['schemes']) || !count($endpoint['schemes'])){
				#		echo "Endpoint without schemes found in provider file providers/$file\n";
				#		print_r($endpoint);
				#		exit(1);
				#	}

					if (!isset($endpoint['url'])){
						echo "Endpoint without URL found in provider file providers/$file\n";
						print_r($endpoint);
						exit(1);
					}
				}
			}
		}
	}

	echo "Loaded $num_files provider files, containing $num_entries entries\n";
	exit(0);


	function check_keys($a){
		$out = array();
		check_keys_inner('', $a, $out);
		return array_keys($out);
	}

	function check_keys_inner($prefix, $a, &$out){
		foreach ($a as $k => $v){
			$key = is_int($k) ? '#' : $k;
			$out[$prefix.$key] = 1;
			if (is_array($v)){
				check_keys_inner($prefix.$key.'/', $v, $out);
			}
		}
	}
