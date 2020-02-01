<?php

// Since we're handling passwords, we want to require HTTPS

if($_SERVER['HTTPS'] == 'off') {
	Header( "HTTP/1.1 301 Moved Permanently" ); 
	Header( "Location: https://gus.baylor.edu/print_history/" );
}

//echo "Print History is currently disabled, pending some changes. Thanks for coming though!";
//exit;

//echo "<pre align=left>";
//print_r($_REQUEST);
//echo "</pre>";

error_reporting (0);

$duration = $_REQUEST['time'];
$action = $_REQUEST['action'];

if ( $_SERVER['AUTH_USER'] ) {
	// Setup variables
	// Get authenticated username for queries
	$username = str_ireplace('BAYLOR\\','',$_SERVER['AUTH_USER']);
	$username = preg_replace('/@.*/','',$username); // For when users login with their email address, strip @ and after
	$username = substr($username, 0, 20);
	
	// Connect to DB. should be moved into a class eventually
	$db = mysql_connect('mysql-server', 'username', 'password');
	if (!$db) {
	    die('Could not connect: ' . mysql_error());
	}
	mysql_select_db('print_history');
	
	/*********************
	***     REQUESTS   ***
	*********************/
	
	// If there was a request, let's take care of inserting that before loading everything else on the page
	if($action=='request') {
		$submittedJobsArray = array();
		$pages = 0;
		foreach($_REQUEST['jobarray'] as $job) {
			$job = unserialize($job);
			$submittedJobsArray[] = $job;
			$pages += $job['jobinfo']['cost'];
		}
		
		//Perform Validations
		$proceed = true;
		
		if(!count($submittedJobsArray)) {
			$msg['msg'] = 'You didn\'t check any jobs to request!';
			$msg['status'] = 'error';
			$proceed = false;
		}
		if(!strlen($_REQUEST['reason'])) {
			$msg['msg'] = 'A description of your PawPrints problem is required when requesting a refund.';
			$msg['status'] = 'error';
			$proceed = false;
		}
		
		if($proceed) {
		// Both requirements check out.. proceed
		// Double check to make sure that we got the pages
			if($pages) {
					
				$reason = mysql_real_escape_string($_REQUEST['reason']);
				// Now contruct the request insert & get the ID of that request
				$sql = "INSERT INTO requests (`username`, `reason`, `pages_requested`) VALUES ('$username', '$reason', '$pages')";
				$result = mysql_query($sql);
				if($result){
					$request_id = mysql_insert_id($db);
					
					// Since the request insert was good, contruct the job hash inserts
					// hash format is an MD5 digest of username, docname, printer, optstring, and balance all together in that order without a delimiter.
					//  Optstring has a hex timestamp, and together with balance should give a unique identifier.
					//  I know it isn't 100% guaranteed to be unique, but it should do just fine
					

					$SQL = "INSERT INTO requested_jobs (`request_id`, `hash`, `timestamp`, `docname`, `printer`, `pages`, `cost`, `balance`) VALUES ";
					foreach($submittedJobsArray as $job) {
						$SQL .= "(
								'$request_id', 
								'" . $job['jobhash'] . "',
								FROM_UNIXTIME('" . $job['jobinfo']['timestamp'] . "'),
								'" . $job['jobinfo']['docname'] . "',
								'" . $job['jobinfo']['printer'] . "',
								'" . $job['jobinfo']['pages'] . "',
								'" . $job['jobinfo']['cost'] . "',
								'" . $job['jobinfo']['balance'] . "'),";
					}
					$SQL = trim($SQL, ",");
					$result = mysql_query($SQL);
					
					if($result) {
						$msg['msg'] = 'Your request has been saved. Please allow us a few days to let you know how it goes.';
						$msg['status'] = 'success';
						mail("pawprints@baylor.edu", "New refund request from $username", "Username: $username\n\nReason: $reason\n\nPages: $pages");
					} else {
						// Cleanup the previously saved request info
						mysql_query("DELETE FROM requests WHERE id = '$request_id'");
						mysql_query("DELETE FROM requested_jobs WHERE request_id = '$request_id'");
					}
				}
				if($msg['status'] != 'success') {
					$msg['msg'] = 'There was a problem saving your request. Please let us know about this problem at pawprints@baylor.edu';
					$msg['status'] = 'error';
					//echo mysql_error();
				}
			} else {
				$msg['msg'] = 'It seems the jobs you checked didn\'t add up to any pages. Was this an error? Let us know at pawprints@baylor.edu.';
				$msg['status'] = 'error';
			}
		}
	} // end $action=='request'
	
	
	$logfile = 'C:\PCOUNTER\DATA\PCOUNTER.LOG';
	$jobsbydate = array();
	
	/*********************
	***     HISTORY    ***
	*********************/
	
	// Start date of query... varies, perhaps start of semester plus a week before? manually set for now
	switch($duration) {
		case "semester":
			if (time() >= strtotime("August 19, ".date('Y'))) {
				// Fall Semester
				$start_date = strtotime("August 19, ".date('Y'));
				$duration_str = "for the Fall ".date('Y')." semester";
			} else if (time() >= strtotime("May 19, ".date('Y'))) {
				// Summer Semester
				$start_date = strtotime("May 19, ".date('Y'));
				$duration_str = "for the Summer ".date('Y')." semester";
			} else {
				// Spring Semester
				$start_date = strtotime("January 1, ".date('Y'));
				$duration_str = "for the Spring ".date('Y')." semester";
			}
			break;
		case "month":
			$start_date = strtotime(date('F 1, Y'));
			$duration_str = "for ".date('F Y');
			break;
		case "all":
			$start_date = "";
			$duration_str = "since Fall 2008";
			break;
		default:
			$start_date = strtotime((date('w')." days ago"));
			$duration_str = "for the week of ".date('M j, Y', strtotime((date('w')." days ago")));
		
	}
	// End date... now
	$end_date = time();
	
	// Parse the PCOUNTER.LOG file for today's entries, not yet in the mysql DB
	
	/* !!!!!!! 
	** We aren't touching the filesystem anymore, so today's jobs can be found
	** in the print_history.temp table.
	
	if (file_exists($logfile)) {
		$temparray = array();
		$contents = file_get_contents($logfile);
		$lines = explode("\n", trim($contents));
		$num = count($lines);
		foreach($lines as $line) {
			$raw = explode(',', $line);
			if(stripos($raw[0],$username)) {
				$elements = array('username'=>$raw[0], 'docname'=>addslashes($raw[1]), 'printer'=>str_replace('\\\\GUS\\','',$raw[2]), 'timestamp'=>strtotime($raw[3]." ".$raw[4]), 'pages'=>$raw[11], 'cost'=>$raw[12], 'optstring'=>$raw[9], 'balance'=>$raw[13], 'size'=>$raw[10]);
				if(count($elements)) {
					array_unshift($temparray, $elements);
				}
			}
		}
		if(count($temparray)) {
			$jobsbydate[date('F d, Y', $temparray[0]['timestamp'])] = $temparray;
		}
	}
	*/
	
	// Setup query string for history table
	$sql = "SELECT h.* FROM history h WHERE `username` LIKE '%\\\\\\\\$username' AND `date` BETWEEN '".date('Y-m-d',$start_date)."' AND '".date('Y-m-d')."' ORDER BY `date` ASC,`balance` DESC";
	// Perform query and stick the rows into the $jobsbydate array grouping by date
	$result = mysql_query($sql);

	if($result) {
		while($temp = mysql_fetch_array($result)) {
			$temp['timestamp'] = strtotime($temp['date']." ".$temp['time']);
			$jobsbydate[date('F d, Y', $temp['timestamp'])][] = $temp;
		}
	}


	// Repeat query for the temp history table
	
	$sql = "SELECT * FROM temp WHERE `username` LIKE '%\\\\$username' AND `date` BETWEEN '".date('Y-m-d',$start_date)."' AND '".date('Y-m-d')."' ORDER BY `date` ASC,`balance` DESC";
	
	// Perform query and stick the rows into the $jobsbydate array grouping by date
	$result = mysql_query($sql);

	if($result) {
		while($temp = mysql_fetch_array($result)) {
			$temp['timestamp'] = strtotime($temp['date']." ".$temp['time']);
			$jobsbydate[date('F d, Y', $temp['timestamp'])][] = $temp;
		}
	}
	
	// Request the job hashes for this user
	$sql = "SELECT r.username, r.id, j.hash FROM requested_jobs j LEFT JOIN requests r on j.request_id = r.id where r.username LIKE '%$username'";
	$job_hashes = array();
	$result = mysql_query($sql);
	if($result) while($row = mysql_fetch_array($result)) $job_hashes[] = $row['hash'];
	
	$numjobs = count($jobsbydate);
	$row=0;
	
