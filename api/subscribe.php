<?php

$file = 'resources/targets.json';

$url = $_REQUEST['url'];

if ( $raw = file_get_contents($file) ) {
	$targets = json_decode($raw, true);
} else {
	http_response_code(500);
	exit();
}

if ( $id = array_search($url, $targets) ) {
	$result = true;
} else {
	$id = uniqid();
	$targets[$id] = $url;
	$result = file_put_contents($file, json_encode($targets), LOCK_EX);
}

if ($result) {
	http_response_code(201);
	$return = array('id' => $id);
	echo json_encode($return);
} else {
	http_response_code(500);
}

?>
