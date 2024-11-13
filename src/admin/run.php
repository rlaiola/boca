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
// Last modified 29/aug/2017 by cassio@ime.usp.br
require 'header.php';
if(isset($_GET["order"]) && $_GET["order"] != "") {
$order = myhtmlspecialchars($_GET["order"]);
	$_SESSION["runline"] = $order;
} else {
	if(isset($_SESSION["runline"]))
  $order = $_SESSION["runline"];
else
		$order = '';
}
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
  <td><b>Judge (Site)</b></td>
  <td><b>AJ</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

// forca aparecer as runs do proprio site
if (trim($s["sitejudging"])!="") $s["sitejudging"].=",".$_SESSION["usertable"]["usersitenumber"];
else $s["sitejudging"]=$_SESSION["usertable"]["usersitenumber"];

$run = DBAllRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"], $order);

if(isset($_POST)) {
  $nrenew = 0;
  $nreopen = 0;
  for ($i=0; $i<count($run); $i++) {
	  if(isset($_POST["cbox_" . $run[$i]["number"] . "_" . $run[$i]["site"]]) && 
		 $_POST["cbox_" . $run[$i]["number"] . "_" . $run[$i]["site"]] != "") {
		  if(isset($_POST["auto"]) && $_POST["auto"]=="Re-run autojudge for selected runs") {
		    if (DBGiveUpRunAutojudging($_SESSION["usertable"]["contestnumber"], 
					       $run[$i]["site"], $run[$i]["number"], '', '', true))
		      $nrenew++;
		  }
		  if(isset($_POST["open"]) && $_POST["open"]=="Open selected runs for rejudging") {
		    DBGiveUpRunAutojudging($_SESSION["usertable"]["contestnumber"], 
					   $run[$i]["site"], $run[$i]["number"]);
		    if (DBChiefRunGiveUp($run[$i]["number"], $run[$i]["site"], 
					 $_SESSION["usertable"]["contestnumber"]))
		      $nreopen++;
		  }
	  }
  }
  if($nrenew > 0) {
    MSGError($nrenew . " runs renewed for autojudging.");
    ForceLoad($runphp);
  }
  if($nreopen > 0) {
    MSGError($nreopen . " runs reopened.");
    ForceLoad($runphp);
  }
}

$us = DBAllUserNames($_SESSION["usertable"]["contestnumber"]);

for($judged=0; $judged<2; $judged++) {
for ($i=0; $i<count($run); $i++) {
  if($run[$i]["status"] == 'gone') continue;
  if(($run[$i]['status'] != 'judged' && $judged==0) ||
     ($run[$i]['status'] == 'judged' && $judged==1)) {



#for ($i=0; $i<count($run); $i++) {
  if($run[$i]["answer1"] != 0 && $run[$i]["answer2"] != 0 && ($run[$i]["status"] != "judged" && $run[$i]["status"] != 'deleted')) {
    if($runphp == "runchief.php")
      echo " <tr bgcolor=\"ff0000\">\n";
    else echo "<tr>\n";
    echo "  <td nowrap bgcolor=\"ff0000\">";
  }
  else {
    echo "  <tr><td nowrap>";
  }
  echo "<input type=\"checkbox\" name=\"cbox_" . $run[$i]["number"] . "_" . $run[$i]["site"] . "\" />"; 
  echo " <a href=\"" . $runeditphp . "?runnumber=".$run[$i]["number"]."&runsitenumber=".$run[$i]["site"] .
       "\">" . $run[$i]["number"] . "</a></td>\n";

  echo "  <td nowrap>" . $run[$i]["site"] . "</td>\n";
  if($runphp == "run.php") {
    if ($run[$i]["user"] != "") {
	echo "  <td nowrap>" . $us[$run[$i]["site"] . '-' . $run[$i]["user"]] . "</td>\n";
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
  if ($run[$i]["judge"] != "") {
	echo "  <td nowrap>" . $us[$run[$i]["judgesite"] .'-'. $run[$i]["judge"]] . " (" . $run[$i]["judgesite"] . ")";
  } else
	echo "  <td>&nbsp;";

  if ($run[$i]["judge1"] != "") {
	echo " [" . $us[$run[$i]["judgesite1"] .'-'. $run[$i]["judge1"]] . " (" . $run[$i]["judgesite1"] . ")]";
  }
  if ($run[$i]["judge2"] != "") {
	echo " [" . $us[$run[$i]["judgesite2"] .'-'. $run[$i]["judge2"]] . " (" . $run[$i]["judgesite2"] . ")]";
  }

  echo "</td>\n";

  if ($run[$i]["autoend"] != "") {
    $color="bbbbff";
    if ($run[$i]["autoanswer"]=="") $color="ff7777";
  }
  else if ($run[$i]["autobegin"]=="") $color="ffff88";
  else $color="77ff77";
  echo "<td bgcolor=\"#$color\">&nbsp;&nbsp;</td>\n";

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
}
}

echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";
?>
  <div id="externalToolbar" <?php if (count($run) == 0) echo "style=\"display: none\""; ?>></div>
  <br>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
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

    // Custom sorter
    function customJudgeSorter(n1, n2) {
      if (n1.value == "‎" && n2.value != "‎") {
        return -1;
      }
      else if (n2.value == "‎" && n1.value != "‎") {
        return 1;
      }
      else if (n1.value == "‎" && n2.value == "‎") {
        return 0;
      }
      else if (n1.value.toLowerCase().indexOf(' admin') == 0 &&
              n2.value.toLowerCase().indexOf(' admin') != 0) {
        return -1;
      }
      else if (n2.value.toLowerCase().indexOf(' admin') == 0 &&
              n1.value.toLowerCase().indexOf(' admin') != 0) {
        return 1;
      }
      else if (n1.value.toLowerCase() < n2.value.toLowerCase()) {
        return -1;
      }
      else if (n2.value.toLowerCase() < n1.value.toLowerCase()) {
        return 1;
      }
      else return 0;
    }

    var tfConfig = {
      base_path: '../vendor/tablefilter/0.7.3/',
      col_widths: [
        '9%', '6%', '10%',
        '5%', '15%', '14%',
        '6%', '13%', '2%',
        '20%'
      ],
      col_types: [
        'number', 'number', 'customstring',
        'number', 'customstring', 'customstring',
        'customstring', 'customjudge', 'customstring',
        'customstring'
      ],
      /* cell_parser delegate used for filtering images in a column */
      cell_parser: {
        cols: [4, 5, 7],
        parse: function(o, cell, colIndex) {
          /* admin users comes before other users, except if empty */
          if (colIndex == 7) {
            var txt = cell.textContent || cell.innerText;

            if (txt.indexOf('admin') == 0) {
              return ' ' + txt;
            }
            else if (txt == ' ') {
              return '‎';
            }
            else return txt;
          }
          else return cell.textContent || cell.innerText;
        }
      },
      col_1: 'select',
      col_2: 'select',
      col_4: 'select',
      col_5: 'select',
      col_6: 'select',
      col_7: 'select',
      col_8: 'none',
      col_9: 'select',
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
        results_per_page: ['Records: ', [50, 200, 1000, 1000000]],
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
            sort.addSortType('customjudge', customStringCaster, customJudgeSorter);
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
  <center>
<b>Click on the number of a run to edit it or select them with<br />the checkboxes and use the buttons to work on multiple runs:</b><br /><br />
      <input type="submit" name="auto" value="Re-run autojudge for selected runs" onClick="conf()">
      <input type="submit" name="open" value="Open selected runs for rejudging" onClick="conf()">
<br><br>
  </center>
  </form>
</body>
</html>
