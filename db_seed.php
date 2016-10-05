<?php
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

require 'vendor/autoload.php';
require 'config.php';
require 'strings.php';

if (!$db_can_seed) die($err_db_seed_disabled);

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
} else {
    $conn = new MongoDB\Client($mongo_server);
}

$found = FALSE;

foreach ($conn->listDatabases() as $db_info) {
    if ($db_info->getName() == $db_name) {
        $found = TRUE;
        break;
    }
}
if (!$found) die($err_db_name_invalid);

$db = $conn->$db_name;
$found = FALSE;

foreach ($db->listCollections() as $coll_info) {
    if ($coll_info->getName() == $coll_name) {
        $found = TRUE;
        break;
    }
}
if (!$found) die($err_coll_name_invalid);

$coll = $db->$coll_name;


$coll->insertOne(
    array('md5' => md5("58d22244231a5793abc36e36fcd5456a"),
        'text' => "aWYoIWV4dGVuc2lvbl9sb2FkZWQoJ2lvbkN1YmUgTG9hZGVyJykpeyRfX29jPXN0cnRvbG93ZXIoc3Vic3RyKHBocF91bmFtZS".
            "gpLDAsMykpOyRfX2xuPSdpb25jdWJlX2xvYWRlcl8nLiRfX29jLidfJy5zdWJzdHIocGhwdmVyc2lvbigpLDAsMykuKCgkX19vYz09J3".
            "dpbicpPycuZGxsJzonLnNvJyk7aWYoZnVuY3Rpb25fZXhpc3RzKCdkbCcpKXtAZGwoJF9fbG4pO31pZihmdW5jdGlvbl9leGlzdHMoJ1".
            "9pbF9leGVjJykpe3JldHVybiBfaWxfZXhlYygpO30kX19sbj0nL2lvbmN1YmUvJy4kX19sbjskX19vaWQ9JF9faWQ9cmVhbHBhdGgoaW".
            "5pX2dldCgnZXh0ZW5zaW9uX2RpcicpKTskX19oZXJlPWRpcm5hbWUoX19GSUxFX18pO2lmKHN0cmxlbigkX19pZCk",
        'seed' => TRUE, 'created' => time())
);





