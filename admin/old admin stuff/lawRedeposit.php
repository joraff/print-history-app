<?php

$law_students = file("c:\inetpub\print_history\admin\G_Law_Student_Paw_Bal.txt");
$pathToAccountExe = "C:\Progra~1\Pcount~1\NT\ACCOUNT";

print_r($law_students);

foreach($law_students as $line) {
	if(strlen($line)) {
		list($student, $balance) = explode(" ", $line);
		$student = trim($student);
		$balance = trim($balance);
		echo "Re-setting balance for $student\n";
		//echo "$pathToAccountExe BALANCE $student $balance 'Balance adjustment on 1/9/09'\n";
		$output = `$pathToAccountExe BALANCE $student $balance 'Balance adjustment on 1/9/09'`;
		echo $output;
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

?>