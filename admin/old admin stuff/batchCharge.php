<?php

$filename = $argv[1];
if(file_exists($filename)) {
	$students = file($filename);
	$pathToAccountExe = "C:\Progra~1\Pcount~1\NT\ACCOUNT";
	
	print_r($students);
	
	foreach($students as $line) {
		if(strlen($line)) {
			list($student, $balance) = explode(" ", $line);
			$student = trim($student);
			$balance = trim($balance);
			echo "Re-setting balance for $student\n";
			//echo "$pathToAccountExe CHARGE $student $balance 'Pre-spring 2009 deposit adjustment on 1/9/09'\n";
			$output = `$pathToAccountExe CHARGE $student $balance 'Balance adjustment on 1/9/09'`;
			echo $output."\n";
		}
	}
	
	/*
	$file = fopen('c:\inetpub\print_history\admin\G_Law_Student_Paw_Bal.txt', 'w+');
	foreach($law_students as $student) {
		$student = trim($student);
		$temp=split("[\n ]",`$pathToAccountExe VIEWBAL $student`);
		$endBalance = $temp[5];
		echo "$student = $endBalance\n";
		fwrite($file, "$student $endBalance\n");
	}
	*/
} else {
	echo "File not found: $filename\n";
}
?>