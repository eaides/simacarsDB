<?php
/**
 * Created by PhpStorm.
 * User: ernesto
 * Date: 28/07/19
 * Time: 09:37
 */

require_once('./sync_XPLE_dbs.php');
include_once('./dbConnection.php');

if (!isset($dbHost)) $dbHost = '';
if (!isset($dbName)) $dbName = '';
if (!isset($dbUser)) $dbUser = '';
if (!isset($dbHost)) $dbHost = '';
$dbHost = trim($dbHost);
$dbName = trim($dbName);
$dbUser = trim($dbUser);
$dbHost = trim($dbHost);

$syncDB = new sync_XPLE_dbs($dbHost, $dbName, $dbUser, $dbPass);
$syncDB->doSync();