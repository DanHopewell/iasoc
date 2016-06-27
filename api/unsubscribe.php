<?php

$file = 'resources/targets.json';

$id = $_GET['id'];

if ( $targets = json_decode(file_get_contents($file), true) ) {
	if ( array_key_exists($id, $targets) ) {
		unset($targets[$id]);
		$result = file_put_contents($file, json_encode($targets), LOCK_EX);
	} else {
		http_response_code(404);
		exit();
	}
} else {
	$result = FALSE;
}

if ($result) {
	http_response_code(200);
} else {
	http_response_code(500);
}

?>
