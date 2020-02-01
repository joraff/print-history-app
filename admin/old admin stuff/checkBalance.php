<?php
$file = fopen("c:\inetpub\print_history\admin\jan_bal.txt", 'w+');

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
			$output = `$pathToAccountExe VIEWBAL $student`;
			echo $output."\n";
			fwrite($file, "$output\n");
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