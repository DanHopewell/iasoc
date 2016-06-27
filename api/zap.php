<?php

class Zap
{
	public $targetsFile = 'resources/targets.json';
	public $tokensFile = 'files/tokens.csv';
	public $membersFile = 'files/members.csv';
	public $logFile = 'logs/requests.txt';

	private $_request;
	private $_data = array();
	private $_tokensArray;
	private $_membersArray;



	public function __construct()
	{

	}

	public function doTheThing()
	{
		if ( !$arr = json_decode(file_get_contents('php://input'), true) ) {
			$this->_oops("Request problem");
		}

		if ( is_array($arr[0]) ) {
			$this->_request = $arr;
		} else {
			$this->_request = array();
			$this->_request[] = $arr;
		}

		$this->_loadFiles();

		foreach ($this->_request as $member) {
			$m = array();
			$m['id'] = $member['id'];
			$m['fName'] = $member['fName'];
			$m['lName'] = $member['lName'];
			$m['email'] = $member['email'];
			if ( $m['token'] = $this->_getToken($m) ) {
				$this->_data[] = $m;
				$this->_yay($m['email'] . ' = ' . $m['token']);
			} else {
				break;
			}
		}

		$this->_send();
		$this->_writeFiles();
	}



	private function _loadFiles()
	{
		if ( !$this->_tokensArray = $this->_csvToArray($this->tokensFile) ) {
			$this->_oops("Can't load tokens file");
		}
		if ( !$this->_membersArray = $this->_csvToArray($this->membersFile) ) {
			$this->_oops("Can't load members file");
		}	
	}

	private function _writeFiles()
	{
		if ( !$this->_arrayToCsv($this->_tokensArray, $this->tokensFile) ) {
			$this->_oops("Can't write tokens file");
		}
		if ( !$this->_arrayToCsv($this->_membersArray, $this->membersFile) ) {
			$this->_oops("Can't write members file");
		}	
	}

	private function _send()
	{
		$data = json_encode($this->_data);

testLog(__LINE__, $data);

		if ( !$targets = json_decode(file_get_contents($this->targetsFile), true) ) {
			$this->_oops("Can't load targets file");
		}
		$badTargets = array();

		foreach ($targets as $id => $url) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

			$result = curl_exec($ch);
			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($status == 200) {
				$this->_yay("sent to " . $url);
			}
			if ($status == 410) {
				$badTargets[] = $id;
			}

			curl_close($ch);
		}

		if (!empty($badTargets)) {
			foreach ($badTargets as $id) {
				unset($targets[$id]);
			}
			@file_put_contents($this->targetsFile, json_encode($targets), LOCK_EX);
		}
	}

	private function _memberExists($id)
	{
		foreach ($this->_membersArray as $member) {
			if ($member['id'] == $id) {
				return $member;
			}
		}
		return false;
	}

	private function _getToken($arr)
	{
		if ( $m = $this->_memberExists($arr['id']) ) {
			$arr['token'] = $m['token'];
		} elseif ( $row = array_shift($this->_tokensArray) ) {
			$arr['token'] = $row['token'];
			array_push($this->_membersArray, $arr);
		} else {
			return false;
		}
		return $arr['token'];
	}

	private function _csvToArray($filename='', $delimiter=',')
	{
		if(!file_exists($filename) || !is_readable($filename)) {
			return FALSE;
		}

		ini_set("auto_detect_line_endings", true);
		
		$data = array();
		$header = NULL;

		if (($handle = fopen($filename, 'r')) !== FALSE) {
			while (($row = fgetcsv($handle, 1024, $delimiter)) !== FALSE) {
				if(!$header) {
					$header = array_map('trim', $row);
				} else {
					$data[] = array_combine($header, $row);
				}
			}
			fclose($handle);
		} else {
			return FALSE;
		}
		
		return $data;
	}

	private function _arrayToCsv($data, $filename='', $delimiter=',')
	{
		if (($handle = fopen($filename, 'w')) !== FALSE) {
			fputcsv($handle, array_keys($data[0]), $delimiter);
			foreach ($data as $row) {
				fputcsv($handle, $row, $delimiter);
			}
			fclose($handle);
			return TRUE;
		} else {
			return FALSE;
		}
	}

	private function _oops($oops)
	{
		$this->_log("\tError\t" . $oops);
		exit();
	}

	private function _yay($yay)
	{
		$this->_log("\tSuccess\t" . $yay);
	}

	private function _log($message)
	{
		$message = date('r') . $message . PHP_EOL;
		@file_put_contents($this->logFile, $message, FILE_APPEND | LOCK_EX);
	}



}


$z = new Zap;
$z->doTheThing();



?>
