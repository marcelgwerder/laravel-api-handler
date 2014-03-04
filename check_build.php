<?php

$laravelVersion = $argv[1];
$phpVersion = substr(phpversion(), 0, 3);

if($laravelVersion=='4.0.*' && ($phpVersion == '5.5' || $phpVersion == '5.4'))
{
	exit(0);
}
