<?php
error_reporting (E_ALL ^ E_NOTICE);

$duration = $_REQUEST['time'];

if ( $_SERVER['AUTH_USER'] ) {
	// Setup variables
	// Get authenticated username for queries
	$username = str_replace('BAYLOR\\','',$_SERVER['AUTH_USER']);
	//$username = 'Joseph_Rafferty';
	$logfile = 'C:\PCOUNTER\DATA\PCOUNTER.LOG';
	$jobsbydate = array();
	
	// Start date of query... varies, perhaps start of semester plus a week before? manually set for now
	switch($duration) {
		case "semester":
			if (time() >= strtotime("August 19, ".date('Y'))) {
				// Fall Semester
				$start_date = strtotime("August 19, ".date('Y'));
				$duration_str = "Fall ".date('Y');
			} else if (time() >= strtotime("May 19, ".date('Y'))) {
				// Summer Semester
				$start_date = strtotime("May 19, ".date('Y'));
				$duration_str = "Summer ".date('Y');
			} else {
				// Spring Semester
				$start_date = strtotime("January 1, ".date('Y'));
				$duration_str = "Spring ".date('Y');
			}
			break;
		case "month":
			$start_date = strtotime(date('F 1, Y'));
			$duration_str = date('F Y');
			break;
		case "all":
			$start_date = "";
			$duration_str = "Since Fall 2008";
			break;
		default:
			$start_date = strtotime(date('w')." days ago");
			$duration_str = "Week of ".date('M j, Y');
		
	}
	// End date... now
	$end_date = time();
	
	// Connect to DB. should be moved into a class eventually
	$db = mysql_connect('.', 'username', 'password');
	if (!$db) {
	    die('Could not connect: ' . mysql_error());
	}
	mysql_select_db('print_history');
	
	// Setup query string
	$sql = "SELECT `username`, `docname`, `printer`,UNIX_TIMESTAMP(`timestamp`) as timestamp, `pages`, `cost`, `optstring`, `balance` FROM history WHERE `username` = '$username' AND `timestamp` BETWEEN '".date('Y-m-d H:i:s',$start_date)."' AND '".date('Y-m-d H:i:s')."' ORDER BY `timestamp` DESC";

	// Perform query and stick the rows into the $jobsbydate array grouping by date
	$result = mysql_query($sql);
	if($result) {
		while($temp = mysql_fetch_array($result)) $jobsbydate[date('F d, Y', $temp['timestamp'])][] = $temp;
	}
	
	// Parse the PCOUNTER.LOG file for today's entries, not yet in the mysql DB
	
	if (file_exists($logfile)) {
		$contents = file_get_contents($logfile);
		$lines = explode("\n", trim($contents));
		$num = count($lines);
		foreach($lines as $line) {
			$raw = explode(',', $line);
			if(stripos($raw[0],$username)) {
				$elements = array($raw[0], addslashes($raw[1]), str_replace('\\\\GUS\\','',$raw[2]), $raw[3]." ".$raw[4], $raw[11], $raw[12], $raw[9], $raw[13]);
				if(count($elements)) array_unshift($jobs, $elements);
			}
		}
	}
	$numjobs = count($jobsbydate);


}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	<title>PawPrints Print History: Welcome</title>
	<link href="css/main2.css" media="screen" rel="stylesheet" type="text/css" />
	
	<script type="text/javascript">
		function toggle(id)
		{
			row=document.getElementById('row'+id);
			box=document.getElementById('box'+id);
			rowclassname=row.className;
			if (rowclassname.search(/highlight/)>0)
			{
				if (rowclassname.search(/blue/)>0)
					row.className='row blue';
				else
					row.className='row';
				box.checked='';    	
			}
			else
			{
				if (rowclassname.search(/blue/)>0)
					row.className='row blue highlight';
				else 
					row.className='row highlight';
				box.checked='true';
			}				
		}
	</script>
	
</head>

<body>
	<div id="header">
		<h3>PawPrints Print History: Joseph_Rafferty</h3>
		<div id="tab_container">
		    <ul id="tabs">
				<li><a href="?time=week" <?if ($duration == "week" || !$duration) {?> class="current" <? } ?>>This Week</a></li>
				<li><a href="?time=month" <?if ($duration == "month") {?> class="current" <? } ?>>This Month</a></li>
		  		<li><a href="?time=semester" <?if ($duration == "semester") {?> class="current" <? } ?>>This Semester</a></li>
		  		<li><a href="?time=all" <?if ($duration == "all") {?> class="current" <? } ?>>All</a></li>
			</ul>
		</div>
	</div>
	
	<div id="Wrapper">
	  <div class="fix_width">
		<div id="Container">
			<div id="Main">
				<div class="pheader">
					<ul>
						<li class="odd">
						<a>
							<div class="pcheck"><img src="checkmark.png" height="11px" style="padding-left: 4px" /></div>
							<div class="ptime">TIME</div>
							<div class="pdoc">DOCUMENT</div>
							<div class="pprinter">PRINTER</div>
							<div class="ppages">COPIES</div>
							<div class="ppages">PAGES</div>
							<div class="pbal">BALANCE</div>
						</a>
						</li>
						<br clear="all">
					</ul>
				</div>
				<? if ($numjobs) {
					foreach($jobsbydate as $date) {
						$datestr = strtoupper(date('l, j F', $date[0]['timestamp']));
						?>
						<div class="pdate_container">
							<span class="pdate"><? echo $datestr ?></span>
							<ul>
								<?
								$jobs = count($date);
								for ($x=0; $x<$jobs; $x++) {
									
									// Parse the options string and shorted the doc name, if longer than 50 chars
									
									$docname = (strlen($date[$x]['docname']) > 50) ? substr($date[$x]['docname'],0,25)."...".substr($date[$x]['docname'],-25,25) : $date[$x]['docname'];
									$options = "";									
									$color = (preg_match('/\/C\//',$date[$x]['optstring'])) ? true: false;
									$dup = (preg_match('/\/D\//',$date[$x]['optstring'])) ? true : false;
									$copies = preg_split('/\/Cp=/',$date[$x]['optstring']);
									$copies = substr($copies[1], 0, 1);
									if($color)
										if($dup) $options = "Color, 2-sided";
										else $options = "Color";
									else if($dup) $options = "2-sided";
									$docname .= (strlen($options))? " (".$options.")" : "";
									
									?>
									<li>
									<a href="#" id="row<?echo $x?>" class="row" onclick="toggle('<?echo $x?>')">
									<div class="pcheck"><input type="checkbox" /></div>
									<div class="ptime"><? date('h:i A', $date[$x]['timestamp']) ?></div>
									<div class="pdoc"><? echo $docname ?></div>
									<div class="pprinter"><? echo $date[$x]['printer'] ?></div>
									<div class="ppages"><? echo $copies ?></div>
									<div class="ppages"><? echo $date[$x]['pages'] ?></div>
									<div class="pbal"><? echo $date[$x]['balance'] ?></div>
									<br clear="all">
									</a>
									</li>
									<?
								}
								?>
								
							</ul>
						</div>
						<?
					}
				}
				?>
		</div>
	</div>
	</div>
</body>
