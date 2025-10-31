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
if(isset($_POST["order"]) && $_POST["order"] != "") {
$order = myhtmlspecialchars($_POST["order"]);
	$_SESSION["runline"] = $order;
} else {
	if(isset($_SESSION["runline"]))
  $order = $_SESSION["runline"];
else
		$order = '';
}
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

$runphp="run.php";
?>
<form name="form1" method="post" action="<?php echo $runphp; ?>">
  <input type=hidden name="confirmation" value="noconfirm" />
<br>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Run #</b></td>
  <td><b>Site</b></td>
<?php if($runphp == "run.php") { ?>
  <td><b>User</b></td>
<?php } ?>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Language</b></td>
<!--  <td><b>Filename</b></td> -->
  <td><b>Status</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

// forca aparecer as runs do proprio site
//echo disabled;
//exit;
echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/stat.php', ".
                "'Problem Statistics','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
                "resizable=yes')\">Statistics</a><br />\n";
//if($_SESSION['usertable']['username']=='staffnoco999') {
echo " <a href=\"#\" class=menu style=\"font-weight:bold\" onClick=\"window.open('report/score.php?p=0&hor=0', ".
                "'Complete Scoreboard','width=800,height=600,scrollbars=yes,toolbar=yes,menubar=yes,".
                "resizable=yes')\">Interactive Scoreboard</a><br /><br />\n";
//}

if (trim($s["sitejudging"])!="") $s["sitejudging"].=",".$_SESSION["usertable"]["usersitenumber"];
else $s["sitejudging"]=$_SESSION["usertable"]["usersitenumber"];

$run = DBAllRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"], $order);

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

                for ($i=0; $i<count($run); $i++) {
                        for($k=1;$k<count($grname);$k++) {
                                if($run[$i]["site"]==$grname[$k]) {
                                        $run[$i]["okk"]=true;
                                        break;
                                }
                                else if(strpos($grname[$k],'/') >= 1) {
                                        $u1 = explode('/',$grname[$k]);
                                        if(isset($u1[1]) && $run[$i]["user"] >= $u1[0] && $run[$i]["user"] <= $u1[1]) {
                                                if(!isset($u1[2]) || $u1[2]==$run[$i]["site"]) {
                                                        $run[$i]["okk"]=true;
                                                        break;
                                                }
                                        }
                                }
                        }
                }
        }
} else $run = array();

$anyprinted=false;


for ($i=0; $i<count($run); $i++) {
if($limittasks && (!isset($run[$i]["okk"]) || $run[$i]["okk"]!=true)) continue;
        $anyprinted=true;

  if($run[$i]["answer1"] != 0 && $run[$i]["answer2"] != 0 && $run[$i]["status"] != "judged") {
    if($runphp == "runchief.php")
      echo " <tr bgcolor=\"ff0000\">\n";
    else echo "<tr>\n";
    echo "  <td nowrap bgcolor=\"ff0000\">";
  }
  else {
    echo "  <tr><td nowrap>";
  }
  //echo "<input type=\"checkbox\" name=\"cbox_" . $run[$i]["number"] . "_" . $run[$i]["site"] . "\" />"; 
  //echo " <a href=\"" . $runeditphp . "?runnumber=".$run[$i]["number"]."&runsitenumber=".$run[$i]["site"] .
    //   "\">" . $run[$i]["number"] . "</a></td>\n";
echo $run[$i]["number"] . "</td>";

  echo "  <td nowrap>" . $run[$i]["site"] . "</td>\n";
  if($runphp == "run.php") {
    if ($run[$i]["user"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["site"], $run[$i]["user"]);
	echo "  <td nowrap>" . $u["username"] . "</td>\n";
    }
  }
  echo "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
//  echo "  <td nowrap>" . $run[$i]["filename"] . "</td>\n";
  if ($run[$i]["judge"] == $_SESSION["usertable"]["usernumber"] && 
      $run[$i]["judgesite"] == $_SESSION["usertable"]["usersitenumber"] && $run[$i]["status"] == "judging")
    $color="ff7777";
  else if ($run[$i]["status"]== "judged+" && $run[$i]["judge"]=="") $color="ffff00";
  else if ($run[$i]["status"]== "judged") $color="bbbbff";
  else if ($run[$i]["status"] == "judging" || $run[$i]["status"]== "judged+") $color="77ff77";
  else if ($run[$i]["status"] == "openrun") $color="ffff88";
  else $color="ffffff";

  echo "  <td nowrap bgcolor=\"#$color\">" . $run[$i]["status"] . "</td>\n";
/*  
if ($run[$i]["judge"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite"], $run[$i]["judge"]);
	echo "  <td nowrap>" . $u["username"] . " (" . $run[$i]["judgesite"] . ")";
  } else
	echo "  <td>&nbsp;";

  if ($run[$i]["judge1"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite1"], $run[$i]["judge1"]);
	echo " [" . $u["username"] . " (" . $run[$i]["judgesite1"] . ")]";
  }
  if ($run[$i]["judge2"] != "") {
	$u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $run[$i]["judgesite2"], $run[$i]["judge2"]);
	echo " [" . $u["username"] . " (" . $run[$i]["judgesite2"] . ")]";
  }

  echo "</td>\n";

  if ($run[$i]["autoend"] != "") {
    $color="bbbbff";
    if ($run[$i]["autoanswer"]=="") $color="ff7777";
  }
  else if ($run[$i]["autobegin"]=="") $color="ffff88";
  else $color="77ff77";
  echo "<td bgcolor=\"#$color\">&nbsp;&nbsp;</td>\n";
*/
  if ($run[$i]["answer"] == "") {
    echo "  <td>&nbsp;</td>\n";
  } else {
    echo "  <td>" . $run[$i]["answer"];
    if($run[$i]['yes']=='t') {
          echo " <img alt=\"".$run[$i]["colorname"]."\" width=\"10\" ".
			  "src=\"" . balloonurl($run[$i]["color"]) ."\" />";
    }
    echo "</td>\n";
  }
  echo " </tr>\n";
}

echo "</table>";
if (!$anyprinted) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";
?>
<?php
// Check if the table filter should be enabled
if (getenv("BOCA_ENABLE_TABLE_FILTER") == "true") {
?>
  <div id="externalToolbar" <?php if (count($run) == 0) echo "style=\"display: none\""; ?>></div>
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
        '100px', '75px', '150px',
        '75px', '225px', '200px',
        '75px', '475px'
      ],
      col_types: [
        'number', 'number', 'customstring',
        'number', 'customstring', 'customstring',
        'customstring', 'customstring'
      ],
      /* cell_parser delegate used for filtering images in a column */
      cell_parser: {
        cols: [4, 5],
        parse: function(o, cell, colIndex) {
          var txt = cell.textContent || cell.innerText;
          return txt;
        }
      },
      col_1: 'select',
      col_2: 'select',
      col_4: 'select',
      col_5: 'select',
      col_6: 'select',
      col_7: 'select',
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
      <?php if (count($run) != 0) { ?>
      no_results_message: {
        content: '<?php echo "<center><b><font color=\"#ff0000\">NO RUNS FOUND</font></b></center>" ?>',
      },
      <?php } ?>
      paging: {
        results_per_page: ['Records: ', [10, 50, 200, 1000, 1000000]],
      },
      // grid layout customisation
      grid_layout: {
        width: '100%',
        <?php if (count($run) != 0) { ?>
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
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <br>
  </form>
</body>
</html>
