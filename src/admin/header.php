<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2012 by BOCA Development Team (bocasystem@gmail.com)
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////////////
// Last modified 19/oct/2017 by cassio@ime.usp.br

ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
if(!isset($_POST['noflush']))
    ob_end_flush();
//$loc = $_SESSION['loc'];
//$locr = $_SESSION['locr'];
$loc = $locr = "..";
$runphp = "run.php";
$runeditphp = "runedit.php";

require_once("$locr/globals.php");
require_once("$locr/db.php");

if(!isset($_POST['noflush'])) {
    require_once("$locr/version.php");
    echo "<html><head><title>Admin's Page</title>\n";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
    echo "<link rel=stylesheet href=\"$loc/Css.php\" type=\"text/css\">\n";
}

if(!ValidSession()) {
    InvalidSession("admin/index.php");
    ForceLoad("$loc/index.php");
}
if($_SESSION["usertable"]["usertype"] != "admin") {
    IntrusionNotify("admin/index.php");
    ForceLoad("$loc/index.php");
}

if ((isset($_GET["Submit1"]) && $_GET["Submit1"] == "Transfer") ||
    (isset($_GET["Submit3"]) && $_GET["Submit3"] == "Transfer scores")) {
    echo "<meta http-equiv=\"refresh\" content=\"60\" />";
}

if(!isset($_POST['noflush'])) {
    echo "</head><body id=\"body\"><table border=1 width=\"100%\">\n";
    echo "<tr><td nowrap bgcolor=\"eeee00\" align=center>";
    echo "<img src=\"../images/smallballoontransp.png\" alt=\"\">";
    echo "<font color=\"#000000\">BOCA</font>";
    echo "</td><td bgcolor=\"#eeee00\" width=\"99%\">\n";
    echo "Username: " . $_SESSION["usertable"]["userfullname"] . " (site=".$_SESSION["usertable"]["usersitenumber"].")<br>\n";
    list($clockstr,$clocktype)=siteclock();
    echo "</td><td bgcolor=\"#eeee00\" align=center nowrap>&nbsp;".$clockstr."&nbsp;</td></tr>\n";
    echo "</table>\n";

    $menuItems = [
        ['url' => 'run.php', 'name' => 'Runs'],
        ['url' => 'score.php', 'name' => 'Score'],
        ['url' => 'clar.php', 'name' => 'Clarifications'],
        ['url' => 'user.php', 'name' => 'Users'],
        ['url' => 'problem.php', 'name' => 'Problems'],
        ['url' => 'language.php', 'name' => 'Languages'],
        ['url' => 'answer.php', 'name' => 'Answers'],
        ['url' => 'misc.php', 'name' => 'Misc'],
        ['url' => 'task.php', 'name' => 'Tasks'],
        ['url' => 'site.php', 'name' => 'Site'],
        ['url' => 'contest.php', 'name' => 'Contest'],
        ['url' => 'log.php', 'name' => 'Logs'],
        ['url' => 'report.php', 'name' => 'Reports'],
        ['url' => 'files.php', 'name' => 'Backups'],
        ['url' => 'option.php', 'name' => 'Options'],
        ['url' => "$loc/index.php", 'name' => 'Logout'],
    ];

    $currentURL = $_SERVER['REQUEST_URI'];
    $currentPage = basename($currentURL);

    $highlightClass = 'current-page';

    echo "<table border=0 width=\"100%\" align=center>\n";
    echo " <tr>\n";

    foreach ($menuItems as $item) {
        $menuItemClass = '';
        if (basename($item['url']) == $currentPage) {
            $menuItemClass = $highlightClass;
        }
        echo "  <td align=center><a class=\"menu $menuItemClass\" style=\"font-weight:bold; padding: 2px 3px; border-radius: 5px;\" href={$item['url']}>{$item['name']}</a></td>\n";
    }
    echo " </tr>\n";
    echo "</table>\n";
}
?>
