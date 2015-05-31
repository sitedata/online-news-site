<?php
/**
 * Remote site menu maintenance
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
session_start();
require 'z/system/configuration.php';
require $includesPath . '/authorization.php';
require $includesPath . '/common.php';
require $includesPath . '/password_compat/password.php';
//
// User-group authorization
//
if ($_SESSION['username'] != 'admin') {
    include 'logout.php';
    exit;
}
//
// Variables
//
$adminPassPost = inlinePost('adminPass');
$archiveAccessPost = inlinePost('archiveAccess');
$classifiedsEdit = null;
$classifiedsPost = inlinePost('classifieds');
$edit = inlinePost('edit');
$hashAdmin = hash('sha512', $adminPassPost . $_SESSION['username']);
$idMenuEdit = null;
$idMenuPost = inlinePost('idMenu');
$menuContentEdit = null;
if (isset($_POST['menuContent'])) {
    $menuContentPost = stripslashes($_POST['menuContent']);
    $menuContentPost = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $menuContentPost);
    $menuContentPost = str_replace("\r\n", "\n", $menuContentPost);
    $menuContentPost = str_replace("\r", "\n", $menuContentPost);
    $menuContentPost = str_replace("\t", '    ', $menuContentPost);
}
$menuNameEdit = null;
$menuNamePost = inlinePost('menuName');
$menuSortOrderEdit = null;
$menuSortOrderPost = inlinePost('menuSortOrder');
$message = null;
//
// Variables for predefined menu items
//
$dbh = new PDO($dbSettings);
$stmt = $dbh->prepare('SELECT access FROM archiveAccess WHERE idAccess=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array(1));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $archiveEdit = $row['access'];
} else {
    $archiveEdit = null;
}
$dbh = new PDO($dbMenu);
$stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE menuName=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array('Classified ads'));
$row = $stmt->fetch();
$dbh = null;
if ($row) {
    $classifiedsEdit = 1;
}
//
// Test admin password authentication
//
if (isset($_POST['adminPass']) and ($_POST['adminPass'] == null or $_POST['adminPass'] == '')) {
    $message = 'The admin password is required for all user maintenance.';
}
$dbh = new PDO($dbEditors);
$stmt = $dbh->prepare('SELECT pass FROM users WHERE user=?');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$stmt->execute(array($_SESSION['username']));
$row = $stmt->fetch();
$dbh = null;
if (password_verify($adminPassPost, $row['pass'])) {
    if (password_needs_rehash($row['pass'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($passPost, PASSWORD_DEFAULT);
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('UPDATE users SET pass=? WHERE idUser=?');
        $stmt->execute(array($newHash, $row['idUser']));
        $dbh = null;
    }
    //
    // Button: Update (predefined menu items)
    //
    if (isset($_POST['updatePredefined'])) {
        //
        // Enable archive access
        //
        if ($archiveAccessPost == strval('on')) {
            $access = 1;
            $archiveEdit = 1;
        } else {
            $access = null;
            $archiveEdit = null;
        }
        $dbh = new PDO($dbSettings);
        $stmt = $dbh->query('DELETE FROM archiveAccess');
        $stmt = $dbh->prepare('INSERT INTO archiveAccess (access) VALUES (?)');
        $stmt->execute(array($access));
        $dbh = null;
        include $includesPath . '/syncSettings.php';
        //
        // Enable classifieds
        //
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName = ?');
        $stmt->execute(array('Classified ads'));
        $dbh = null;
        if ($classifiedsPost == strval('on')) {
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('INSERT INTO menu (menuName, menuSortOrder, menuPath) VALUES (?, ?, ?)');
            $stmt->execute(array('Classified ads', 1, 'classified-ads'));
            $dbh = null;
            $classifiedsEdit = 1;
        } else {
            $classifiedsEdit = null;
        }
        //
        // Update remote sites
        //
        include $includesPath . '/syncMenu.php';
    }
    //
    // Button: Add / update
    //
    if (isset($_POST['addUpdate'])) {
        //
        // Determine insert or update, check for unique user name
        //
        if (empty($_POST['existing']) and isset($menuNamePost)) {
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('SELECT menuName FROM menu WHERE menuName=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($menuNamePost));
            $row = $stmt->fetch();
            $dbh = null;
            if ($row) {
                $message = 'The menu name is already in use.';
                header('Location: ' . $uri . 'menu.php');
                exit;
            } else {
                $dbh = new PDO($dbMenu);
                $stmt = $dbh->query('DELETE FROM menu WHERE menuName IS NULL');
                $stmt = $dbh->prepare('INSERT INTO menu (menuName) VALUES (?)');
                $stmt->execute(array(null));
                $idMenu = $dbh->lastInsertId();
                $dbh = null;
            }
        } elseif (isset($menuNamePost)) {
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('SELECT idMenu FROM menu WHERE idMenu=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($idMenuPost));
            $row = $stmt->fetch();
            $dbh = null;
            extract($row);
        }
        //
        // Apply update
        //
        if ($_POST['menuName'] != null) {
            //
            // Establish the change in sort order, if any
            //
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('SELECT menuSortOrder FROM menu WHERE idMenu=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($idMenu));
            $row = $stmt->fetch();
            $dbh = null;
            if ($menuSortOrderPost > $row['menuSortOrder']) {
                $menuSortOrderPost++;
            }
            //
            $menuPath = strtolower($menuNamePost);
            $menuPath = str_replace(' ', '-', $menuPath);
            $menuPath = str_replace("'", '', $menuPath);
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('UPDATE menu SET menuName=?, menuSortOrder=?, sortPriority=?, menuPath=?, menuContent=? WHERE idMenu=?');
            $stmt->execute(array($menuNamePost, $menuSortOrderPost, 1, $menuPath, $menuContentPost, $idMenu));
            $dbh = null;
            //
            // Update remote sites
            //
            $request = null;
            $request['task'] = 'menuInsert';
            $request['idMenu'] = $idMenu;
            $request['menuName'] = $menuNamePost;
            $request['menuSortOrder'] = $menuSortOrderPost;
            $request['menuPath'] = $menuPath;
            $request['menuContent'] = $menuContentPost;
            $dbhRemote = new PDO($dbRemote);
            $stmt = $dbhRemote->query('SELECT remote FROM remotes');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $dbhRemote = null;
            foreach ($stmt as $row) {
                $response = soa($row['remote'] . 'z/', $request);
            }
        } else {
            $message = 'No menu name was input.';
        }
    }
    //
    // Button: Delete
    //
    if (isset($_POST['delete'])) {
        if ($_POST['menuName'] != null) {
            $dbh = new PDO($dbMenu);
            $stmt = $dbh->prepare('SELECT menuName FROM menu WHERE menuName=?');
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute(array($menuNamePost));
            $dbh = null;
            $row = $stmt->fetch();
            if ($row) {
                $dbh = new PDO($dbMenu);
                $stmt = $dbh->prepare('DELETE FROM menu WHERE menuName=?');
                $stmt->execute(array($menuNamePost));
                $dbh = null;
                //
                // Update remote sites
                //
                $request = null;
                $request['task'] = 'menuDelete';
                $request['idMenu'] = $idMenu;
                $dbhRemote = new PDO($dbRemote);
                $stmt = $dbhRemote->query('SELECT remote FROM remotes');
                $stmt->setFetchMode(PDO::FETCH_ASSOC);
                $dbhRemote = null;
                foreach ($stmt as $row) {
                    $response = soa($row['remote'] . 'z/', $request);
                }
            } else {
                $message = 'The menu name was not found.';
            }
        } else {
            $message = 'No menu name was input.';
        }
    }
    //
    // Update menu sort order
    //
    if (isset($_POST['addUpdate']) or isset($_POST['delete'])) {
        $count = null;
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->query('SELECT idMenu, menuSortOrder FROM menu ORDER BY menuSortOrder, sortPriority');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            extract($row);
            $count++;
            $stmt = $dbh->prepare('UPDATE menu SET menuSortOrder=? WHERE idMenu=?');
            $stmt->execute(array($count, $idMenu));
        }
        $stmt = $dbh->prepare('UPDATE menu SET sortPriority=?');
        $stmt->execute(array(2));
        $dbh = null;
        //
        // Update the remote databases
        //
        $sortOrder = null;
        $dbh = new PDO($dbMenu);
        $stmt = $dbh->query('SELECT idMenu, menuSortOrder FROM menu ORDER BY idMenu');
        $stmt->setFetchMode(PDO::FETCH_NUM);
        foreach ($stmt as $row) {
            $sortOrder[] = $row;
        }
        $dbh = null;
        $sortOrder = json_encode($sortOrder);
        $request = null;
        $request['task'] = 'menuOrder';
        $request['sortOrder'] = $sortOrder;
        $dbhRemote = new PDO($dbRemote);
        $stmt = $dbhRemote->query('SELECT remote FROM remotes');
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        foreach ($stmt as $row) {
            $response = soa($row['remote'] . 'z/', $request);
        }
        $dbhRemote = null;
        include $includesPath . '/syncMenu.php';
    }
} elseif (isset($_POST['addUpdate']) or isset($_POST['delete'])) {
    $message = 'The admin password is invalid.';
}
//
// Button: Edit
//
if (isset($_POST['edit'])) {
    $dbh = new PDO($dbMenu);
    $stmt = $dbh->prepare('SELECT idMenu, menuName, menuSortOrder, menuContent FROM menu WHERE idMenu=?');
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $stmt->execute(array($idMenuPost));
    $row = $stmt->fetch();
    $dbh = null;
    if ($row) {
        extract($row);
        $idMenuEdit = $idMenu;
        $menuContentEdit = $menuContent;
        $menuNameEdit = $menuName;
        $menuSortOrderEdit = $menuSortOrder;
    }
}
//
// HTML
//
require $includesPath . '/header1.inc';
echo "  <title>Menu maintenance</title>\n";
echo '  <script type="text/javascript" src="z/wait.js"></script>' . "\n";
require $includesPath . '/header2.inc';
require $includesPath . '/body.inc';
?>

  <h4 class="m"><a class="m" href="usersEditors.php">&nbsp;Editing users&nbsp;</a><a class="m" href="usersSubscribers.php">&nbsp;Patron mgt users&nbsp;</a></h4>

  <h4 class="m"><a class="m" href="usersAdvertising.php">&nbsp;Advertising users&nbsp;</a><a class="m" href="usersClassified.php">&nbsp;Classified users&nbsp;</a></h4>

  <h4 class="m"><a class="s" href="menu.php">&nbsp;Menu&nbsp;</a><a class="m" href="settings.php">&nbsp;Settings&nbsp;</a><a class="m" href="classifiedSections.php">&nbsp;Classifieds&nbsp;</a></h4>
<?php echoIfMessage($message); ?>

  <h1 id="waiting">Please wait.</h1>

  <h1><span class="h">Menu</span></h1>

<?php
$rowcount = null;
$dbh = new PDO($dbMenu);
$stmt = $dbh->query('SELECT idMenu, menuName, menuSortOrder, menuContent FROM menu ORDER BY menuSortOrder');
$stmt->setFetchMode(PDO::FETCH_ASSOC);
foreach ($stmt as $row) {
    extract($row);
    $rowcount++;
    echo '  <form action="' . $uri . 'menu.php" method="post">' . "\n";
    echo '    <p><span class="p">' . html($menuName) . " - order: $rowcount<br />\n";
    echo '    <input name="idMenu" type="hidden" value="' . $idMenu . '" /><input type="submit" value="Edit" name="edit" class="button" /></span></p>' . "\n";
    echo "  </form>\n\n";
}
$dbh = null;
?>
  <h1>Menu maintenance</h1>

  <form class="wait" action="<?php echo $uri; ?>menu.php" method="post">
    <p>The admin password is required for all menu maintenance.</p>

    <p><label for="adminPass">Password</label><br />
    <input id="adminPass" name="adminPass" type="password" class="h" autofocus required /></p>

    <h1>Predefined menu items</h1>

    <p><label>
      <input type="checkbox" name="archiveAccess"<?php echoIfYes($archiveEdit); ?> /> Enable subscribers to search archives
    </label></p>

    <p><label>
      <input type="checkbox" name="classifieds"<?php echoIfYes($classifiedsEdit); ?> /> Enable classified ads
    </label></p>

    <p><input type="submit" value="Update" name="updatePredefined" class="button" /></p>

    <h1>Add, update and delete menu items</h1>

    <p>The name and sort order fields required to add or update a menu item. Menu names must be unique.</p>

    <p><label for="menuName">Name / Page title</label><br />
    <input id="menuName" name="menuName" type="text" class="h"<?php echoIfValue($menuNameEdit); ?> /></p>

    <p><label for="menuSortOrder">Sort order</label><br />
    <input id="menuSortOrder" name="menuSortOrder" type="text" class="h"<?php echoIfValue($menuSortOrderEdit); ?> /><input name="idMenu" type="hidden" <?php echoIfValue($idMenuEdit); ?> /></p>

    <p><label for="menuContent">Page content *</label><br />
    <textarea id="menuContent" name="menuContent" class="h" rows="8"><?php echoIfText($menuContentEdit); ?></textarea></p>

    <p class="b"><input type="submit" value="Add / update" name="addUpdate" class="button" /><br />
    <input type="submit" value="Delete" name="delete" class="button" /><input type="hidden" name="existing"<?php echoIfValue($edit); ?> /></p>
  </form>

  <p>* Page content is entered in the markdown syntax, a common format within web-based content management systems. By abbreviating some HTML, markdown makes it easier to read and proof the input content. For markup not covered by markdown syntax, like tables and images, use regular HTML. The text below this point can be cut and pasted into the page content input field above to see the result on the subscriber site.</p>

  <p>Headers use one to six hash characters at the start of the line, corresponding to header levels on through six. For example:</p>

  <p># This is an H1</p>

  <p>## This is an H2</p>

  <p>### This is an H3</p>

  <p>For links, the anchor text is delimited by [square brackets]. To create an inline link, use a set of regular parentheses immediately after the link text’s closing square bracket. Inside the parentheses, put the URL where you want the link to point.</p>

  <p>[This link](http://example.net/) is for a web page.</p>

  <p>This link, [John Doe](mailto:john.doe@example.net), is for an e-mail address.</p>

  <p>Like tables, images must be entered with regular HTML, for example:</p>

  <p>&lt;img src="images/sample.png" alt="" width="310" height="74" /&gt;</p>

  <p>Markdown supports unordered (bulleted) and ordered (numbered) lists. Unordered lists use a dash followed by a space:</p>

  <p>- Red<br />
  - Green<br />
  - Blue</p>

  <p>Ordered lists use numbers followed by periods:</p>

  <p>1. Bird<br />
  2. McHale<br />
  3. Parish</p>

  <p>It’s important to note it’s possible to trigger an ordered list by accident, by writing something like this:</p>

  <p>1986. What a great season.</p>

  <p>To avoid the result, escape the period with two preceding backslashes:</p>

  <p>1986\\. What a great season.</p>

  <p>Markdown provides backslash escapes for the characters below, which all can have an interpreted meaning within markdown depending upon where they occur. See the full <a href="http://daringfireball.net/projects/markdown/syntax/" target="_blank">markdown documentation</a> for more information. When one of the characters is interpreted but is supposed to be as typed, then add two backslashes before the character.</p>

  <p>\ backslash<br />
  ` backtick<br />
  * asterisk<br />
  _ underscore<br />
  {} curly braces<br />
  [] square brackets<br />
  () parentheses<br />
  &gt; greater than<br />
  # hash mark<br />
  + plus sign<br />
  - minus sign (hyphen)<br />
  . dot<br />
  ! exclamation mark</p>
</body>
</html>
