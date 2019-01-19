<?php
/**
 * Remote site menu maintenance
 *
 * PHP version 7
 *
 * @category  Publishing
 * @package   Online-News-Site
 * @author    Hardcover LLC <useTheContactForm@hardcoverwebdesign.com>
 * @copyright 2018 Hardcover LLC
 * @license   https://hardcoverwebdesign.com/license  MIT License
 *            https://hardcoverwebdesign.com/gpl-2.0  GNU General Public License, Version 2
 * @version:  2019 01 18
 * @link      https://hardcoverwebdesign.com/
 * @link      https://online-news-site.com/
 * @link      https://github.com/hardcover/
 */
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
//
// User-group authorization
//
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT userType FROM users WHERE idUser=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([$_SESSION['userId']]);
$row = $stmt->fetch();
$dbh = null;
if (empty($row['userType']) or $row['userType'] != 5) {
    include 'logout.php';
    exit;
}
//
// Variables
//
$archiveEdit = null;
$archivePost = inlinePost('archive');
$calendarEdit = null;
$calendarPost = inlinePost('calendar');
$classifiedsEdit = null;
$classifiedsPost = inlinePost('classifieds');
$contactEdit = null;
$contactPost = inlinePost('contact');
$edit = inlinePost('edit');
$message = null;
//
// Archive search edit variable
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT access FROM archiveAccess WHERE idAccess=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $archiveEdit = $row['access'];
} else {
    $archiveEdit = null;
}
//
// Calendar edit variable
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT access FROM calendarAccess WHERE idCalendarAccess=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $calendarEdit = $row['access'];
} else {
    $calendarEdit = null;
}
//
// Classified ads edit variable
//
$dbh = new PDO($dbMenu);
$stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE menuName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(['Classified ads']);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $classifiedsEdit = 1;
} else {
    $classifiedsEdit = null;
}
//
// Contact forms edit variable
//
$dbh = new PDO($dbMenu);
$stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE menuName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(['Contact us']);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $contactEdit = 1;
} else {
    $contactEdit = null;
}
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
// Button: Update
//
if (isset($_POST['updatePredefined'])) {
    //
    // Enable archive access
    //
    if ($archivePost === 'on') {
        $access = 1;
        $archiveEdit = 1;
    } else {
        $access = null;
        $archiveEdit = null;
    }
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE FROM archiveAccess');
    $stmt = $dbh->prepare('INSERT INTO archiveAccess (access) VALUES (?)');
    $stmt->execute([$access]);
    $dbh = null;
    include $includesPath . '/syncSettings.php';
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ? AND menuContent IS NULL');
    $stmt->execute(['Archive search']);
    $dbh = null;
    if ($archivePost === 'on') {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath, menuContent) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Archive search', 1, 'archive-search', null]);
        $dbh = null;
        $archiveEdit = 1;
    } else {
        $archiveEdit = null;
    }
    //
    // Enable calendar access
    //
    if ($calendarPost === 'on') {
        $access = 1;
        $calendarEdit = 1;
    } else {
        $access = null;
        $calendarEdit = null;
    }
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE FROM calendarAccess');
    $stmt = $dbh->prepare('INSERT INTO calendarAccess (access) VALUES (?)');
    $stmt->execute([$calendarEdit]);
    $dbh = null;
    include $includesPath . '/syncSettings.php';
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ? AND menuContent IS NULL');
    $stmt->execute(['Calendar']);
    $dbh = null;
    if ($calendarPost === 'on') {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath, menuContent) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Calendar', 2, 'calendar', null]);
        $dbh = null;
        $calendarEdit = 1;
    } else {
        $calendarEdit = null;
    }
    //
    // Enable classifieds
    //
    if ($classifiedsPost === 'on') {
        $access = 1;
        $classifiedsEdit = 1;
    } else {
        $access = null;
        $classifiedsEdit = null;
    }
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE FROM classifiedAccess');
    $stmt = $dbh->prepare('INSERT INTO classifiedAccess (access) VALUES (?)');
    $stmt->execute([$classifiedsEdit]);
    $dbh = null;
    include $includesPath . '/syncSettings.php';
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ? AND menuContent IS NULL');
    $stmt->execute(['Classified ads']);
    $dbh = null;
    if ($classifiedsPost === 'on') {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath, menuContent) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Classified ads', 3, 'classified-ads', null]);
        $dbh = null;
        $classifiedsEdit = 1;
    } else {
        $classifiedsEdit = null;
    }
    //
    // Enable contact form
    //
    if ($contactPost === 'on') {
        $access = 1;
        $contactEdit = 1;
    } else {
        $access = null;
        $contactEdit = null;
    }
    $dbh = new PDO($dbSettings);
    $stmt = $dbh->query('DELETE FROM contactAccess');
    $stmt = $dbh->prepare('INSERT INTO contactAccess (access) VALUES (?)');
    $stmt->execute([$contactEdit]);
    $dbh = null;
    include $includesPath . '/syncSettings.php';    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ? AND menuContent IS NULL');
    $stmt->execute(['Contact us']);
    $dbh = null;
    if ($contactPost === 'on') {
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath, menuContent) VALUES (?, ?, ?, ?)');
        $stmt->execute(['Contact us', 4, 'contact-us', null]);
        $dbh = null;
        $contactEdit = 1;
    } else {
        $contactEdit = null;
    }
    //
    // Update remote sites
    //
    include $includesPath . '/syncMenu.php';
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo "  <title>Predefined menu items maintenance</title>\n";
echo '  <script src="z/wait.js"></script>' . "\n";
require $includesPath . '/header2.inc';
require $includesPath . '/body.inc';
?>

  <h4 class="m"><a class="m" href="menu.php">&nbsp;Menu&nbsp;</a><a class="m" href="menuCalendar.php">&nbsp;Calendar&nbsp;</a><a class="s" href="menuPredefine.php">&nbsp;Predefined&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1>Predefined menu items maintenance</h1>

  <form class="wait" action="<?php echo $uri; ?>menuPredefine.php" method="post">
    <p><label>
      <input type="checkbox" name="archive"<?php echoIfYes($archiveEdit); ?> /> Enable archive search
    </label></p>

    <p><label>
      <input type="checkbox" name="calendar"<?php echoIfYes($calendarEdit); ?> /> Enable calendar
    </label></p>

    <p><label>
      <input type="checkbox" name="classifieds"<?php echoIfYes($classifiedsEdit); ?> /> Enable classified ads
    </label></p>

    <p><label>
      <input type="checkbox" name="contact"<?php echoIfYes($contactEdit); ?> /> Enable contact form
    </label></p>

    <p><input type="submit" value="Update" name="updatePredefined" class="button" /></p>
  </form>
</body>
</html>
