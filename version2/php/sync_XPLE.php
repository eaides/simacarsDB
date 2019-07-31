<?php
/**
 * Created by PhpStorm.
 * User: ernesto
 * Date: 28/07/19
 * Time: 09:37
 */

require_once('./sync_XPLE_class.php');
require_once('./sync_XPLE_dbConnection.php');
include_once('./sync_XPLE_airports.php');

if (!isset($disable)) $disable = true;

if ($disable)
{
    header("HTTP/1.0 404 Not Found");
    die('Page not found');
}

if (!isset($dbHost)) $dbHost = '';
if (!isset($dbName)) $dbName = '';
if (!isset($dbUser)) $dbUser = '';
if (!isset($dbHost)) $dbHost = '';
if (!isset($dbCreateIndex)) $dbCreateIndex = false;
$dbHost = trim($dbHost);
$dbName = trim($dbName);
$dbUser = trim($dbUser);
$dbHost = trim($dbHost);
$dbCreateIndex = (bool)$dbCreateIndex;

if (!isset($airports)) $airports = array();

/** @var sync_XPLE_class $syncDB */
$syncDB = new sync_XPLE_class($dbHost, $dbName, $dbUser, $dbPass, $dbCreateIndex, $airports);

// $syncDB->setJustSpain(true);
$rc = $syncDB->doSync();

$syncDB->echoo('');
if ($rc)
{
    $syncDB->echoo('Synchronization OK');
}
else
{
    $syncDB->echoo('No synchronization done');
}
$syncDB->echoo('');
$syncDB->echoo('');
