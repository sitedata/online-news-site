<?php
/**
 * Search the archives and displays the results
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
$uri = $uriScheme . '://' . $_SERVER["HTTP_HOST"] . rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
require $includesPath . '/common.php';
require $includesPath . '/parsedown-master/Parsedown.php';
//
// Variables
//
$bylinePost = inlinePost('byline');
$endDatePost = inlinePost('endDate');
$headlinePost = inlinePost('headline');
$idArticle = inlinePost('idArticle');
$startDatePost = inlinePost('startDate');
$textPost = inlinePost('text');
//
$archiveSync = '1';
$database = $dbArchive;
$database2 = $dbArchive2;
$editorView = '1';
$imagePath = 'imagea.php';
$imagePath2 = 'imagea2.php';
$menu = "\n" . '  <h4 class="m"><a class="m" href="edit.php">&nbsp;Edit&nbsp;</a><a class="m" href="published.php">&nbsp;Published&nbsp;</a><a class="m" href="preview.php">&nbsp;Preview&nbsp;</a><a class="s" href="archive.php">&nbsp;Archives&nbsp;</a></h4>' . "\n";
$publishedIndexAdminLinks = null;
$title = 'Archives';
$use = 'archive';
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
// Build the SQL query
//
$sql1 = 'SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE ';
$sql2 = null;
if (isset($bylinePost)) {
    $sql1.= 'byline MATCH ?';
    $sql2[] = $bylinePost;
}
if (isset($bylinePost, $headlinePost)) {
    $sql1.= ' INTERSECT SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE';
}
if (isset($headlinePost)) {
    $sql1.= ' headline MATCH ?';
    $sql2[] = $headlinePost;
}
if (isset($bylinePost, $textPost) or isset($headlinePost, $textPost)) {
    $sql1.= ' INTERSECT SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE';
}
if (isset($textPost)) {
    $sql1.= ' text MATCH ?';
    $sql2[] = $textPost;
}
if (isset($bylinePost, $startDatePost)
    or isset($headlinePost, $startDatePost)
    or isset($textPost, $startDatePost)
    or isset($bylinePost, $endDatePost)
    or isset($headlinePost, $endDatePost)
    or isset($textPost, $endDatePost)
) {
    $sql1.= ' INTERSECT SELECT idArticle, publicationDate, byline, headline, summary FROM articles WHERE';
}
if (empty($startDatePost) and isset($endDatePost)) {
    $startDatePost = '1970-01-01';
}
if (empty($endDatePost)) {
    $endDatePost = date("Y-m-d");
}
if (isset($startDatePost, $endDatePost)) {
    $sql1.= ' ? <= publicationDate AND publicationDate <= ?';
    $sql2[] = $startDatePost;
    $sql2[] = $endDatePost;
}
$sql1.= ' ORDER BY publicationDate DESC';
//
// Button: Delete
//
if (isset($_POST['delete'])) {
    $dbNumber = 0;
    while ($dbNumber !== -1) {
        $db = str_replace('archive', 'archive-' . $dbNumber, $dbArchive);
        if ($dbNumber === 0
            or file_exists(str_replace('sqlite:', '', $db))
        ) {
            if ($dbNumber === 0) {
                $database = $dbArchive;
            } else {
                $database = $db;
            }
            $dbNumber++;
        } else {
            $dbNumber = -1;
            $dbh = null;
        }
        if (!empty($database)) {
            $dbh = new PDO($database);
            $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticle]);
            $row = $stmt->fetch();
            if ($row) {
                $stmt = $dbh->prepare('DELETE FROM articles WHERE idArticle=?');
                $stmt->execute([$idArticle]);
                $dbh = null;
                $db = str_replace('archive2', 'archive2-' . $dbNumber, $dbArchive2);
                if ($dbNumber === 0) {
                    $database = $dbArchive2;
                } else {
                    $database = $db;
                }
                $dbh = new PDO($database);
                $stmt = $dbh->prepare('DELETE FROM imageSecondary WHERE idArticle=?');
                $stmt->execute([$idArticle]);
                $dbh = null;
                $dbNumber = -1;
            }
        }
    }
    $request = null;
    $response = null;
    $request['task'] = 'archiveDelete';
    $request['idArticle'] = $idArticle;
    foreach ($remotes as $remote) {
        $response = soa($remote . 'z/', $request);
    }
    include $includesPath . '/syncArticles.php';
}
//
// Button: Return to edit
//
if (isset($_POST['edit'])) {
    $dbNumber = 0;
    while ($dbNumber !== -1) {
        $db = str_replace('archive', 'archive-' . $dbNumber, $dbArchive);
        if ($dbNumber === 0
            or file_exists(str_replace('sqlite:', '', $db))
        ) {
            if ($dbNumber === 0) {
                $database = $dbArchive;
            } else {
                $database = $db;
            }
            $dbNumber++;
        } else {
            $dbNumber = -1;
            $dbh = null;
        }
        if (!empty($database)) {
            $dbh = new PDO($database);
            $stmt = $dbh->prepare('SELECT idArticle FROM articles WHERE idArticle=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute([$idArticle]);
            $row = $stmt->fetch();
            $dbh = null;
            if ($row) {
                $dbNumber = -1;
                $dbFrom = $database;
                include $includesPath . '/moveArticle.php';
                include $includesPath . '/syncArticles.php';
                $request = null;
                $response = null;
                $request['task'] = 'archiveDelete';
                $request['idArticle'] = $idArticle;
                foreach ($remotes as $remote) {
                    $response = soa($remote . 'z/', $request);
                }
                foreach ($remotes as $remote) {
                    $request = null;
                    $response = null;
                    $request['task'] = 'sitemap';
                }
            }
        }
    }
}
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
echo $menu . "\n";
echo '  <h1 id="waiting">Please wait.</h1>' . "\n\n";
echo '  <h1>' . $title . "</h1>\n";
//
// Article view displays an entire single article
//
$html = null;
if (isset($_GET['a'])) {
    $html = "\n" . '  <form action="' . $uri . 'archive.php" method="post" class="wait"></form>' . "\n\n";
    include $includesPath . '/displayArticle.inc';
    //
    // Index view displays a list of all articles with a summary of each article
    //
} else {
    echo '
  <p>Search by any of the following criteria. Enter complete words or the beginning of words followed by an asterisk, for example, either <i>the</i> or <i>th*</i>.</p>

  <form action="' . $uri . 'archive.php" method="post" class="wait">
    <p><label for="headline">Headline contains</label><br />
    <input type="text" class="w" id="headline" name="headline" autofocus /></p>

    <p><label for="startDate">Publication date range search</label><br />
    <input type="text" class="datepicker h" id="startDate" name="startDate" placeholder="Start date" /> <input type="text" class="datepicker h" name="endDate" placeholder="End date" /></p>

    <p><label for="byline">Byline contains</label><br />
    <input type="text" class="w" id="byline" name="byline" /></p>

    <p><label for="text">Article contains</label><br />
    <input type="text" class="w" id="text" name="text" /></p>

    <p><input type="submit" class="button" value="Search" name="search" />
  </form>' . "\n";
    if (isset($bylinePost) or isset($headlinePost) or isset($startDatePost) or isset($textPost)) {
        $html = null;
        $stopTime = 19 + time();
        $dbNumber = 0;
        while ($dbNumber !== -1) {
            $db = str_replace('archive', 'archive-' . $dbNumber, $dbArchive);
            if ($dbNumber === 0
                or file_exists(str_replace('sqlite:', '', $db))
            ) {
                if ($dbNumber === 0) {
                    $dbh = new PDO($dbArchive);
                } else {
                    $dbh = new PDO($db);
                }
                $dbNumber++;
            } else {
                $dbNumber = -1;
                $dbh = null;
            }
            if (!empty($dbh)) {
                $stmt = $dbh->prepare($sql1);
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $stmt->execute($sql2);
                foreach ($stmt as $row) {
                    extract($row);
                    if (time() > $stopTime) {
                        echoIfMessage('The query is taking too long. Please refine the search criteria to narrow the search results.');
                        break;
                    }
                    $html.= "  <hr />\n\n";
                    if (isset($headline)) {
                        $html.= '  <h2><a class="n" href="' . $uri . $use . '.php?a=' . $idArticle . '">' . html($headline) . "</a></h2>\n\n";
                    }
                    $bylineDateTime = isset($publicationDate) ? date("l, F j, Y", strtotime($publicationDate)) : null;
                    if (!empty($bylineDateTime)) {
                        $html.= '  <p>' . html($bylineDateTime);
                    }
                    if (!empty($byline) and isset($bylineDateTime)) {
                        $html.= ', ';
                    }
                    if (!empty($byline)) {
                        $html.= 'by ' . html($byline);
                    }
                    if (!empty($byline) or isset($bylineDateTime)) {
                        $html.= "</p>\n\n";
                    }
                    if (!empty($summary)) {
                        $summary = str_replace('*', '', $summary);
                        $html.= '  <p class="s">' . html($summary) . "</p>\n";
                    }
                    if (isset($editorView) and $editorView === '1') {
                        $html.= "\n" . '  <form action="' . $uri . 'archive.php" method="post" class="wait">' . "\n";
                        $html.= '    <p><input type="hidden" name="idArticle" value="' . $idArticle . '"><input type="submit" class="button" value="Delete" name="delete" /> <input type="submit" class="button" value="Return to edit" name="edit" /></p>' . "\n";
                        $html.= "  </form>\n";
                    }
                }
                $dbh = null;
            }
        }
    }
}
echo $html;
?>
</body>
</html>