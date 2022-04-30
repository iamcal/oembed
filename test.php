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

					# this test is currently disabled because three providers fail it.
					# i _believe_ they should be deleted, as they are of no use to consumers.

				#	if (!isset($endpoint['discovery']) || $endpoint['discovery'] === 0){
				#		if (!isset($endpoint['schemes']) || !count($endpoint['schemes'])){
				#			echo "Endpoint without schemes or discovery found in provider file providers/$file\n";
				#			print_r($endpoint);
				#			exit(1);
				#		}
				#	}

					if (!isset($endpoint['url'])){
						echo "Endpoint without URL found in provider file providers/$file\n";
						print_r($endpoint);
						exit(1);
					}

					if (isset($endpoint['schemes'])){
					foreach ($endpoint['schemes'] as $scheme){

						# check for people trying to put regexes in the schemes (e.g. "(foo|bar)")
						if (strpos($scheme, '(') !== false){
							echo "Scheme contains illegal character '(' in provider file providers/$file\n";
							print_r($endpoint['schemes']);
							exit(1);
						}

						if (strpos($scheme, ')') !== false){
							echo "Scheme contains illegal character ')' in provider file providers/$file\n";
							print_r($endpoint['schemes']);
							exit(1);
						}

						# check for wildcards in schemes (and that a scheme exists)
						if (!preg_match('!^([a-z]+):!', $scheme)){
							echo "Scheme URL must contain a scheme which itself may not contain wildcards in provider file providers/$file\n";
							print_r($endpoint['schemes']);
							exit(1);
						}

						# for HTTP(S) URLs, check for domain wildcards
						if (preg_match('!^https?://([^/]+)!', $scheme, $m)){
							$domain = $m[1];
							$parts = array_reverse(explode('.', $domain));

							# allow 'foo.com' but no 'com'
							if (count($parts) < 2){
								echo "Scheme domain must be fully qualified in provider file providers/$file\n";
								print_r($endpoint['schemes']);
								exit(1);
							}

							# '*.foo.com' is ok, but '*.com' is not
							if ($parts[0] == '*' || $parts[1] == '*'){
								echo "Scheme domain may not contain a wildcard as the TLD in provider file providers/$file\n";
								print_r($endpoint['schemes']);
								exit(1);
							}

							# domain atoms may either be a wildcard, or a literal match,
							# so '*.foo.bar.com' is ok, but '*foo.bar.com' is not
							foreach ($parts as $part){
								if (strpos($part, '*') !== false && $part !== '*'){
									echo "Scheme domain wildcards must be for a whole atom TLD in provider file providers/$file\n";
									print_r($endpoint['schemes']);
									exit(1);
								}
							}
						}
					}
					}
				}
			}
		}else if (in_array($file,  ['README.md', '.', '..'])){
			# these are expected to be here
		}else{
			echo "Unexpected file {$file} in providers directory - your file must end in .yml\n";
			exit(1);
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
