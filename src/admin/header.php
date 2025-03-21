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
	echo "<link rel=\"icon\" type=\"image/x-icon\" href=\"../images/balloon.svg\">";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
	echo "<script src=\"../vendor/tablefilter/0.7.3/tablefilter.js\"></script>\n";
	echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"../vendor/tablefilter/0.7.3/style/tablefilter.css\">\n";
	echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"../vendor/tablefilter/0.7.3/style/filtersVisibility.css\">\n";
	echo "<link type=\"text/css\" rel=\"stylesheet\" href=\"../vendor/tablefilter/0.7.3/style/colsVisibility.css\">\n";
	echo "<script src=\"../vendor/moment.js/2.30.1/moment.min.js\"></script>\n";
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
	echo "Username: " . $_SESSION["usertable"]["username"] . " (site=".$_SESSION["usertable"]["usersitenumber"].")<br>\n";
	list($clockstr,$clocktype)=siteclock();
	echo "</td><td bgcolor=\"#eeee00\" align=center nowrap>&nbsp;".$clockstr."&nbsp;</td></tr>\n";
	echo "</table>\n";
	echo "<table border=0 width=\"100%\" align=center>\n";
	echo " <tr>\n";
	$currentPage = basename($_SERVER['REQUEST_URI']);
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "run.php") || str_contains($currentPage, "runedit.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=run.php>Runs</a></td>\n";
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "score.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=score.php>Score</a></td>\n";
	if (getenv('BOCA_DISABLE_CLARIFICATIONS') !== 'true') {
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "clar.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=clar.php>Clarifications</a></td>\n";
	}
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "user.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=user.php>Users</a></td>\n";
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "problem.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=problem.php>Problems</a></td>\n";
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "language.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=language.php>Languages</a></td>\n";
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "answer.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=answer.php>Answers</a></td>\n";
	if (getenv('BOCA_DISABLE_MISC') !== 'true') {
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "misc.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=misc.php>Misc</a></td>\n";
	}
//echo " </tr></table><hr><table border=0 width=\"100%\" align=center><tr>\n";
	echo " </tr><tr>\n";
	if (getenv('BOCA_DISABLE_TASKS') !== 'true') {
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "task.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=task.php>Tasks</a></td>\n";
	}
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "site.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=site.php>Site</a></td>\n";
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "contest.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=contest.php>Contest</a></td>\n";
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "log.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=log.php>Logs</a></td>\n";
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "report.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=report.php>Reports</a></td>\n";
	if (getenv('BOCA_DISABLE_BACKUP') !== 'true') {
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "files.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=files.php>Backups</a></td>\n";
	}
	echo "  <td align=center><a class=\"menu" . (str_contains($currentPage, "option.php") ? " current-page" : "") . "\" style=\"font-weight:bold\" href=option.php>Options</a></td>\n";
	echo "  <td align=center><a class=menu style=\"font-weight:bold\" href=$loc/index.php>Logout</a></td>\n";
	echo " </tr>\n"; 
	echo "</table>\n";
}

//if(decryptData(encryptData("aaaaa","senha"),"senha")) MSGError("yay");

?>
