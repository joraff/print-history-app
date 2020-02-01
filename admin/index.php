<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
	<title>PawPrints Print History: Welcome</title>
	<link href="playground.css" media="screen" rel="stylesheet" type="text/css" />
	
	<script src="mootools.core.js" type="text/javascript"></script>
	<script src="mootools.more.js" type="text/javascript"></script>
	
	<script type="text/javascript">
		function toggleTextarea(id)
		{
			text = new Fx.Slide('textarea'+id, {duration: 250, onComplete: function(){ } });
			var show = false;
			if (!$('approve'+id).checked) 
			{
				show = true;
			}
			if(!$('deny'+id).checked)
			{
				show = true;
			}
			if($('send'+id).checked)
			{
				show = true;
			}
			if(show)
			{
				text.slideIn();
			} else
			{ 
				text.slideOut();
			}
			return false;
		}
		
	function selectRadioValues(value,theElements) {
	//Programmed by Shawn Olson
	//Copyright (c) 2007
	//Permission to use this function provided that it always includes this credit text
	//  http://www.shawnolson.net
	//Find more JavaScripts at http://www.shawnolson.net/topics/Javascript/
	//This script was modified from the function checkUncheckSome() also
	//created by Shawn Olson

	//theElements is an array of objects designated as a comma separated list of their IDs
	//All Radio inputs with a value matching value will be selected inside theElements


	 var formElements = theElements.split(',');
	 for(var z=0; z<formElements.length;z++)
	 {
		theItem = document.getElementById(formElements[z]);
		if(theItem)
		{
			theInputs = theItem.getElementsByTagName('input');
			for(var y=0; y<theInputs.length; y++)
			{
				if(theInputs[y].type == 'radio')
				{
		 			theName = theInputs[y].name;
		 			if(theInputs[y].value==value)
					{
		   				theInputs[y].checked='checked';
		 			}
				}
	  		}
		}
	 }
	}
	</script>
	
</head>

<?php

error_reporting (E_ALL);

//echo "<pre align='left'>";
//print_r($_REQUEST);
//echo "</pre>";

function pluralize( $string , $amount ) 
{
	if($amount>1) {
    	$plural = array(
    	    array( '/(quiz)$/i',               "$1zes"   ),
			array( '/^(ox)$/i',                "$1en"    ),
			array( '/([m|l])ouse$/i',          "$1ice"   ),
			array( '/(matr|vert|ind)ix|ex$/i', "$1ices"  ),
			array( '/(x|ch|ss|sh)$/i',         "$1es"    ),
			array( '/([^aeiouy]|qu)y$/i',      "$1ies"   ),
			array( '/([^aeiouy]|qu)ies$/i',    "$1y"     ),
		    array( '/(hive)$/i',               "$1s"     ),
		    array( '/(?:([^f])fe|([lr])f)$/i', "$1$2ves" ),
		    array( '/sis$/i',                  "ses"     ),
		    array( '/([ti])um$/i',             "$1a"     ),
		    array( '/(buffal|tomat)o$/i',      "$1oes"   ),
    	    array( '/(bu)s$/i',                "$1ses"   ),
		    array( '/(alias|status)$/i',       "$1es"    ),
		    array( '/(octop|vir)us$/i',        "$1i"     ),
		    array( '/(ax|test)is$/i',          "$1es"    ),
		    array( '/s$/i',                    "s"       ),
		    array( '/$/',                      "s"       )
    	);
    	
    	$irregular = array(
    		array( 'move',   'moves'    ),
    		array( 'sex',    'sexes'    ),
    		array( 'child',  'children' ),
    		array( 'man',    'men'      ),
    		array( 'person', 'people'   )
    	);

		$uncountable = array( 
    		'sheep', 
    		'fish',
    		'series',
    		'species',
    		'money',
    		'rice',
    		'information',
    		'equipment'
    	);

    	// save some time in the case that singular and plural are the same
    	if ( in_array( strtolower( $string ), $uncountable ) )
			break;
    	
    	// check for irregular singular forms
    	foreach ( $irregular as $noun )
    	{
    	if ( strtolower( $string ) == $noun[0] )
    	    return $noun[1];
    	}
    	
    	// check for matches using regular expressions
    	foreach ( $plural as $pattern )
    	{
    	if ( preg_match( $pattern[0], $string ) )
			return preg_replace( $pattern[0], $pattern[1], $string );
    	}
	}
    
	return $string;
}

