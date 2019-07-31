<?php
/**
 * Created by PhpStorm.
 * User: ernesto
 * Date: 28/07/19
 * Time: 09:45
 */

/**
 * @brief
 *      The $disable variable must be false ONLY when you want to run the script
 *      Do it true when finish to update the databases, to avoid unintentional runs
 */
/** @var bool $disable */
$disable = false;

/**
 * @brief
 *      Replace this values with the correct ones before upload the file to the VAM site
 */
$dbHost = 'localhost';
$dbName = 'vam';
$dbUser = 'homestead';
$dbPass = 'secret';
$dbCreateIndex = true;
