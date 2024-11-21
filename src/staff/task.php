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
// Last modified 07/sep/2015 by cassio@ime.usp.br
require('header.php');

// This page cannot be accessed if the environment variable BOCA_DISABLE_TASKS is not set to true
if (getenv("BOCA_DISABLE_TASKS") == "true") {
  MSGError("This feature is disabled.");
  ForceLoad("../index.php");
}

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

if (isset($_GET["return"]) && is_numeric($_GET["return"]) && isset($_GET["site"]) && is_numeric($_GET["site"])) {
	DBTaskGiveUp ($_GET["return"], $_GET["site"], $_SESSION["usertable"]["contestnumber"],
		     $_SESSION["usertable"]["usernumber"], $_SESSION["usertable"]["usersitenumber"]);
	ForceLoad("task.php");
}

if (isset($_GET["get"]) && is_numeric($_GET["get"]) && isset($_GET["site"]) && is_numeric($_GET["site"])) {
	DBGetTaskToAnswer($_GET["get"], $_GET["site"], $_SESSION["usertable"]["contestnumber"]);
	ForceLoad("task.php");
}

if (isset($_GET["done"]) && is_numeric($_GET["done"]) && isset($_GET["site"]) && is_numeric($_GET["site"])) {
        DBUpdateTask( $_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"],
	      $_SESSION["usertable"]["usernumber"], $_GET["site"], $_GET["done"], 'done');
	ForceLoad("task.php");
}



?>
<br>
  <script language="javascript">
    function conf2(url) {
//      if (confirm("Confirm?")) {
        document.location=url;
//      } else {
//        document.location='task.php';
//      }
    }
  </script>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Task #</b></td>
  <td><b>Time</b></td>
  <td><b>User / Site</b></td>
  <td><b>Description</b></td>
  <td><b>File</b></td>
  <td><b>Status</b></td>
  <td><b>Actions</b></td>
 </tr>
<?php
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

//$task = DBOpenTasksInSites($_SESSION["usertable"]["contestnumber"], $s["sitetasking"]);
$task = DBAllTasksInSites($_SESSION["usertable"]["contestnumber"], $s["sitetasking"], 'task');

$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";
$limittasks=false;
if(is_readable($_SESSION["locr"] . $ds . 'private' . $ds . 'score.sep')) {
	$limittasks=true;
	$rf=file($_SESSION["locr"] . $ds . 'private' . $ds . 'score.sep');
	for($rfi=1;$rfi<=count($rf);$rfi++) {
		$lin = explode('#',trim($rf[$rfi-1]));
		if(isset($lin[1]) && $_SESSION["usertable"]["usertype"]!='admin') {
			$arr=explode(' ',trim($lin[1]));
			for($arri=0;$arri<count($arr);$arri++)
				if($arr[$arri] != '' && preg_match($arr[$arri],$_SESSION["usertable"]["username"])) break;
			if($arri>=count($arr)) continue;
		}
		$lin = trim($lin[0]);
		if($lin=='') continue;
		$grname=explode(' ',$lin);

		for ($i=0; $i<count($task); $i++) {
			for($k=1;$k<count($grname);$k++) {
				if($task[$i]["site"]==$grname[$k]) {
					$task[$i]["ok"]=true;
					break;
				}
				else if(strpos($grname[$k],'/') >= 1) {
					$u1 = explode('/',$grname[$k]);
					if(isset($u1[1]) && $task[$i]["user"] >= $u1[0] && $task[$i]["user"] <= $u1[1]) {
						if(!isset($u1[2]) || $u1[2]==$task[$i]["site"]) {
							$task[$i]["ok"]=true;
							break;
						}
					}
				}
			}
		}
	}
}