if ( $_SERVER['AUTH_USER'] ) {
	
	ini_set("sendmail_from", "pawprints@baylor.edu");
	
	$headers = 'From: pawprints@baylor.edu'. "\r\n" .
	    'Reply-To: pawprints@baylor.edu' . "\r\n" .
		'Bcc: pawprints@baylor.edu' . "\r\n" .
	    'X-Mailer: PHP/' . phpversion();
	
	
	$requestarray = (isset($_REQUEST['requests'])) ? $_REQUEST['requests']: array();
	

	// Setup variables
	// Get authenticated username for queries
	$adminname = str_replace('BAYLOR\\','',$_SERVER['AUTH_USER']);
	//$adminname = 'Joseph_Rafferty';
	
	$db = mysql_connect('macgyver', 'username', 'password');
	if (!$db) {
	    die('Could not connect: ' . mysql_error());
	}
	mysql_select_db('print_history');
	if(count($requestarray)) {
		foreach($requestarray as $request_id=>$attr) {
			
			switch($attr['action']) {
				case "Approve":
					// All you have to do is set the approved flag to 1 for an approval
					$comment = mysql_real_escape_string($attr['requestMsg']);
					$pages = $attr['pages'];
					$result = mysql_query($sql = "UPDATE requests SET approved = '1', comment = '$comment', pages_approved = '$pages', refund_by = '$adminname', refund_timestamp = NOW() WHERE id = $request_id");
					if($result) $msgactions[] = $attr['username']." was approved ".$attr['pages']." pages<br>";
					else $msgactions[] = "There was a problem saving the approved for ".$attr['username']." (".$attr['pages']." pages)<br>";
					break;
					
				case "Deny":
				print_r($attr);
				// set the status of $request_id to denied, refund_date (also for denials), and the admin
					$notify = ($attr['notifyDeny'] == 'on') ? 1 : 0;
					$comment = mysql_real_escape_string($attr['requestMsg']);
					$sql = "UPDATE `requests` SET approved = '-1', refund_timestamp = NOW(), comment = '$comment', refund_by = '$adminname', donotify = '$notify' WHERE `id` = '$request_id'";
					$result = mysql_query($sql);
					if($result) $msgactions[] = $attr['username']." was <b>denied</b> ".$attr['pages']." pages<br>";
					else $msgactions[] = "There was a problem saving the denial for ".$attr['username']." (".$attr['pages']." pages)<br>";
					break;
				case "Email":
					mail($attr['username']."@baylor.edu", "Re: PawPrints Refund Request #".$request_id, $attr['comment']);
					
			}
		}
	}
	$requestsbydate = array();
	$requests = array();
	$jobs = array();
	
	// fetch all open requests
	$sql = "SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp,  UNIX_TIMESTAMP(refund_timestamp) as refund_timestamp FROM requests WHERE `approved` = '0' ORDER BY timestamp ASC";
	$result = mysql_query($sql);
	if($result) {
		while($temp = mysql_fetch_assoc($result)) {
			$requests[] = $temp;
		}
	}

	// use the open request ids to fetch jobs
	if(count($requests)) {
		$sql = "SELECT rj.*, UNIX_TIMESTAMP(rj.timestamp) as timestamp FROM requested_jobs rj WHERE ";
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
		//echo "<pre align=left>"; print_r($jobs); echo "</pre>";
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
	

?>


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
		<p><a href="#" onClick="selectRadioValues('Approve', 'theElements');">Approve All</a> | <a href="#" onClick="selectRadioValues('', 'theElements');">No Action all</a></p>
		<form name="requestsform" action="index.php" method="post" id="theElements">
			<input type="submit" value="Commit Changes">
			<div class="fix_width">
				<?php
				if(count($requestsbydate)) {
					foreach($requestsbydate as $key=>$requests) {
						$datestr = strtoupper(date('l, j F', strtotime($key)));
				?>
				<div class="Container">
					<div>
						<div class="pdate_container">
							<span class="pdate"><?php echo $datestr ?></span>
							<ul>
								<?php
									$jobs = count($requests);
									for ($x=0; $x<$jobs; $x++) {
										$id = $requests[$x]['id'];
								?>
								<li class="summary"><span class="bigpages"><?php echo $requests[$x]['pages_requested'] ?></span> pages being requested by <b><?php echo $requests[$x]['username'] ?></b> (<a href="https://gus.baylor.edu/print_history/admin/history.php?username=<?php echo $requests[$x]['username'] ?>" target="_new">history</a>) (<a href="javascript: return false;" class="histlink" onclick="jobs<?php echo $id ?>.toggle()">in <?php echo count($requests[$x]['jobs']) ?> jobs</a>)</li>
								<div class="indent">
									<li class="reason"><?php echo $requests[$x]['reason'] ?></li>
									
									<script>
										window.addEvent('domready', function(){
											jobs<?php echo $id ?> = new Fx.Slide('jobsContainer<?php echo $id ?>', {duration: 250, onComplete: function(){ } }).hide();
										});
										window.addEvent('domready', function(){
											text<?php echo $id ?> = new Fx.Slide('textarea<?php echo $id ?>', {duration: 250, onComplete: function(){ } }).hide();
										});
									</script>
									
									<div id="jobsContainer<?php echo $id ?>" class="jobsContainer">
										<?php
										foreach($requests[$x]['jobs'] as $job) {
										?>
										<li>
											<div class="ptime"><?php echo date('n/j H:i', $job['timestamp']) ?></div>
											<div class="pdoc"><?php echo $job['docname'] ?></div>
											<div class="pprinter"><?php echo $job['printer'] ?></div>
											<div class="ppages">Pgs: <?php echo $job['pages'] ?></div>
											<div class="ppages">Cost: <?php echo $job['cost'] ?></div>
											<div class="ppages">Bal: <?php echo $job['balance'] ?></div>
										</li>
										<?php
										}
										?>
									</div>
									
									<li>
										<input type="radio" name="requests[<?php echo $id ?>][action]" value="Approve" onClick="toggleTextarea('<?php echo $id ?>');"/>
										Approve  (Use standard reply?<input type="checkbox" name="requests[<?php echo $id ?>][stndApproveMsg]" checked onClick="toggleTextarea('<?php echo $id ?>');" id="approve<?php echo $id ?>">
										 # of Pages? <input type="text" name="requests[<?php echo $id ?>][pages]" size="3" value="<?php echo $requests[$x]['pages_requested'] ?>">)
									</li>
									<li>
										<input type="radio" name="requests[<?php echo $id ?>][action]" value="Deny" onClick="toggleTextarea('<?php echo $id ?>');">
										Deny  (Notify patron? <input type="checkbox" name="requests[<?php echo $id ?>][notifyDeny]" checked> Use standard reply?<input type="checkbox" name="requests[<?php echo $id ?>][stndDenyMsg]" checked id="deny<?php echo $id ?>" onClick="toggleTextarea('<?php echo $id ?>');">)
									</li>
									<li>
										<input type="radio" name="requests[<?php echo $id ?>][action]" value="Email" onClick="toggleTextarea('<?php echo $id ?>');" id="send<?php echo $id ?>"> Send Email (use box)
									</li>
									<li><input type="radio" name="requests[<?php echo $id ?>][action]" value="" checked onClick="toggleTextarea('<?php echo $id ?>');">No action</li>
									<textarea name="requests[<?php echo $id ?>][requestMsg]" rows="4" cols="80" onfocus="this.style.backgroundColor='#FFF5C5';" onblur="this.style.backgroundColor=''" id="textarea<?php echo $id?>"></textarea>
									<input type="hidden" name="requests[<?php echo $id ?>][username]" value="<?php echo $requests[$x]['username'] ?>" />
									<br clear="all">
								</div>
								<?php if($x<($jobs-1)) echo "<hr>";
								}
								?>
							</ul>
							<br clear="left" />
						</div>
					</div>
				</div> <!-- div: Container -->
			

<?php 		
		}
?>
			<input type="submit" value="Commit Changes">
			</div> <!-- div: fix_width -->
		</form>
	</div> <!-- div: Wrapper -->
</body>
</html>
<?php	} else {
			echo "<h3>Woohoo! No open requests.</h3>";
		}
} else { echo "You must login to view this page"; }
?>