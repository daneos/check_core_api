#!/usr/bin/php
<?php

	$reverse = false;
	
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

	$options = getopt("hrdu:a:i:q:t:k:");
	foreach($options as $option=>$val)
	{
		switch($option)
		{
			case 'u':
				$url = $val;
				break;
			case 'r':
				$reverse = true;
				break;
			case 'a':
				$action = $val;
				break;
			case 'i':
				$cts = explode(':', $val);
				break;
			case 'q':
				$qc = $val;
				break;
			case 'd':
				$usersdata = true;
				break;
			case 't':
				$task = $val;
				break;
			case 'k':
				$key = $val;
				break;
			case 'h':
				usage();
				return 0;
				break;
			default:
				echo "Unknown option: -$option\n";
				return 1;
				break;
		}
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
	if((!$data->success && !$reverse) || ($data->success && $reverse))
	{
		echo "CRITICAL: Not succeed\n";
		return $STATE_CRITICAL;
	}

	if(isset($cts))
	{
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
	if(isset($action))
	{
		if(is_array($action))
		{
			$actc = count($action);
			while($actc > 0)
			{
				if(!isset($data->data->actions->$action[$actc-1]))
				{
					echo "CRITICAL: Method ".$action[$actc-1]." not exist\n";
					return $STATE_CRITICAL;
				}
				$actc--;
			}
		}
		else
		{
			if(!isset($data->data->actions->$action))
			{
				echo "CRITICAL: Method ".$action." not exist\n";
				return $STATE_CRITICAL;
			}
		}
	}
	if(isset($qc))
	{
		$count = count($data->data);
		if($qc == '-')
		{
			if($count <= 0)
			{
				echo "CRITICAL: Suggestion count 0 or less\n";
				return $STATE_CRITICAL;
			}
		}
		else if($qc < 0)
		{
			if($count > -$qc)
			{
				echo "CRITICAL: $count suggestions\n";
				return $STATE_CRITICAL;
			}
		}
		else
		{
			if($count < $qc)
			{
				echo "CRITICAL: $count suggestions\n";
				return $STATE_CRITICAL;
			}
		}
	}
	if(isset($usersdata))
	{
		if(!isset($data->users_data))
		{
			echo "CRITICAL: No users_data\n";
			return $STATE_CRITICAL;
		}
	}
	if(isset($task))
	{
		$vars = get_object_vars($data->data);
		if(is_array($task))
		{
			$tskc = count($task);
			while($tskc > 0)
			{
				if(!isset($vars[$task[$tskc-1]]->task))
				{
					echo "CRITICAL: No task ".$task[$tskc-1]."\n";
					return $STATE_CRITICAL;
				}
				if(isset($key))
				{
					if(is_array($key))
					{
						$keyc = count($key);
						while($keyc > 0)
						{
							if(!isset($vars[$task[$tskc-1]]->task->$key[$keyc-1]))
							{
								echo "CRITICAL: No key ".$key[$keyc-1]." in task ".$task[$tskc-1]."\n";
								return $STATE_CRITICAL;
							}
							$keyc--;
						}
					}
					else 
					{
						if(!isset($vars[$task[$tskc-1]]->task->$key))
						{
							echo "CRITICAL: No key ".$key[$keyc-1]." in task ".$task[$tskc-1]."\n";
							return $STATE_CRITICAL;
						}
					}
				}
				$tskc--;
			}
		}
		else
		{
			if(!isset($vars[$task]->task))
			{
				echo "CRITICAL: No task ".$task."\n";
				return $STATE_CRITICAL;
			}
			if(isset($key))
			{
				if(is_array($key))
				{
					$keyc = count($key);
					while($keyc > 0)
					{
						if(!isset($vars[$task]->task->$key[$keyc-1]))
						{
							echo "CRITICAL: No key ".$key[$keyc-1]." in task ".$task."\n";
							return $STATE_CRITICAL;
						}
						$keyc--;
					}
				}
				else 
				{
					if(!isset($vars[$task]->task->$key))
					{
						echo "CRITICAL: No key ".$key." in task ".$task."\n";
						return $STATE_CRITICAL;
					}
				}
			}
		}
	}

	echo "OK: All Core API checks passed.\n";
	return $STATE_OK;