<?php
header("HTTP/1.0 200 OK");
header("Content-Type: text/html");

//error_reporting (0);

if ( $_SERVER['AUTH_USER'] ) {
	
	ini_set("sendmail_from", "pawprints@baylor.edu");
	
	$headers = 'From: pawprints@baylor.edu'. "\r\n" .
	    'Reply-To: pawprints@baylor.edu' . "\r\n" .
		'Cc: pawprints@baylor.edu' . "\r\n" .
	    'X-Mailer: PHP/' . phpversion();
	
	print_r($_REQUEST);
	$requestarray = $_REQUEST['requests'];
	//echo "<pre align=left>";
	//print_r($requestarray);
	//echo "</pre>";



	// Setup variables
	// Get authenticated username for queries
	$adminname = str_replace('BAYLOR\\','',$_SERVER['AUTH_USER']);
	//$adminname = 'Joseph_Rafferty';
	
	$db = mysql_connect('.', 'username', 'password');
	if (!$db) {
	    die('Could not connect: ' . mysql_error());
	}
	mysql_select_db('print_history');
	if(count($requestarray)) {
		echo "test 1";
		foreach($requestarray as $request_id=>$attr) {
			switch($attr['action']) {
				case "Approve":
				
					// execute account.exe to replace the balance, which will insert into pcounter.log
					
					$pathToAccountExe = "C:\Program Files\Pcounter for NT\NT\ACCOUNT";
					
					exec($pathToAccount." VIEWBAL ".$attr['username'] . " | find balance | grep \"{print $6}\"", $beginningBal);
					echo "beginning balance = ".$beginningBal;
					$execstr .= $pathToAccount." ".$accountAction." ".$attr['username'] . " " . $attr['pages'] . " 'Print History Refund by $adminname on ".date('m/d/Y')."'";
					//exec($execstr, $output);
					//$execSuccess = strpos($output, "Unable");
					
					//preg_match_all('/.*\s(-?\d+)/', exec($pathToAccount." VIEWBAL ".$attr['username']. " | find 'balance'"), $out);
					$balance = $out[1][0];
					
					// if the account.exe refund was successful, mark as so in the DB
					if($execSuccess) {
						
						// set the status of $request_id to approved, refund_date (also for denials), and the admin
						$sql = "UPDATE `requests` SET `status` = 'approved', `pages_refunded` = '".$attr['pages']."', `refund_timestamp` = NOW(), `refund_by` = '$adminname' WHERE `id` = '$request_id'";
						$result = mysql_query($sql);
						if($result) {
							$msgactions[] = $attr['username']." was <b>refunded</b> ".$attr['pages']." pages";
							$to = $attr['username']."@baylor.edu";
							$subject = "Re: PawPrints refund request for ".$attr['pages']." pages";
							$body = ($attr['stndApproveMsg']=='on') ? "Thank you for using PawPrints. We're sorry you had a problem, but are happy to let you know that you've been refunded ".$attr['pages']." pages. Your new balance is":"";
							//mail( )
						}
					} else $msgactions[] = "There was a problem refunding ".$attr['username']." ".$attr['pages']." pages. ACCOUNT.EXE output: $output";
					
					break;
					
				case "Deny":
				// set the status of $request_id to denied, refund_date (also for denials), and the admin
					$sql = "UPDATE `requests` SET `status` = 'denied', `pages_refunded` = '0', `refund_timestamp` = NOW(), `refund_by` = '$adminname' WHERE `id` = '$request_id'";
					$result = mysql_query($sql);
					if($result) $msgactions[] = $attr['username']." was <b>denied</b> ".$attr['pages']." pages";
					else $msgactions[] = "There was a problem denying ".$attr['username']." ".$attr['pages']." pages";
					break;
				case "Email":
					
					
			}
		}
	}
	$requestsbydate = array();
	$requests = array();
	$jobs = array();
	
	// fetch all open requests
	$sql = "SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp,  UNIX_TIMESTAMP(refund_timestamp) as refund_timestamp FROM requests WHERE `status` = 'open' ORDER BY timestamp ASC";
	$result = mysql_query($sql);
	if($result) {
		while($temp = mysql_fetch_assoc($result)) {
			$requests[] = $temp;
		}
	}

	// use the open request ids to fetch jobs
	if(count($requests)) {
		$sql = "SELECT j.*, UNIX_TIMESTAMP(j.timestamp) as timestamp, rj.request_id FROM requested_jobs rj JOIN history j ON (rj.job_id=j.id) WHERE ";
		foreach($requests as $request) {
			$sql .= "rj.request_id = ".$request['id']." OR ";
		}
		$sql = trim($sql, ' OR');
		$result = mysql_query($sql);
		if($result) {
			while($temp = mysql_fetch_assoc($result)) {
				$jobs[$temp['request_id']][] = $temp;
			}
		}
		/*
		foreach($requests as $request) {
			$request['jobs'] = $jobs[$request['id']];
		}
		*/
	}
	
	if(count($jobs)) {
		//print_r($jobs);
		foreach($requests as $key=>$request) {
			$request['jobs'] = $jobs[$request['id']];
			$requestsbydate[date('F d, Y', $request['timestamp'])][] = $request;
		}		
/*		echo "<pre align=left>";
		print_r($requestsbydate);
		echo "</pre>";*/
	}
	
echo "hello";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	<title>PawPrints Print History: Welcome</title>
	<link href="../css/main2.css" media="screen" rel="stylesheet" type="text/css" />
	<link href="../css/admin.css" media="screen" rel="stylesheet" type="text/css" />
	
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
	</script>
	
</head>

<body>
	<div id="header" class="adminheader">
		<h3>PawPrints Requests Center</h3>
		<div id="tab_container">
		    <ul id="tabs">
				<li><a href="?" class="current">Open Requests</a></li>
			</ul>
		</div>
	</div>
	
	<div id="Wrapper">
		<div id="msg" class="<?php if(!$msgactions) echo 'hidden'; else echo 'success'; ?>">
			<ul>
				<?php
				foreach($msgactions as $msg) {
					echo "<li>$msg</li>";
				}
				?>
			</ul>
		</div>
		<p>Here are all of the "open" requests, <b>oldest</b> first. Evaluate the number of pages, reason, etc., and choose your action. You may send a customized response along with your decision as well as refund a custom number of pages.</p>
	  <div class="fix_width">
		<div id="Container">
			<div id="Main">
				<form name="requestsform" action="./index.php" method="POST">
	<?php
	if(count($requestsbydate)) {
		foreach($requestsbydate as $key=>$requests) {
			$datestr = strtoupper(date('l, j F', strtotime($key)));
			?>
			<div class="pdate_container">
				<span class="pdate"><?php echo $datestr ?></span>
				<ul>
					<?php
					$jobs = count($requests);
					for ($x=0; $x<$jobs; $x++) {
						?>
						<li class="summary"><span class="bigpages"><?php echo $requests[$x]['pages_requested'] ?></span> pages being requested by <b><?php echo $requests[$x]['username'] ?></b> in <?php echo count($requests[$x]['jobs']) ?> jobs.</li>
						<li><i><?php echo $requests[$x]['reason'] ?></i></li>
						<?php
						foreach($requests[$x]['jobs'] as $job) {
							$docname = (strlen($job['docname']) > 30) ? substr($job['docname'],0,15)."...".substr($job['docname'],-15,15) : $job['docname'];
							$options = "";									
							$color = (preg_match('/\/C\//',$job['optstring'])) ? true: false;
							$dup = (preg_match('/\/D\//',$job['optstring'])) ? true : false;
							$copies = preg_split('/\/Cp=/',$job['optstring']);
							$copies = substr($copies[1], 0, 1);
							if($color)
								if($dup) $options = "Color, 2-sided";
								else $options = "Color";
							else if($dup) $options = "2-sided";
							$docname .= (strlen($options))? " (".$options.")" : "";
							?>
							<li>
							<a class="row">
								<div class="ptime"><?php echo date('g:ia', $job['timestamp']) ?></div>
								<div class="pdoc"><?php echo $docname ?></div>
								<div class="pprinter"><?php echo $job['printer'] ?></div>
								<div class="ppages">Cps: <?php echo $copies ?></div>
								<div class="ppages">Pgs: <?php echo $job['pages'] ?></div>
								<div class="pbal"><?php echo $job['balance'] ?></div>
							</a>
							</li>
						<?php
						}
						?>
						<li><input type="radio" name="requests[<?php echo $requests[$x]['id'] ?>][action]" value="Approve">Approve  (Use standard reply?<input type="checkbox" name="requests[<?php echo $requests[$x]['id'] ?>][stndApproveMsg]" checked> # of Pages? <input type="text" name="requests[<?php echo $requests[$x]['id'] ?>][pages]" size="3" value="<?php echo $requests[$x]['pages_requested'] ?>">)</li>
						<li><input type="radio" name="requests[<?php echo $requests[$x]['id'] ?>][action]" value="Deny">Deny  (<input type="checkbox" name="requests[<?php echo $requests[$x]['id'] ?>][notifyDeny]" checked>Notify patron?)</li>
						<li><input type="radio" name="requests[<?php echo $requests[$x]['id'] ?>][action]" value="Email">Email patron with:<br><textarea name="requests[<?php echo $requests[$x]['id'] ?>][requestMsg]" rows="4" cols="80" onfocus="this.style.backgroundColor='#FFF5C5'; if(this.innerHTML == 'Denial Note, Custom Approval Message, or email body.') this.innerHTML = ''" onblur="this.style.backgroundColor=''">Denial Note, Custom Approval Message, or email body.</textarea></li>
						<li><input type="radio" name="requests[<?php echo $requests[$x]['id'] ?>][action]" value="" checked>No action</li>
						<input type="hidden" name="requests[<?php echo $requests[$x]['id'] ?>][username]" value="<?php echo $requests[$x]['username'] ?>" />
						<br clear="all">
						<?php if($x<($jobs-1)) echo "<hr>";
					}
					?>
					
				</ul>
				<br clear="left" />
			</div>
			<input type="submit" value="Commit Changes">
			<?php 		}
	}
} else { echo "You must login to view this page"; }
?>