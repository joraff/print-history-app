<?php



$lawStudents = file('C:\inetpub\print_history\scripts\G_Law_Student_Paw.txt');

$file = fopen('C:\inetpub\print_history\scripts\lawbals.html', 'w+');

fwrite($file, "<html><body>");

$balances = array();
$numover = 0;
$avg = 0;

if(count($lawStudents)) {

	$pathToAccountExe = "C:\Progra~1\Pcount~1\NT\ACCOUNT";
	
	foreach($lawStudents as $student) {
		if(strlen($student)) {
			$student = trim($student);
			$output = `$pathToAccountExe VIEWBAL $student`;
			$output = split("[\n ]", $output);
			$balances[$output[0]] = $output[5];
			if($output[5] < 0) $numover++;
			$avg += $output[5];
		}
	}
	
	$avg = $avg/count($balances);
	$time = 
	fwrite($file, "<p>Last updated: ".strftime("%Y-%m-%d %I:%M:%S %p")."</p>");
	fwrite($file, "<p>Average balance: $avg</p>");
	fwrite($file, "<p>Number in the negative: $numover</p>");

	fwrite($file, "<p>Balances:<br><br>");
	asort($balances);
	fwrite($file, "<table border=0>");
	foreach($balances as $n=>$b) fwrite($file, "<tr><td>$n</td><td>$b</td></tr>");
	fwrite($file, "</table></p>");
} else {
	fwrite($file, "no students in file");
}

fclose($file);
?>