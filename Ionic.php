<?php header('Content-Type: application/json', true, 202);

/*
Ionic Scanner - Scan and validates IonCube Loader encoded files.
Copyright (C) 2016  Niall Newman (niall.newman@btinternet.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

ini_set('precision', 8);
ini_set('max_execution_time', 600);

error_reporting(E_ALL);
date_default_timezone_set("UTC");

define('IonicLoaded', TRUE);

require 'vendor/autoload.php';
require 'config.php';
require 'strings.php';

class SmithWatermanGotoh 
{
	private $gapValue;
	private $leftSubstitution;
	
	/**
	* Constructs a new Smith Waterman metric.
	* 
	* @param gapValue
	*            a non-positive gap penalty
	* @param substitution
	*            a substitution function
	*/
	public function __construct($gapValue=-0.5, $leftSubstitution=null) {
		if($gapValue > 0.0) throw new Exception("gapValue must be <= 0");
		if (empty($leftSubstitution)) $this->leftSubstitution = new SmithWatermanMatchMismatch(1.0, -2.0);
		else $this->leftSubstitution = $leftSubstitution;
		$this->gapValue = $gapValue;
	}
	
	public function compare($left, $right) {
		if (empty($left) && empty($right)) {
			return 1.0;
		}
		if (empty($left) || empty($right)) {
			return 0.0;
		}
		if (md5($left) == md5($right)) {
			return 1.0;
		}
		
		$maxDistance = min(mb_strlen($left), mb_strlen($right)) * max($this->leftSubstitution->max(), $this->gapValue);
		return $this->analyse($left, $right) / $maxDistance;
	}
	
	private function analyse($left, $right) {
		$deletion = [];
		$insertion = [];
		$rightLength = mb_strlen($right);
		$max = $deletion[0] = max(0, $this->gapValue, $this->leftSubstitution->compare($left, 0, $right, 0));
		
		for ($j = 1; $j < $rightLength; $j++) {
			$deletion[$j] = max(0, $deletion[$j - 1] + $this->gapValue, $this->leftSubstitution->compare($left, 0, $right, $j));
			$max = max($max, $deletion[$j]);
		}
		
		for ($i = 1; $i < mb_strlen($left); $i++) {
			$insertion[0] = max(0, $deletion[0] + $this->gapValue, $this->leftSubstitution->compare($left, $i, $right, 0));
			$max = max($max, $insertion[0]);
			
			for ($j = 1; $j < $rightLength; $j++) {
				$insertion[$j] = max(0, $deletion[$j] + $this->gapValue, $insertion[$j - 1] + $this->gapValue,
								        $deletion[$j - 1] + $this->leftSubstitution->compare($left, $i, $right, $j));
				$max = max($max, $insertion[$j]);
			}
			
			for ($j = 0; $j < $rightLength; $j++) {
				$deletion[$j] = $insertion[$j];
			}
		}
		return $max;
	}
}

class SmithWatermanMatchMismatch
{
	private $matchValue;
	private $mismatchValue;
	
	/**
	* Constructs a new match-mismatch substitution function. When two
	* characters are equal a score of <code>matchValue</code> is assigned. In
	* case of a mismatch a score of <code>mismatchValue</code>. The
	* <code>matchValue</code> must be strictly greater then
	* <code>mismatchValue</code>
	* 
	* @param matchValue
	*            value when characters are equal
	* @param mismatchValue
	*            value when characters are not equal
	*/
	public function __construct($matchValue, $mismatchValue) {
		if($matchValue <= $mismatchValue) throw new Exception("mismatchValue must be > matchValue");
		
		$this->matchValue = $matchValue;
		$this->mismatchValue = $mismatchValue;
	}
	
	public function compare($left, $leftIndex, $right, $rightIndex) {
		return ($left[$leftIndex] === $right[$rightIndex] ? $this->matchValue
				                : $this->mismatchValue);
	}
	
	public function max() {
		return $this->matchValue;
	}
	
	public function min() {
		return $this->mismatchValue;
	}
}

function statusSuccess(string $message = "none") {
	return sprintf("{status: \"success\", message: \"%s\"}", $message);
}

