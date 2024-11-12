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
// Last modified 05/aug/2012 by cassio@ime.usp.br
require 'header.php';

// This page cannot be accessed if the environment variable BOCA_ENABLE_VIEW_RUNS is not set to true
if (getenv("BOCA_ENABLE_VIEW_RUNS") != "true") {
  MSGError("This feature is disabled.");
  ForceLoad("../index.php");
}

// ???
$runsitenumber = myhtmlspecialchars($_GET["runsitenumber"]);
$runnumber = myhtmlspecialchars($_GET["runnumber"]);

if (($a = DBChiefGetRunToAnswer($runnumber, $runsitenumber, 
		$_SESSION["usertable"]["contestnumber"])) === false) {
	MSGError("Another judge got it first.");
	ForceLoad($runphp);
}

$b = DBGetProblemData($_SESSION["usertable"]["contestnumber"], $a["problemnumber"]);
?>
<br><br><center><b>The following fields were used to judge this run:
</b></center>
<form name="form1" method="post" action="<?php echo $runeditphp; ?>">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="1">
      <tr> 
        <td width="27%" align=right><b>Site:</b></td>
        <td width="83%"> 
		<input type=hidden name="sitenumber" value="<?php echo $a["sitenumber"]; ?>" />
		<?php echo $a["sitenumber"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Number:</b></td>
        <td width="83%"> 
		<input type=hidden name="number" value="<?php echo $a["number"]; ?>" />
		<?php echo $a["number"]; ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Time:</b></td>
        <td width="83%"> 
		<?php echo dateconvminutes($a["timestamp"]); ?>
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Problem</b>: </td>
        <td width="83%"> 
          <?php echo $a["problemname"]; ?>
	&nbsp;</td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Language</b>:</td>
        <td width="83%"><i> <?php echo $a["language"]; ?></i>
        &nbsp;</td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Source code:</b></td>
        <td width="83%"> 
<?php
			echo "<a href=\"../filedownload.php?". filedownload($a["sourceoid"],$a["sourcename"]) . "\">" . $a["sourcename"] . "</a>\n";
echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?" .
filedownload($a["sourceoid"],$a["sourcename"]) ."', 'View - SOURCE', ".
"'width=680,height=600,scrollbars=yes,resizable=yes')\">view</a>\n";
?>
        </td>
      </tr>
      <tr>
        <td width="27%" align=right><b>Answer:</b></td>
        <td width="83%">
<?php
$ans = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);

for ($i=0;$i<count($ans);$i++)
	if ($a["answer"] == $ans[$i]["number"]) {
    echo $ans[$i]["desc"];
//	  if($ans[$i]["fake"] != "t") {
    if ($a["judgesite"] != "" && $a["judge"] != "") {
      $uu = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $a["judgesite"], $a["judge"]);
      // echo " [judge=" . $uu["username"] . " (" . $a["judgesite"] . ")]";
	  }
  }
?>
        </td>
      </tr>

<!--
      <tr> 
        <td width="27%" align=right><b>Notify user:</b></td>
        <td width="83%">
          <input class=checkbox type=checkbox name="notifyuser" value="yes"
<?php 
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

if ($a["timestamp"] < $s["sitelastmileanswer"]) echo "checked"; ?>>
(do not change this unless you know exactly what you are doing)
        </td>
      </tr>
      <tr> 
        <td width="27%" align=right><b>Update score board:</b></td>
        <td width="83%">
          <input class=checkbox type=checkbox name="updatescore" value="yes"
<?php if ($a["timestamp"] < $s["sitelastmilescore"]) echo "checked"; ?>>
(do not change this unless you know exactly what you are doing)
        </td>
      </tr>
-->
    </table>
  </center>
  <br>
<!--
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Judge" onClick="conf()">
     <input type="submit" name="open" value="Open run for rejudging" onClick="conf()">
      <input type="submit" name="cancel" value="Cancel editing">
      <input type="submit" name="delete" value="Delete" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
<br><br>
  </center>

-->
  <center>
<br>
<b>Autojudging:</b>
<!--
<input type="submit" name="giveup" value="Renew">
-->
<br><br>
  <table border="1">
  <tr>
        <td width="27%" align=right><b>Autojudging answer:</b></td>
        <td width="83%"> 
<?php
if($a["autobegin"]!="" && $a["autoend"]=="")
      echo "in progress";
else if($a["autoend"]!="") {
      if($a["autoanswer"]!="") echo $a["autoanswer"];
      else echo "Autojudging error";
} else
      echo "unavailable";
?>
        </td>
  </tr>
  <tr>
        <td width="27%" align=right><b>Autojudged by:</b></td>
<?php if($a["autobegin"]!="" && $a["autoend"]=="")
      echo "<td width=\"83%\">". $a["autoip"] ." since ". dateconv($a["autobegin"]) ."</td>";
else if($a["autoend"]!="")
      echo "<td width=\"83%\">". $a["autoip"] ." from ". dateconv($a["autobegin"]) ." to ". dateconv($a["autoend"]) ."</td>";
else
      echo "<td width=\"83%\">unavailable</td>";
?>
  </tr>
  <tr> 
        <td width="27%" align=right><b>Standard output:</b></td>
        <td width="83%"> 
<?php 
if($a["autostdout"]!="") {
	echo "<a href=\"../filedownload.php?".filedownload($a["autostdout"],"stdout") ."\">stdout</a>\n";
	echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
	filedownload($a["autostdout"],"stdout") ."', 'View - STDOUT','width=680,height=600,scrollbars=yes,".
	"resizable=yes')\">view</a>\n";
} else
      echo "unavailable";
?>
        </td>
  </tr>
  <tr> 
        <td width="27%" align=right><b>Standard error:</b></td>
        <td width="83%"> 
<?php 
if($a["autostderr"]!="") {
	echo "<a href=\"../filedownload.php?". filedownload($a["autostderr"],"stderr") . "\">stderr</a>\n";
	echo "<a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
	filedownload($a["autostderr"],"stderr") ."', 'View - STDERR','width=680,height=600,scrollbars=yes,".
	"resizable=yes')\">view</a>\n";
} else
      echo "unavailable";
?>
        </td>
  </tr>
  </table></center>

</form>
</body>
</html>
