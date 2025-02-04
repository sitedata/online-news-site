<?php
/**
 * Set the maximum number of ads to display simultaneously
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
if (empty($row['userType']) or $row['userType'] !== '3') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$maxAdsEdit = null;
$maxAdsPost = inlinePost('maxAds');
$message = null;
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
// Button: Add / update
//
//
// Button: Set maximum
//
if (isset($_POST['setMaximum']) and isset($maxAdsPost)) {
    $dbh = new PDO($dbAdvertising);
    $stmt = $dbh->query('DELETE FROM maxAd');
    $stmt = $dbh->prepare('INSERT INTO maxAd (maxAds) VALUES (?)');
    $stmt->execute([$maxAdsPost]);
    $dbh = null;
    //
    // Update remote sites
    //
    $request = null;
    $request['task'] = 'adMax';
    $request['maxAds'] = $maxAdsPost;
    foreach ($remotes as $remote) {
        $response = soa($remote . 'z/', $request);
    }
}
//
// Set maxAds
//
$dbh = new PDO($dbAdvertising);
$stmt = $dbh->prepare('SELECT maxAds FROM maxAd WHERE idMaxAds=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute([1]);
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $maxAdsEdit = $row['maxAds'];
}
//
// HTML
//
require $includesPath . '/header1.inc';
?>
  <title>Advertising maintenance</title>
  <link rel="icon" type="image/png" href="images/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" type="text/css" href="z/base.css" />
  <link rel="stylesheet" type="text/css" media="(max-width: 768px)" href="z/small.css" />
  <link rel="stylesheet" type="text/css" media="(min-width: 768px)" href="z/large.css" />
  <script src="z/wait.js"></script>
</head>

<?php require $includesPath . '/body.inc';?>

  <h4 class="m"><a class="m" href="advertisingPublished.php">&nbsp;Published ads&nbsp;</a><a class="m" href="advertisingEdit.php">&nbsp;Edit ads&nbsp;</a><a class="s" href="advertisingMax.php">&nbsp;Ads max&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1>Set the maximum number of ads to display simultaneously</h1>
  <form class="wait" action="<?php echo $uri; ?>advertisingMax.php" method="post">
    <p><label for="maxAds">Maximum number of ads</label><br />
    <input type="number" id="maxAds" name="maxAds"<?php echoIfValue($maxAdsEdit); ?> /></p>

    <p><input type="submit" class="button" name="setMaximum" value="Set maximum" /></p>
  </form>
</body>
</html>