function statusFail(string $message = "none") {
	return sprintf("{status: \"fail\", message: \"%s\"}", $message);
}

function statusTimeout(string $message = "none") {
	return sprintf("{status: \"timeout\", message: \"%s\"}", $message);
}


if ($use_ssl) {
	if (!preg_match("/\?ssl=true/", $mongo_server)) {
		$mongo_server .= (substr($mongo_server, -1) != '/' ? '/?ssl=true' : '?ssl=true');
	}
	$conn = new MongoDB\Client($mongo_server, [], [
		"local_cert" => $ssl_local_cert,
		"passphrase" => $ssl_cert_passphrase,
		"cafile" => $ssl_dir . '/' . $ssl_file,
		"allow_self_signed" => $ssl_allow_self_signed,
		"verify_peer" => $ssl_verify_peer,
		"verify_peer_name" => $ssl_verify_peer_name,
		"verify_expiry" => $ssl_verify_expiry,
	]);
}
else {
	$conn = new MongoDB\Client($mongo_server);
}

$found = FALSE;

foreach ($conn->listDatabases() as $db_info) {
	if ($db_info->getName() == $db_name) {
		$found = TRUE;
		break;
	}
}

if (!$found) die(statusFail($err_db_name_invalid));

$db = $conn->$db_name;
$found = FALSE;

foreach ($db->listCollections() as $coll_info) {
	if ($coll_info->getName() == $coll_name) {
		$found = TRUE;
		break;
	}
}

if (!$found) die(statusFail($err_coll_name_invalid));

$coll = $db->$coll_name;

if ($coll->count() == 0) die(statusFail($err_db_not_seeded));



if (isset($_GET['mode'])) $mode = $_GET['mode']; else $mode = $default_mode;
if (isset($_GET['string'])) $left = $_GET['string'];
if (isset($_GET['file'])) $filePath = $_GET['file'];
if (isset($_GET['maxRecur'])) $maxRecur = $_GET['maxRecur']; else $maxRecur = 100;

if ($mode != 'string' && $mode != 'file') die(statusFail($err_inv_arg_mode));
if ($maxRecur < 1) {
	die(statusFail(sprintf($err_inv_arg_mrec, escapeshellarg($maxRecur))));
}

if ($mode == 'string') {
	if (isset($string) && (strlen($string) < $ion_bootstrap_min_length || strlen($string) > $ion_bootstrap_max_length)) {
		die(statusFail(sprintf($err_inv_arg_str, strlen($string))));
	}
	$left = $string;
}
else {
	if (isset($filePath) && (strlen($filePath) < $sec_fp_min_length || strlen($filePath) > $sec_fp_max_length)) {
		die(statusFail($err_inv_arg_fp));
	}
	$file = file($filePath) or die(statusFail(sprintf($err_file_no_acc, escapeshellarg($filePath))));
	
	if (!isset($file[1])) die(statusFail($err_not_ion_file));
	$left = $file[1];
	if (strlen($left) > $ion_bootstrap_max_length) statusFail($err_not_ion_file);
}

$search = array('md5' => md5($left));

if ($coll->count($search) > 0) {
	$document = $coll->findOne($search);
}
else {
	$docs = $coll->find();
	$counter = 0;
	foreach ($docs as $item) {
		if ($counter >= $maxRecur) die(statusTimeout($err_match_timeout));
		
		$metric = new SmithWatermanGotoh();
		$sim = $metric->compare($left, base64_decode($item['text']));
		$counter++;
		
		if ($sim > $ion_sim_threshold) {
			$coll->insertOne(array('md5' => md5($left), 'text' => base64_encode($left), 'sim' => $sim, 'created' => time()));
			break;
		}
		else {
			die(statusFail($msg_no_template));
		}
	}
}

if (isset($file[2])) {
	array_splice($file, 0, 2);
	foreach ($file as $line) {
		if (strlen($line) < 4) continue;
		
		if (preg_match("/^[a-zA-Z0-9+\/]+={0,2}$/", $line) != 1) {
			die(statusFail($msg_regex_bb_miss . $line));
		}
	}
	die(statusSuccess($msg_valid_ioncube));
}
die(statusSuccess($msg_abrupt_f_end));
?>