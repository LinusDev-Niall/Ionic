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

$mongo_server = 'mongodb://localhost/';
$db_name = 'dev_db_php';
$coll_name = 'dev_db_php';
$default_mode = 'file';


$use_ssl = FALSE;
$ssl_dir = '';
$ssl_file = '';
$ssl_local_cert = '/path/to/cert.pem';
$ssl_cert_passphrase = '';
$ssl_allow_self_signed = FALSE;
$ssl_verify_peer = TRUE;
$ssl_verify_peer_name = TRUE;
$ssl_verify_expiry = TRUE;


$sec_fp_min_length = 1;
$sec_fp_max_length = 250;


$ion_bootstrap_min_length = 200;
$ion_bootstrap_max_length = 2000;
$ion_match_max_recur = 100;
$ion_sim_threshold = 0.8;

?>