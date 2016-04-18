<?php
	$num_files = 0;
	$num_entries = 0;

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
		}
	}

	echo "Loaded $num_files provider files, containing $num_entries entries\n";
	exit(0);
