#!/usr/bin/php

<?php
	$autosuggester_urls[] = "http://es.suggest.z-dn.net/?q=a";
	$autosuggester_urls[] = "http://es.suggest.z-dn.net/?q=asdgadfhasdghashgsd";
	$autosuggester_urls[] = "http://es.suggest.z-dn.net/?q=";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $autosuggester_urls[0]);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$content[] = curl_exec($curl);
	curl_setopt($curl, CURLOPT_URL, $autosuggester_urls[1]);
	$content[] = curl_exec($curl);
	curl_setopt($curl, CURLOPT_URL, $autosuggester_urls[2]);
	$content[] = curl_exec($curl);
	curl_close($curl);
	
	if($content[0] && $content[1] && $content[2])
	{
		$data[] = json_decode($content[0]);
		$data[] = json_decode($content[1]);
		$data[] = json_decode($content[2]);
		if($data[0] && $data[1] && $data[2])
		{
			if($data[0]->success && $data[1]->success && !$data[2]->success)
			{
				if((count($data[0]->data) > 0) && (count($data[1]->data) == 0))
				{
					$output = "SUGGEST OK: All counts OK.\n";
				}
				else $output = "SUGGEST CRITICAL: Incorrect values.\n";
			}
			else $output = "SUGGEST CRITICAL: Returns success on failure.\n";
		}
		else $output = "SUGGEST UNKNOWN: Cannot parse JSON.\n";
	}
	else $output = "SUGGEST UNKNOWN: Cannot get JSON data.\n";
	echo $output;
?>