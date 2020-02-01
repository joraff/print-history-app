<?

echo "This script is disabled";

/*
ini_set('memory_limit', '256M');
set_time_limit(300);

$db = mysql_connect('.', 'username', 'password');
if (!$db) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db('print_history');

for($d=14;$d<=24;$d++) {
	if (file_exists("c:\PCOUNTER\DATA\PCOUNTER_2008_10".sprintf('%02s',$d).".LOG")) {
		echo "c:\PCOUNTER\DATA\PCOUNTER_2008_10".sprintf('%02s',$d).".LOG exists... parsing<br>";
		$contents = file_get_contents("c:\PCOUNTER\DATA\PCOUNTER_2008_10".sprintf('%02s',$d).".LOG");
		$lines = explode("\n", trim($contents));
		$num = count($lines);
		$jobs = array();
		foreach($lines as $line) {
			$elements = explode(',', $line);
			$elements[1] = addslashes($elements[1]);
			if(count($elements)) $jobs[] = $elements;
		}
		
		$sql = "INSERT INTO `history` (	`username`,
										`docname`,
										`printer`,
										`timestamp`,
										`client`,
										`subcode`,
										`clientcode`,
										`papersize`,
										`optstring`,
										`size`,
										`pages`,
										`cost`,
										`balance`) VALUES ";
		$numjobs = count($jobs);
		for($x=0; $x<$numjobs; $x++) {
			$job = $jobs[$x];
			$sql .= "(	'".str_replace('BAYLOR\\','',$job[0])."',
						'".$job[1]."',
						'".str_replace('\\\\GUS\\','',$job[2])."',
						'".date('Y-m-d h:i:s', mktime(substr($job[4],0,2),substr($job[4],3,2),0,substr($job[3],0,2),substr($job[3],3,2),substr($job[3],6,4)))."',
						'".substr($job[5],2)."',
						'".$job[6]."',
						'".$job[7]."',
						'".$job[8]."',
						'".$job[9]."',
						'".$job[10]."',
						'".$job[11]."',
						'".$job[12]."',
						'".trim($job[13])."')";
			$sql .= ($x==($numjobs-1)) ? "" : ", ";
		}

		mysql_query($sql);
		echo mysql_error();
		
	} else {
	echo "c:\PCOUNTER\DATA\PCOUNTER_2008_10".sprintf('%02s',$d).".LOG not found!<br>";
	}
}
*/
?>