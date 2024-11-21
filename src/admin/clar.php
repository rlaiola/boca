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

// This page cannot be accessed if the environment variable BOCA_DISABLE_CLARIFICATIONS is not set to true
if (getenv("BOCA_DISABLE_CLARIFICATIONS") == "true") {
  MSGError("This feature is disabled.");
  ForceLoad("../index.php");
}

if (isset($_POST["message"]) && isset($_POST["problem"]) && isset($_POST["Submit"])) {
	if ($_POST["confirmation"] == "confirm") {
		$param['contest']=$_SESSION["usertable"]["contestnumber"];
		$param['site']=$_SESSION["usertable"]["usersitenumber"];
		$param['user']= $_SESSION["usertable"]["usernumber"];
		$param['problem'] = htmlspecialchars($_POST["problem"]);
		$param['question'] = htmlspecialchars($_POST["message"]);
		DBNewClar($param);
	}
	ForceLoad("clar.php");
}
if(isset($_GET["order"]) && $_GET["order"] != "") {
	$order = htmlspecialchars($_GET["order"]);
	$_SESSION["clarline"] = $order;
} else {
	if(isset($_SESSION["clarline"]))
		$order = $_SESSION["clarline"];
	else
		$order='';
}
?>
<br>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Clar #</b></td>
  <td><b>Site</b></td>
  <td><b>User</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Status</b></td>
  <td><b>Judge (Site)</b></td>
  <td><b>Question</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php

if(($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"])) == null)
	ForceLoad("$loc/index.php");

// forca aparecer as clars do proprio site
if (trim($s["sitejudging"])!="") $s["sitejudging"].=",".$_SESSION["usertable"]["usersitenumber"];
else $s["sitejudging"]=$_SESSION["usertable"]["usersitenumber"];

$clar = DBAllClarsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"], $order);

for ($i=0; $i<count($clar); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap><a href=\"claredit.php?clarnumber=".$clar[$i]["number"]."&clarsitenumber=".$clar[$i]["site"] .
       "\">" . $clar[$i]["number"] . "</a></td>\n";
  echo "  <td nowrap>" . $clar[$i]["site"] . "</td>\n";
  echo "  <td nowrap>" . $clar[$i]["user"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($clar[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $clar[$i]["problem"] . "</td>\n";
  if ($clar[$i]["judge"] == $_SESSION["usertable"]["usernumber"] &&
      $clar[$i]["judgesite"] == $_SESSION["usertable"]["usersitenumber"] && $clar[$i]["status"] == "answering")
    $color="ff7777";
  else if (strpos($clar[$i]["status"], "answered") !== false) $color="bbbbff";
  else if ($clar[$i]["status"] == "answering") $color="77ff77";
  else if ($clar[$i]["status"] == "openclar") $color="ffff88";
  else $color="ffffff";

  echo "  <td nowrap bgcolor=\"#$color\">" . $clar[$i]["status"] . "</td>\n";
  if ($clar[$i]["judge"] != "") {
    $u = DBUserInfo ($_SESSION["usertable"]["contestnumber"], $clar[$i]["judgesite"], $clar[$i]["judge"]);
    echo "  <td nowrap>" . $u["username"] . " (" . $clar[$i]["judgesite"] . ")</td>\n";
  }
  else
    echo "  <td>&nbsp;</td>\n";

  if ($clar[$i]["question"] == "") $clar[$i]["question"] = "&nbsp;";

  echo "  <td>";
//  echo "<pre>" . $clar[$i]["question"] . "</pre>";
//  echo $clar[$i]["question"];
  echo "  <textarea name=\"m$i\" cols=\"60\" rows=\"8\" readonly>". unsanitizeText($clar[$i]["question"]) ."</textarea>\n";
  echo "</td>\n";
  if (trim($clar[$i]["answer"]) == "") $clar[$i]["answer"] = "Not answered yet";
  echo "  <td>";
//  echo "  <pre>" . $clar[$i]["answer"] . "</pre>";
//  echo $clar[$i]["answer"];
  echo "  <textarea name=\"a$i\" cols=\"60\" rows=\"8\" readonly>". unsanitizeText($clar[$i]["answer"]) ."</textarea>\n";
  echo "</td>\n";

  echo " </tr>\n";
}

echo "</table>";
if (count($clar) == 0) echo "<br><center><b><font color=\"#ff0000\">NO CLARIFICATIONS AVAILABLE</font></b></center>";

?>
<?php
// Check if the table filter should be enabled
if (getenv("BOCA_ENABLE_TABLE_FILTER") == "true") {
?>
<div id="externalToolbar" <?php if (count($clar) == 0) echo "style=\"display: none\""; ?>></div>
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

  // Custom string sorter
  function customProblemSorter(n1, n2) {
    if (n1.value  == "General" && n2.value != "General") {
      return -1;
    }
    if (n2.value == "General" && n1.value != "General") {
      return 1;
    }
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
      '7%', '6%', '6%',
      '6%', '15%', '8%', 
      '8%', '22%', '22%'
    ],
    col_types: [
      'number', 'number', 'number',
      'customstring', 'customproblem', 'customstring',
      'customjudge', 'customstring', 'customstring'
    ],
    /* cell_parser delegate used for filtering images in a column */
    cell_parser: {
      cols: [4, 6, 7, 8],
      parse: function(o, cell, colIndex) {
        /* Clars targeted to General comes first */
        if (colIndex == 4) {
          var txt = cell.textContent || cell.innerText;
          if (txt == 'General') {
            return ' General';
          }
          else return txt;
        }
        /* admin users comes before other users, except if empty */
        else if (colIndex == 6) {
          var txt = cell.textContent || cell.innerText;

          if (txt.indexOf('admin') == 0) {
            return '‎' + txt;
          }
          else if (txt == ' ') {
            return '‎';
          }
          else return txt;
        }
        else {
          var txt = cell.getElementsByTagName('textarea')[0].value;
          return txt;
        }
      }
    },
    col_1: 'select',
    col_2: 'select',
    col_4: 'select',
    col_5: 'select',
    col_6: 'select',
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
    <?php if (count($clar) != 0) { ?>
    no_results_message: {
      content: '<?php echo "<center><b><font color=\"#ff0000\">NO CLARIFICATIONS FOUND</font></b></center>" ?>',
    },
    <?php } ?>
    paging: {
      results_per_page: ['Records: ', [50, 200, 1000, 1000000]],
    },
    // grid layout customisation
    grid_layout: {
      width: '100%',
      <?php if (count($clar) != 0) { ?>
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
          sort.addSortType('customproblem', customStringCaster, customProblemSorter);
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
<?php
}
?>
</body>
</html>
