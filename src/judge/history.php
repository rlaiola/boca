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
// updated 20/oct/08 by cassio@ime.usp.br
//  -  bugfix of Marcelo Cezar Pinto (mcpinto@unipampa.edu.br) - div by zero at counts
require('header.php');
?>
<br>
<table class="bocaTable1" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Clar # (site)</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Status</b></td>
  <td><b>Question</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
$clar = DBAllClarsInSites($_SESSION["usertable"]["contestnumber"],  $s["sitejudging"], "clar");
//$clar = DBJudgedClars($_SESSION["usertable"]["contestnumber"],
//		      $_SESSION["usertable"]["usersitenumber"],
//		      $_SESSION["usertable"]["usernumber"]);
$myclars = 0;
for ($i=0; $i<count($clar); $i++) {
  echo " <tr>\n";
  if($clar[$i]["judge"] == $_SESSION["usertable"]["usernumber"] &&
     $clar[$i]["judgesite"] == $_SESSION["usertable"]["usersitenumber"]) {
    echo "  <td nowrap bgcolor=\"#b0b0a0\">" . $clar[$i]["number"] . "(" . $clar[$i]["site"] . ")</td>\n";
    $myclars++;
  }
  else
    echo "  <td nowrap>" . $clar[$i]["number"] . "(" . $clar[$i]["site"] . ")</td>\n";
  echo "  <td nowrap>" . dateconvminutes($clar[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $clar[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $clar[$i]["status"] . "</td>\n";
  if ($clar[$i]["question"] == "") $clar[$i]["question"] = "&nbsp;";
  if ($clar[$i]["answer"] == "") $clar[$i]["answer"] = "&nbsp;";

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
<div id="externalToolbar1" <?php if (count($clar) == 0) echo "style=\"display: none\""; ?>></div>
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

  var tfConfig = {
    base_path: '../vendor/tablefilter/0.7.3/',
    col_widths: [
      '10%', '10%', '20%',
      '10%', '25%', '25%'
    ],
    col_types: [
      'number', 'number', 'customproblem',
      'customstring', 'customstring', 'customstring'
    ],
    /* cell_parser delegate used for filtering images in a column */
    cell_parser: {
      cols: [2, 4, 5],
      parse: function(o, cell, colIndex) {
        /* Clars targeted to General comes first */
        if (colIndex == 2) {
          var txt = cell.textContent || cell.innerText;
          if (txt == 'General') {
            return ' General';
          }
          else return txt;
        }
        else {
          var txt = cell.getElementsByTagName('textarea')[0].value;
          return txt;
        }
      }
    },
    col_2: 'select',
    col_3: 'select',
    responsive: {
      details: true
    },
    toolbar: {
      target_id: 'externalToolbar1'
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
      height: '400px'
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
          sort.addSortType('customstring', customStringCaster, customStringSorter);
        }
      },
    ]
  };
  var tf = new TableFilter(
    document.querySelector('.bocaTable1'),
    tfConfig
  );
  tf.init();
</script>
<br><br>
<table class="bocaTable2" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Run #</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Language</b></td>
  <td><b>Status</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
$run = DBAllRunsInSites($_SESSION["usertable"]["contestnumber"],
  	  	     $s["sitejudging"],
		    "run");
//$run = DBJudgedRuns($_SESSION["usertable"]["contestnumber"],
//  	  	    $_SESSION["usertable"]["usersitenumber"],
//		    $_SESSION["usertable"]["usernumber"]);
$yes = 0;
$myyes = 0;
$myruns = 0;
for ($i=0; $i<count($run); $i++) {
  echo " <tr>\n";
  if($run[$i]["yes"]=="t") $yes++;
  if(($_SESSION["usertable"]["usersitenumber"] == $run[$i]["judgesite1"] &&
     $_SESSION["usertable"]["usernumber"] == $run[$i]["judge1"]) ||
	 ($_SESSION["usertable"]["usersitenumber"] == $run[$i]["judgesite2"] &&
	  $_SESSION["usertable"]["usernumber"] == $run[$i]["judge2"])) {
    echo "  <td nowrap bgcolor=\"#b0b0a0\">" . $run[$i]["number"] . "</td>\n";
    if($run[$i]["yes"]=="t") $myyes++;
    $myruns++;
  }
  else
    echo "  <td nowrap>" . $run[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["status"] . "</td>\n";
  if ($run[$i]["answer"] == "") $run[$i]["answer"] = "&nbsp;";
  echo "  <td>" . $run[$i]["answer"] . "</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";
?>
<div id="externalToolbar2" <?php if (count($run) == 0) echo "style=\"display: none\""; ?>></div>
<br>
<script language="JavaScript">
  var tfConfig2 = {
    base_path: '../vendor/tablefilter/0.7.3/',
    col_widths: [
      '10%', '10%', '20%',
      '15%', '10%', '35%'
    ],
    col_types: [
      'number', 'number', 'customstring',
      'customstring', 'customstring', 'customstring'
    ],
    /* cell_parser delegate used for filtering images in a column */
    cell_parser: {
      cols: [3, 4],
      parse: function(o, cell, colIndex) {
        var txt = cell.textContent || cell.innerText;
        return txt;
      }
    },
    col_2: 'select',
    col_3: 'select',
    col_4: 'select',
    col_5: 'select',
    responsive: {
      details: true
    },
    toolbar: {
      target_id: 'externalToolbar2'
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
      height: '400px'
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
  var tf2 = new TableFilter(
    document.querySelector('.bocaTable2'),
    tfConfig2
  );
  tf2.init();
</script>

<?php
echo "<br><br>\n";
echo "My answered clars: " . $myclars . "/" . count($clar) . " (";
if(count($clar)>0) echo ((int) ($myclars*1000/count($clar)))/10 . "%)<br>\n";
else echo "0%)<br>\n";
echo "My judged runs: " . $myruns . "/" . count($run) ." (";
if(count($run)>0) echo ((int) ($myruns*1000/count($run)))/10 . "%)<br>\n";
else echo "0%)<br>\n";
echo "Accepted runs that I've judged: " . $myyes . "/" . $yes . " (";
if($yes>0) echo ((int) ($myyes*1000/$yes))/10 ."%)<br>\n";
else echo "0%)<br>\n";

if (count($clar) != 0 || count($run) != 0) echo "<br><b><font color=\"#b0b0a0\">* Shadowed clars and runs were judged by this judge</font></b>";

?>
</body>
</html>
