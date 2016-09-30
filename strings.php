<?php if(!defined('IonicLoaded')) { header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404); }

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

$err_inv_arg_mrec = "IC011 - Invalid argument, maxRecur must be > 0 but was %s";
$err_inv_arg_mode = "IC012 - Invalid argument, no mode specified.";
$err_inv_arg_str = "IC013 - Invalid argument, input string must be between 20 and 2000 carachters, was %s.";
$err_inv_arg_fp = "IC014 - Invalid argument, invalid path specified.";
$err_file_no_acc = "IC021 - Could not access the specified file: %s";
$err_not_ion_file = "IC031 - File does not appear to be an IonCube file.";
$err_match_timeout = "IC041 - Timeout was reached while processing database entries.";
$err_db_not_seeded = "IC051 - The configured database is not seeded.";
$err_db_name_invalid = "IC052 - The configured database does not exist on the server.";
$err_coll_name_invalid = "IC053 - The configured collection does not exist in the database.";


$msg_no_template = "IC951 - Unable to find a matching template.";
$msg_regex_bb_miss = "IC961 - Regex match missed on line: ";
$msg_abrupt_f_end = "IC971 - End of the file was reached sooner than expected.";
$msg_valid_ioncube = "IC001 - IonCube file validated.";


?>