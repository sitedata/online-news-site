<?php
/**
 * Downloads the latest remote classifieds
 *
 * PHP version 5
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2013-2015 Hardcover LLC
 * @license   http://hardcoverwebdesign.com/license  MIT License
 *.@license   http://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version   GIT: 2015-05-31
 * @link      http://hardcoverwebdesign.com/
 * @link      http://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
//
// Loop through each remote location
//
$dbhRemote = new PDO($dbRemote);
$stmt = $dbhRemote->query('SELECT remote FROM remotes');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    //
    // Get the IDs of the new ads
    //
    $classifieds = array();
    $response = null;
    $request['task'] = 'classifiedsSyncNew';
    $response = soa($remote . 'z/', $request);
    if ($response['result'] == 'success') {
        $classifieds = json_decode($response['remoteClassifieds'], true);
        if ($classifieds == 'null' or $classifieds == null) {
            $classifieds = array();
        }
    }
    //
    // Download new classifieds from remote sites
    //
    foreach ($classifieds as $classified) {
        $response = null;
        $request = null;
        $request['task'] = 'classifiedsNewDownload';
        $request['idAd'] = $classified;
        $response = soa($remote . 'z/', $request);
        if ($response['result'] == 'success' and isset($response['email'])) {
            extract($response);
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->prepare('INSERT INTO ads (email, title, description, categoryId, photos) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(array($email, $title, $description, $categoryId, $photos));
            $idAdMain = $dbh->lastInsertId();
            $dbh = null;
            //
            // Download the images, one at a time
            //
            $photos = json_decode($photos, true);
            $request = null;
            $request['task'] = 'classifiedsNewDownloadPhoto';
            $request['idAd'] = $classified;
            $i = null;
            foreach ($photos as $photo) {
                $i++;
                if ($photo == 1) {
                    $request['photo'] = $i;
                    $response = soa($remote . 'z/', $request);
                    if ($response['result'] == 'success' and isset($response['photo'])) {
                        $dbh = new PDO($dbClassifieds);
                        $stmt = $dbh->prepare('UPDATE ads SET photo' . $i . '=? WHERE idAd=?');
                        $stmt->execute(array($response['photo'], $idAdMain));
                        $dbh = null;
                    }
                }
            }
            //
            // Delete the ad from the classifiedsNew database
            //
            $request = null;
            $request['task'] = 'classifiedsNewCleanUp';
            $request['idAd'] = $classified;
            $response = soa($remote . 'z/', $request);
        }
        //
        // Determine the missing and extra classifieds
        //
        $request['task'] = 'classifiedsSync';
        $response = soa($remote . 'z/', $request);
        $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
        if ($remoteClassifieds == 'null' or $remoteClassifieds == null) {
            $remoteClassifieds = array();
        }
        $classifieds = array();
        $dbh = new PDO($dbClassifieds);
        $stmt = $dbh->query('SELECT idAd FROM ads');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $classifieds[] = $row['idAd'];
        }
        $dbh = null;
        $missingClassifieds = array_diff($classifieds, $remoteClassifieds);
        $extraClassifieds = array_diff($remoteClassifieds, $classifieds);
        //
        // When extra remote classifieds were found above, check again and delete the extra classifieds
        //
        if (count($extraClassifieds) > 0) {
            $request['task'] = 'classifiedsSync';
            $response = soa($remote . 'z/', $request);
            $remoteClassifieds = json_decode($response['remoteClassifieds'], true);
            if ($remoteClassifieds == 'null' or $remoteClassifieds == null) {
                $remoteClassifieds = array();
            }
            $dbh = new PDO($dbClassifieds);
            $stmt = $dbh->query('SELECT idAd FROM ads');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            foreach ($stmt as $row) {
                $classifieds[] = $row['idAd'];
            }
            $dbh = null;
            $extraClassifieds = array_diff($remoteClassifieds, $classifieds);
            //
            // Delete extra remote classifieds
            //
            foreach ($extraClassifieds as $idAd) {
                $response = null;
                $request['task'] = 'classifiedsDelete';
                $request['idAd'] = $idAd;
                $response = soa($remote . 'z/', $request);
            }
        }
    }
}
$dbhRemote = null;
?>
