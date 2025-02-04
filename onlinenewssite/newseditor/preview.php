<?php
/**
 * A view of the published articles for an input date
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
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
$datePost = inlinePost('date');
//
if (is_null($datePost)) {
    $datePost = $today;
}
$database = $dbPublished;
$database2 = $dbPublished2;
$editorView = null;
$imagePath = 'imagep.php';
$imagePath2 = 'imagep2.php';
$links = null;
$menu = "\n" . '  <h4 class="m"><a class="m" href="edit.php">&nbsp;Edit&nbsp;</a><a class="m" href="published.php">&nbsp;Published&nbsp;</a><a class="s" href="preview.php">&nbsp;Preview&nbsp;</a><a class="m" href="archive.php">&nbsp;Archives&nbsp;</a></h4>' . "\n\n";
$message = null;
$publishedIndexAdminLinks = null;
$title = 'Preview';
$use = 'preview';
//
// HTML
//
require $includesPath . '/header1.inc';
echo '  <title>' . $title . "</title>\n";
?>
  <link rel="icon" type="image/png" href="images/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.theme.css" />
  <link rel="stylesheet" type="text/css" href="z/jquery-ui.structure.css" />
  <link rel="stylesheet" type="text/css" href="z/base.css" />
  <link rel="stylesheet" type="text/css" media="(max-width: 768px)" href="z/small.css" />
  <link rel="stylesheet" type="text/css" media="(min-width: 768px)" href="z/large.css" />
  <script src="z/jquery.min.js"></script>
  <script src="z/jquery-ui.min.js"></script>
  <script src="z/datepicker.js"></script>
</head>

<?php
require $includesPath . '/body.inc';
echo $menu;
echo '  <h1>' . $title . "</h1>\n\n";
echoIfMessage($message);
echo '  <form method="post" action="' . $uri . 'preview.php">' . "\n";
echo '    <p><label for="date">Publication date</label><br />' . "\n";
echo '    <input id="date" name="date" type="text" class="datepicker h" value="' . $datePost . '" /></p>' . "\n\n";
echo '    <p><input type="submit" class="button" value="Select date" /></p>' . "\n";
echo "  </form>\n\n";
require $includesPath . '/displayIndex.inc';
?>
</body>
</html>