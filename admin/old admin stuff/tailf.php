<?php

function tailme($file)
{
	
	$tail = popen('c:\unxutils\tail -f '.$file, 'r');
	while(!feof($tail))
	{
		$line = fgets($tail);
		echo $line;
		flush();
	}
	pclose($tail);
}

tailme('C:\PCOUNTER\DATA\PCOUNTER.LOG');

?>