#!/usr/bin/php
<?php
	$options = getopt("hrdu:a:i:q:t:k:");

	$STATE_OK=0;
	$STATE_WARNING=1;
	$STATE_CRITICAL=2;
	$STATE_UNKNOWN=3;

	function usage()
	{
		echo "Usage: check_core_api.php <options>\n";
		echo "Check Core API\n";
		echo "Options:\n";
		echo "-u URL (required)\n";
		echo "-r reverse success check\n";
		echo "-a actions to check for existence (can be multiplied)\n";
		echo "-i checks item count (x:y:Y:X), where x - min val, below CRIT\n";
		echo "                                      y - min val, below WARN\n";
		echo "                                      Y - max val, above WARN\n";
		echo "                                      X - max val, above CRIT\n";
		echo "-q checks suggestions count (number or -, -number means CRIT when greater than number)\n";
		echo "-d checks existence of user_data\n";
		echo "-t checks data:x:task, x is task id in JSON reply (can be multiplied)\n";
		echo "-k checks keys in data:x:task, must be used with -t (can be multiplied)\n";
		echo "-h shows this help message\n";
	}

	if(in_array('h', array_keys($options)))
	{
		usage();
		return 0;
	}
	if(!in_array('u', array_keys($options)))
	{
		echo "No URL specified.\n";
		return 0;
	}

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $options['u']);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$content = curl_exec($curl);
	$HTTPCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);

	if($HTTPCode != 200)
	{
		echo "UNKNOWN: HTTP ".$HTTPCode."\n";
		return $STATE_UNKNOWN;
	}
	if($content === FALSE)
	{
		echo "UNKNOWN: Cannot get JSON\n";
		return $STATE_UNKNOWN;
	}
	$data = json_decode($content);
	if($data === NULL)
	{
		echo "CRITICAL: JSON corrupted\n";
		return $STATE_CRITICAL;
	}
	if((!$data->success && !in_array('r', array_keys($options))) || ($data->success && in_array('r', array_keys($options))))
	{
		echo "CRITICAL: Not succeed\n";
		return $STATE_CRITICAL;
	}

	if(in_array('i', array_keys($options)))
	{
		$cts = explode(':', $options['i']);
		$count = count($data->data->tasks->items);
		if(($count < $cts[0]) || ($count > $cts[3]))
		{
			echo "CRITICAL: Items count: $count\n";
			return $STATE_CRITICAL; 
		}
		if(($count < $cts[1]) || ($count > $cts[2]))
		{
			echo "WARNING: Items count: $count\n";
			return $STATE_WARNING;
		}	
	}
	if(in_array('a', array_keys($options)))
	{
		if(is_array($options['a']))
		{
			$actc = count($options['a']);
			while($actc > 0)
			{
				if(!isset($data->data->actions->$options['a'][$actc-1]))
				{
					echo "CRITICAL: Method ".$options['a'][$actc-1]." not exist\n";
					return $STATE_CRITICAL;
				}
				$actc--;
			}
		}
		else
		{
			if(!isset($data->data->actions->$options['a']))
			{
				echo "CRITICAL: Method ".$options['a']." not exist\n";
				return $STATE_CRITICAL;
			}
		}
	}
	if(in_array('q', array_keys($options)))
	{
		$count = count($data->data);
		if($options['q'] == '-')
		{
			if($count <= 0)
			{
				echo "CRITICAL: Suggestion count 0 or less\n";
				return $STATE_CRITICAL;
			}
		}
		if($options['q'] == '-0')
		{
			if($count > 0)
			{
				echo "CRITICAL: Suggestion count greater than 0\n";
				return $STATE_CRITICAL;
			}
		}
		else if($options['q'] < 0)
		{
			if($count > -$options['q'])
			{
				echo "CRITICAL: $count suggestions\n";
				return $STATE_CRITICAL;
			}
		}
		else
		{
			if($count < $options['q'])
			{
				echo "CRITICAL: $count suggestions\n";
				return $STATE_CRITICAL;
			}
		}
	}
	if(in_array('d', array_keys($options)))
	{
		if(!isset($data->users_data))
		{
			echo "CRITICAL: No users_data\n";
			return $STATE_CRITICAL;
		}
	}
	if(in_array('t', array_keys($options)))
	{
		$vars = $data->data;
		if(is_array($options['t']))
		{
			$tskc = count($options['t']);
			while($tskc > 0)
			{
				if(!isset($vars[$options['t'][$tskc-1]]->task))
				{
					echo "CRITICAL: No task ".$options['t'][$tskc-1]."\n";
					return $STATE_CRITICAL;
				}
				if(in_array('k', array_keys($options)))
				{
					if(is_array($options['k']))
					{
						$keyc = count($options['k']);
						while($keyc > 0)
						{
							if(!isset($vars[$options['t'][$tskc-1]]->task->$options['k'][$keyc-1]))
							{
								echo "CRITICAL: No key ".$options['k'][$keyc-1]." in task ".$options['t'][$tskc-1]."\n";
								return $STATE_CRITICAL;
							}
							$keyc--;
						}
					}
					else 
					{
						if(!isset($vars[$options['t'][$tskc-1]]->task->$options['k']))
						{
							echo "CRITICAL: No key ".$options['k'][$keyc-1]." in task ".$options['t'][$tskc-1]."\n";
							return $STATE_CRITICAL;
						}
					}
				}
				$tskc--;
			}
		}
		else
		{
			if(!isset($vars[$options['t']]->task))
			{
				echo "CRITICAL: No task ".$options['t']."\n";
				return $STATE_CRITICAL;
			}
			if(in_array('k', array_keys($options)))
			{
				if(is_array($options['k']))
				{
					$keyc = count($options['k']);
					while($keyc > 0)
					{
						if(!isset($vars[$options['t']]->task->$options['k'][$keyc-1]))
						{
							echo "CRITICAL: No key ".$options['k'][$keyc-1]." in task ".$options['t']."\n";
							return $STATE_CRITICAL;
						}
						$keyc--;
					}
				}
				else 
				{
					if(!isset($vars[$options['t']]->task->$options['k']))
					{
						echo "CRITICAL: No key ".$options['k']." in task ".$options['t']."\n";
						return $STATE_CRITICAL;
					}
				}
			}
		}
	}
	echo "OK: All Core API checks passed.\n";
	return $STATE_OK;