//	echo "<pre align=left>";
//	print_r($jobsbydate);
//	echo "</pre>";

}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	<title>PawPrints Print History Beta: Welcome</title>
	<link href="css/main2.css" media="screen" rel="stylesheet" type="text/css" />
	
	<script type="text/javascript">
		function toggle(id)
		{
			row=document.getElementById('row'+id);
			box=document.getElementById('box'+id);
			rowclassname=row.className;
			if (rowclassname.search(/highlight/)>0)
			{
				row.className='row';
				box.checked='';    	
			}
			else
			{
				row.className='row highlight';
				box.checked='true';
			}				
		}
		function highlight(id)
		{
			row=document.getElementById('row'+id);
			rowclassname=row.className;
			if (rowclassname.search(/highlight/)>0)
			{
				row.className='row';	
			}
			else
			{
				row.className='row highlight';
			}				
		}
	</script>
	
</head>

<body>
	<div id="header">
		<h3>PawPrints Print History for: <?php echo $username?></h3>
		<div id="tab_container">
		    <ul id="tabs">
				<li><a href="?time=week" <?php if ($duration == "week" || !$duration) {?> class="current" <?php } ?>>This Week</a></li>
				<li><a href="?time=month" <?php if ($duration == "month") {?> class="current" <?php } ?>>This Month</a></li>
		  		<li><a href="?time=semester" <?php if ($duration == "semester") {?> class="current" <?php } ?>>This Semester</a></li>
		  		<li><a href="?time=all" <?php if ($duration == "all") {?> class="current" <?php } ?>>All</a></li>
			</ul>
		</div>
	</div>
	
	<div id="Wrapper">
		<p>Print History is currently in Beta, which means that we feel it is good enough to be used by everyone, but there still may be a few bugs. If you experience any problems using this Print History app, please let us know using the <a style="text-decoration: underline" href="http://www.baylor.edu/pawprints/index.php?id=19713">PawPrints comment form</a>. Be sure to include a good description of the problem or we may not be able to help! 
		<div id="msg" class="<?php if($msg) echo $msg['status']; else echo 'hidden'; ?>"><?php echo $msg['msg'] ?></div>
		<p>Here's your print history <?php echo $duration_str ?>. If you had any problems printing and didn't receive your document, you can submit those to us to ask for a refund. Simply check the box next to the job(s), fill out the reason for your request, and hit refund. We will review your request and let you know by email of our decision.</p>
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
							<div class="ppages">PAGES</div>
							<div class="ppages">COST</div>
							<div class="pbal">BALANCE</div>
						</a>
						</li>
						<br clear="all">
					</ul>
				</div>
				<form name="refund_form" action="<?php echo basename(__FILE__) ?>" method="POST">
				<?php if ($numjobs) {
					foreach($jobsbydate as $date) {
						$datestr = strtoupper(date('l, F j \'y', $date[0]['timestamp']));
						?>
						<div class="pdate_container">
							<span class="pdate"><?php echo $datestr ?></span>
							<ul>
								<?php 								$jobs = count($date);
								for ($x=0; $x<$jobs; $x++) {
									
									// Parse the options string and shorted the doc name, if longer than 50 chars
									
									$docname = (strlen($date[$x]['docname']) > 50) ? substr($date[$x]['docname'],0,25)."...".substr($date[$x]['docname'],-25,25) : $date[$x]['docname'];
									$options = "";
									if(strpos($docname, "Deposit")===false && strpos($docname, "SetBal")===false) {
										$color = (preg_match('/\/C\//',$date[$x]['optstring'])) ? true: false;
										$dup = (preg_match('/\/D\//',$date[$x]['optstring'])) ? true : false;
										$copies = preg_split('/\/Cp=/',$date[$x]['optstring']);
										$copies = substr($copies[1], 0, 1);
										$copies = ($copies>1) ? "$copies copies" : "$copies copy";
										if($color)
											if($dup) $options = "Color, 2-sided";
											else $options = "Color";
										else if($dup) $options = "2-sided";
										$options .= (strlen($options)) ? ", $copies" : "$copies";
										$docname .= (strlen($options))? " (<i>".$options."</i>)" : "";
										$printer = str_replace("\\\\GUS\\", "", $date[$x]['queue']);
									} else {	
										$printer = '---';
									}
									
									$jobhash = md5($date[$x]['username'].$date[$x]['docname'].$date[$x]['queue'].$date[$x]['optstring'].$date[$x]['balance']);
									$jobinfo = array("timestamp"=>$date[$x]['timestamp'], "docname"=>$docname, "printer"=>$date[$x]['queue'], "pages"=>$date[$x]['pages'], "cost"=>$date[$x]['cost'], "balance"=>trim($date[$x]['balance']));
									$jobarray = array("jobhash"=>$jobhash, "jobinfo"=>$jobinfo);
					
									$serializedjob = htmlentities(serialize($jobarray));
									
									$disabled = (in_array($jobhash, $job_hashes)) ? true : false;
									?>
									<?php if(!$disabled) { ?>
										<li class="row" id="row<?php echo $row ?>" >
									<?php } else { ?>
										<li class="disabledrow">
									<?php } ?>
									
									<div class="pcheck">
									<?php if(!$disabled) { ?>
										<input id="box<?php echo $row?>" type="checkbox" name="jobarray[]" value='<?php echo $serializedjob ?>' onClick="highlight('<?php echo $row ?>');"/>
									<?php } else { ?>
										<input type="checkbox" disabled/>
									<?php } ?>
									</div>
									<?php if(!$disabled) { ?>
										<span onClick="toggle('<?php echo $row ?>'); return false;">
									<?php } ?>
									<div class="ptime"><?php echo date('g:ia', $date[$x]['timestamp']) ?></div>
									<div class="pdoc"><?php echo $docname ?></div>
									<div class="pprinter"><?php echo $printer ?></div>
									<div class="ppages"><?php echo $date[$x]['pages'] ?></div>
									<div class="ppages"><?php echo $date[$x]['cost'] ?></div>
									<div class="pbal"><?php echo $date[$x]['balance'] ?></div>
									<?php if(!$disabled) { ?>
										</span>
									<?php } ?>
									<br clear="all">
									</a>
									</li>
									<?php 									$row++;
								}
								?>
								
							</ul>
							<br clear="left" />
						</div>
						<?php 					}
				} else {
					echo "<p>Nothing to show $duration_str. Perhaps try searching further back?</p>";
				}
				?>
		</div>
	</div>
	<p>Please enter the reason for your refund request. Did the printer jam, paper have smudges, or did it simply just not come out of the printer?<br/>
		<br/>
		<textarea name="reason" rows="10" cols="90" onfocus="this.style.backgroundColor='#FFF5C5'" onblur="this.style.backgroundColor=''"></textarea><br/>
		<br/>
		<input type="hidden" name="action" value="request" />
		<input type="hidden" name="time" value="<?php echo $_REQUEST['time'] ?>">
		<input type="submit" value="Submit for Refund" /></p>
	</div>
</body>
