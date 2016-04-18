<?php
	header('Content-type: text/plain; charset=utf-8');

	$data = yaml_parse_file('providers.yml');

	foreach ($data as &$provider){

		$name = str_replace(' ', '_', StrToLower($provider['provider_name']));

		if (!$name){
			echo "CAN'T BUILD NAME:\n";
			print_r($provider);
		}


		echo "$name.yml\n";
		yaml_emit_file("providers/$name.yml", array($provider));

	}