$anyprinted=false;
for ($i=0; $i<count($task); $i++) {
	if($limittasks && (!isset($task[$i]["ok"]) || $task[$i]["ok"]!=true)) continue;
	$anyprinted=true;
	$st = $task[$i]["status"];

  if($st == "processing" && $task[$i]["staff"]==$_SESSION["usertable"]["usernumber"] &&
	 $task[$i]["staffsite"]==$_SESSION["usertable"]["usersitenumber"]) $mine=1;
  else $mine=0;

  echo " <tr>\n";
  echo "  <td nowrap>" . $task[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($task[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>".$task[$i]["username"]."(" . $task[$i]["user"] . ") / ".$task[$i]["site"]."</td>\n";

  echo "  <td>" . $task[$i]["description"];
  if($task[$i]["color"] != "") {
	  echo " <img alt=\"".$task[$i]["colorname"]."\" width=\"25\" ".
		  "src=\"" . balloonurl($task[$i]["color"]) ."\" />";
  }
  echo "</td>\n";
  if ($task[$i]["oid"] != null) {
    $msg = "///// " . $task[$i]["username"]." -- ". substr($task[$i]["fullname"],0,60) ." -- ".$task[$i]["username"]." ";
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($task[$i]["oid"],$task[$i]["filename"]) .
        "\">" . $task[$i]["filename"] . "</a>";
    echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('../filewindow.php?".
		filedownload($task[$i]["oid"],$task[$i]["filename"],$msg) .
		"', 'Viewx$i','width=680,height=600,scrollbars=yes,toolbar=yes,".
		"resizable=yes')\">view</a>";
	echo "</td>\n";
  }


  else
    echo "  <td nowrap>&nbsp;</td>\n";

  if ($mine) $color="ff7777";
  else if ($st == "done") $color="bbbbff";
  else if ($st == "processing") $color="77ff77";
  else if ($st == "opentask") $color="ffff88";
  else $color="ffffff";

  echo "  <td nowrap bgcolor=\"#$color\">$st</td>\n  <td nowrap>";

  if($st == "opentask")
    echo "  <a href=\"javascript: conf2('task.php?get=" . $task[$i]["number"] . "&site=" . 
       $task[$i]["site"] . "')\">get</a>\n";
  if($st == "processing" && $mine==1) {
    echo "  <a href=\"javascript: conf2('task.php?return=" . $task[$i]["number"] . "&site=" . 
       $task[$i]["site"] . "')\">return</a>\n";
    echo "  <a href=\"javascript: conf2('task.php?done=" . $task[$i]["number"] . "&site=" . 
       $task[$i]["site"] . "')\">done</a>\n";
  }
  echo "&nbsp;</td>\n";
}
echo "</table>";
if (!$anyprinted) echo "<br><center><b><font color=\"#ff0000\">NO TASKS AVAILABLE</font></b></center>";

?>
<?php
// Check if the table filter should be enabled
if (getenv("BOCA_ENABLE_TABLE_FILTER") == "true") {
?>
<div id="externalToolbar" <?php if (count($task) == 0) echo "style=\"display: none\""; ?>></div>
<script language="JavaScript">
  // Custom string caster
  function customStringCaster(val) {
    return val.toString();
  }

  // Custom string sorter
  function customStringSorter(n1, n2) {
    if (n1.value.toLowerCase() < n2.value.toLowerCase()) {
      return -1;
    }
    if (n2.value.toLowerCase() < n1.value.toLowerCase()) {
      return 1;
    }
    return 0;
  }

  var tfConfig = {
    base_path: '../vendor/tablefilter/0.7.3/',
    col_widths: [
      '8%', '5%', '23%',
      '31%', '15%', '8%',
	  '10%'
    ],
    col_types: [
      'number', 'number', 'customstring',
      'customstring', 'customstring', 'customstring',
      'customstring'
    ],
    col_2: 'select',
    col_5: 'select',
    col_6: 'none',
    responsive: {
      details: true
    },
    toolbar: {
      target_id: 'externalToolbar'
    },
    sticky_headers: true,
    rows_counter: {
      ignore_case: true
    },
    watermark: 'Filter...',
    auto_filter: {
      delay: 100 //milliseconds
    },
    msg_filter: 'Filtering...',
    loader: true,
    status_bar: true,
    ignore_diacritics: true,
    <?php if (count($task) != 0) { ?>
    no_results_message: {
      content: '<?php echo "<center><b><font color=\"#ff0000\">NO TASKS FOUND</font></b></center>" ?>',
    },
    <?php } ?>
    paging: {
      results_per_page: ['Records: ', [50, 200, 1000, 1000000]],
    },
    // grid layout customisation
    grid_layout: {
      width: '100%',
      <?php if (count($task) != 0) { ?>
      height: 'auto'
      <?php } else { ?>
      height: 'auto'
      <?php } ?>
    },
    btn_reset: true,
    extensions: [
      {
        name: 'filtersVisibility',
        visible_at_start: false
      },
      {
        name: 'colsVisibility',
        enable_tick_all: true
      },
      {
        name: 'sort',
        // Register custom sorter when sort extension is loaded
        on_sort_loaded: function(o, sort) {
          // addSortType accepts:
          // 1. an identifier of the sort type (lowercase)
          // 2. an optional function that takes a string and casts it to a
          // desired format, if not specified it returns the string
          // 3. an optional compare function taking 2 values and compares
          // them. If not specified defaults to `less than compare` type
          sort.addSortType('customstring', customStringCaster, customStringSorter);
        }
      },
    ]
  };
  var tf = new TableFilter(
    document.querySelector('.bocaTable'),
    tfConfig
  );
  tf.init();
</script>
<?php
}
?>
</body>
</html>
