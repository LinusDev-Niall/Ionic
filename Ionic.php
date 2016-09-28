<?php header('Content-Type: application/json');

/*
Ionic Scanner - 
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

error_reporting(E_ALL);
date_default_timezone_set("UTC");

require 'vendor/autoload.php';

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

$conn = new MongoDB\Client();
$coll = $conn->dev_db_php->dev_db_php;

if (isset($_GET['mode'])) $mode = $_GET['mode'];
else $mode = 'string';
if (isset($_GET['string'])) $left = $_GET['string'];
if (isset($_GET['file'])) $filePath = $_GET['file'];

if (isset($_GET['maxRecur'])) $maxRecur = $_GET['maxRecur'];
else $maxRecur = 100;

if ($mode != 'string' && $mode != 'file') die(statusFail("No mode specified."));
if ($maxRecur < 1) {
	die(statusFail(sprintf("Invalid argument, maxRecur must be > 0 but was %s", escapeshellarg($maxRecur))));
}

if ($mode == 'string') {
	if (isset($string) && (strlen($string) < 20 || strlen($string) > 2000)) {
		die(statusFail(sprintf("Input string must be between 20 and 2000 carachters, was %s.", strlen($string))));
	}
	$left = $string;
}
else {
	if (isset($filePath) && (strlen($filePath) < 1 || strlen($filePath) > 250)) die(statusFail("Invalid path specified."));
	
	$file = file($filePath) or die(statusFail(sprintf("Could not access the specified file: %s", 
                escapeshellarg($filePath))));
	
	if (!isset($file[1])) die(statusFail("File does not appear to be an IonCube file."));
	$left = $file[1];
	if (strlen($left) > 2000) statusFail("File does not appear to be an IonCube file.");
}

$search = array('md5' => md5($left));

if ($coll->count($search) > 0) {
	$document = $coll->findOne($search);
}
else {
	$docs = $coll->find();
	$counter = 0;
	foreach ($docs as $item) {
		if ($counter >= $maxRecur) die(statusTimeout("Timeout was reached while processing database entries."));
		
		$metric = new SmithWatermanGotoh();
		$sim = $metric->compare($left, base64_decode($item['text']));
		$counter++;
		
		if ($sim > 0.8) {
			$coll->insertOne(array('md5' => md5($left), 'text' => base64_encode($left), 'sim' => $sim, 'created' => time()));
			break;
		}
		else {
			die(statusFail("Unable to find a matching template."));
		}
	}
}

if (isset($file[2])) {
	array_splice($file, 0, 2);
	foreach ($file as $line) {
		if (strlen($line) < 4) continue;
		
		if (preg_match("/^[a-zA-Z0-9+\/]+={0,2}$/", $line) != 1) {
			die(statusFail("Regex match missed on line: " . $line));
		}
	}
	die(statusSuccess());
}
die(statusSuccess());
?>