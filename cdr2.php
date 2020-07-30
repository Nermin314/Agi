#!/usr/bin/php
<?php

$debug_mode = false;
$log_file = '/tmp/test.log'; 
$allowed_ext = array('1234', 'nermin-office'); 

//get all AGI variables
$agivars = array();
while (!feof(STDIN)) {
	$agivar = trim(fgets(STDIN));
	if ($agivar === '') {
		break;
	}
	else {
		$agivar = explode(':', $agivar);
		$agivars[$agivar[0]] = trim($agivar[1]);
	}
}
foreach($agivars as $k=>$v) {
	log_agi("Got $k=$v");
}
extract($agivars);

//Hangup if it is not valid name
if (!empty($allowed_ext)) {
	if (!in_array($agi_callerid, $allowed_ext)) {
		log_agi("Call rejected from $agi_calleridname <$agi_callerid>");
		execute_agi('STREAM FILE goodbye ""');
		execute_agi('HANGUP');
		exit;
	}
}
$servername = "localhost";
$username = "asterisk";
$password = "asterisk";
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
  }
  mysqli_select_db($conn,"asterisk");
  $sql = "SELECT pw FROM demo";
  $res =$conn->query($sql);
  if(! $res) {
  	die('Could not get pw: ' . mysql_error());
 }

$row = mysqli_fetch_array($res);
$pw=$row['pw'];

execute_agi('STREAM FILE vasastarasifra ""');
execute_agi("SAY DIGITS $pw \"\"");

$ext = '';
$result = execute_agi('STREAM FILE unesitenovusifru "0123456789"');
if ($result['result'] == 0) {
		$result = execute_agi("GET DATA unesitenovusifru 5000 4");
}
else {
	$ext = chr($result['result']);
}

if (strlen($ext) < 4) {
	//still no input of digits
	if (empty($ext)) {
		execute_agi('STREAM FILE unesitenovusifru ""');
	}
	while (strlen($ext) < 4) {
		$result = execute_agi('WAIT FOR DIGIT -1');
		//Digits only
		if ($result['result'] >= 48 && $result['result'] <= 57) {
			$ext .= chr($result['result']);
		}
		else {
			continue;
		}
	}
}

$sql2 = "UPDATE demo SET pw=$ext WHERE name='$agi_callerid'";
$res =$conn->query($sql2);

log_agi("Got extension $ext");

execute_agi('STREAM FILE vasanovasifra ""');
execute_agi("SAY DIGITS $ext \"\"");
execute_agi('STREAM FILE dovidjenja ""');
execute_agi('HANGUP');
exit;

function execute_agi($command) {
	global $debug_mode, $log_file;

	fwrite(STDOUT, "$command\n");
	fflush(STDOUT);
	$result = trim(fgets(STDIN));
	$ret = array('code'=> -1, 'result'=> -1, 'timeout'=> false, 'data'=> '');
	if (preg_match("/^([0-9]{1,3}) (.*)/", $result, $matches)) {
		$ret['code'] = $matches[1];
		$ret['result'] = 0;
		if (preg_match('/^result=([0-9a-zA-Z]*)\s?(?:\(?(.*?)\)?)?$/', $matches[2], $match))  {
			$ret['result'] = $match[1];
			$ret['timeout'] = ($match[2] === 'timeout') ? true : false;
			$ret['data'] = $match[2];
		}
	}
	if ($debug_mode && !empty($logfile)) {
		$fh = fopen($logfile, 'a');
		if ($fh !== false) {
			$res = $ret['result'] . (empty($ret['data']) ? '' : " / $ret[data]");
			fwrite($fh, "-------\n>> $command\n<< $result\n<<     parsed $res\n");
			fclose($fh);
		}
	}
	return $ret;
}

function log_agi($entry, $level = 1) {
	if (!is_numeric($level)) {
		$level = 1;
	}
	$result = execute_agi("VERBOSE \"$entry\" $level");
}
?>
