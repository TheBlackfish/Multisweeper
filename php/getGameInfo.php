<?php

require_once('../../../../database.php');
require_once('translateGameState.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	header('Content-Type: text/xml');
	
	$conn = new mysqli($sqlhost, $sqlusername, $sqlpassword);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
	
	$doc = new DOMDocument('1.0');
	$doc->formatOutput = true;
	$root = $doc->createElement('stats');
	$root = $doc->appendChild($root);
	
	/*$sql = "SELECT playername, lpad(score,60,'0') as sc FROM pythongame.scores ORDER BY sc DESC LIMIT 100";
	$result = $conn->query($sql);
	
	while ($row = mysqli_fetch_row($result)) {
		$newrow = $doc->createElement('info');
		$newrow = $root->appendChild($newrow);
			
		$name = $doc->createElement('name');
		$name = $newrow->appendChild($name);
		$tick = $doc->createTextNode($row[0]);
		$tick = $name->appendChild($tick);
		
		$exeN = $doc->createElement('score');
		$exeN = $newrow->appendChild($exeN);
		$exe = $doc->createTextNode($row[1]);
		$exe = $exeN->appendChild($exe);
	}*/
	
	$r = $doc->saveXML();
	echo $r;
}
?>