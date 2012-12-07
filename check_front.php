#!/usr/bin/php

<?php
	$front_urls[] = "http://api.z-dn.net/api/1/endpoints/es";
	$front_urls[] = "http://api.z-dn.net/api/1/endpoints/esdupa";
	$front_urls[] = "http://api.z-dn.net/api/1.2.2/endpoints/es";

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $front_urls[0]);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$content[] = curl_exec($curl);
	curl_setopt($curl, CURLOPT_URL, $front_urls[1]);
	$content[] = curl_exec($curl);
	curl_setopt($curl, CURLOPT_URL, $front_urls[2]);
	$content[] = curl_exec($curl);
	if($content[0] && $content[1] && $content[2])
	{
		$data[] = json_decode($content[0]);
		$data[] = json_decode($content[1]);
		$data[] = json_decode($content[2]);
		if($data[0] && $data[1] && $data[2])
		{
			if($data[0]->success && !$data[1]->success && !$data[2]->success)
			{
				if(!strcmp($data[1]->message, "Unsupported language") && !strcmp($data[2]->message, "Unsupported version"))
				{
					$output = "FRONT OK: ".count(get_object_vars($data[0]->data->actions))." actions.\n";
				}
				else $output = "FRONT CRITICAL: Incorrect messages.\n";
			}
			else $output = "FRONT CRITICAL: Returns success on failure.\n";
		}
		else $output = "FRONT UNKNOWN: Cannot parse JSON.\n";
	}
	else $output = "FRONT UNKNOWN: Cannot get JSON data.\n";
	curl_close($curl);
	echo $output;
?>