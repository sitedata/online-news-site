<?php
/**
 * Updates the remote settings databases
 *
 * PHP version 8
 *
 * @category  Publishing
 * @package   Online_News_Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2021 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2021 5 17
 * @link      https://hardcoverwebdesign.com/
 * @link      https://onlinenewssite.com/
 * @link      https://github.com/hardcover/
 */
//
// Variables
//
$remotes = [];
$dbh = new PDO($dbRemote);
$stmt = $dbh->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    $remotes[] = $row['remote'];
}
$dbh = null;
//
$request = null;
$request['task'] = 'settingsUpdate';
//
// Update archive access
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idAccess, access FROM archiveAccess');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['archiveAccess'] = json_encode($row);
}
//
// Update calendar access
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idCalendarAccess, access FROM calendarAccess');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['calendarAccess'] = json_encode($row);
}
//
// Update classified access
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idClassifiedAccess, access FROM classifiedAccess');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['classifiedAccess'] = json_encode($row);
}
//
// Update contact form access
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idContactAccess, access FROM contactAccess');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['contactAccess'] = json_encode($row);
}
//
// Update email alert for classifieds
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->query('SELECT idClassified, emailClassified FROM alertClassified');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['alertClassified'] = json_encode($row);
}
//
// Update newspaper name
//
$stmt = $dbh->query('SELECT idName, name, description FROM names');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['name'] = json_encode($row);
}
//
// Update registration information
//
$stmt = $dbh->query('SELECT idRegistration, information FROM registration');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['information'] = json_encode($row);
}
//
// Update contact form information
//
$stmt = $dbh->query('SELECT idForm, infoForms FROM forms');
$stmt->setFetchMode(PDO::FETCH_NUM);
$row = $stmt->fetch();
if ($row) {
    $request['infoForms'] = json_encode($row);
}
//
// Update newpaper sections
//
$sortOrder = null;
$stmt = $dbh->query('SELECT idSection, section, sortOrderSection FROM sections ORDER BY idSection');
$stmt->setFetchMode(PDO::FETCH_NUM);
foreach ($stmt as $row) {
    $sortOrder[] = $row;
}
$dbh = null;
$sortOrder = json_encode($sortOrder);
$request['sortOrder'] = $sortOrder;
//
// Loop through each remote location
//
foreach ($remotes as $remote) {
    $response = soa($remote . 'z/', $request);
}
?>