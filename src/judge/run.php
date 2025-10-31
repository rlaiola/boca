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
// Last modified 21/jul/2012 by cassio@ime.usp.br
require('header.php');
?>

<br>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Run #</b></td>
  <td><b>Site</b></td>
  <td><b>Time</b></td>
  <td><b>Problem</b></td>
  <td><b>Language</b></td>
<!--  <td><b>Filename</b></td> -->
  <td><b>Status</b></td>
  <td><b>AJ</b></td>
  <td><b>Answer</b></td>
 </tr>
<?php
if (($s=DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

$run = DBOpenRunsInSites($_SESSION["usertable"]["contestnumber"], $s["sitejudging"]);

for ($i=0; $i<count($run); $i++) {
  echo " <tr>\n";
//  if (strpos($run[$i]["status"], "judged") === false || $run[$i]["judge"]=="" || $run[$i]["judge"]==$_SESSION["usertable"]["usernumber"])
  echo "  <td nowrap><a href=\"runedit.php?runnumber=".$run[$i]["number"]."&runsitenumber=".$run[$i]["site"] .
         "\">" . $run[$i]["number"] . "</td>\n";
//  else
//    echo "  <td nowrap>" . $run[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["site"] . "</td>\n";
  echo "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
  echo "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
//  echo "  <td nowrap>" . $run[$i]["filename"] . "</td>\n";
  if ($run[$i]["judge1"] == $_SESSION["usertable"]["usernumber"] && 
      $run[$i]["judgesite1"] == $_SESSION["usertable"]["usersitenumber"])
    $color="ff7777";
  else if ($run[$i]["judge2"] == $_SESSION["usertable"]["usernumber"] && 
      $run[$i]["judgesite2"] == $_SESSION["usertable"]["usersitenumber"])
    $color="ff7777";
  else if ($run[$i]["status"] == "judged+") $color="ffff00";
  else if ($run[$i]["status"] == "judged") $color="0000ff";
  else if ($run[$i]["status"] == "judging") $color="77ff77";
  else if ($run[$i]["status"] == "openrun") $color="ffff88";
  else $color="ffffff";

  echo "  <td nowrap bgcolor=\"#$color\">" . $run[$i]["status"] . "</td>\n";
  if ($run[$i]["autoend"] != "") {
    $color="bbbbff";
    if ($run[$i]["autoanswer"]=="") $color="ff7777";
  }
  else if ($run[$i]["autobegin"]=="") $color="ffff88";
  else $color="77ff77";
  echo "<td bgcolor=\"#$color\">&nbsp;&nbsp;</td>\n";

  if ($run[$i]["answer"] == "") $run[$i]["answer"] = "&nbsp;";
  echo "  <td>" . $run[$i]["answer"] . "</td>\n";
  echo " </tr>\n";
}

echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";

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
        '8%', '5%', '10%',
        '15%', '15%', '15%',
        '2%', '30%'
      ],
      col_types: [
        'number', 'number', 'number',
        'customstring', 'customstring', 'customstring',
        'customstring', 'customstring'
      ],
      /* cell_parser delegate used for filtering images in a column */
      cell_parser: {
        cols: [3, 4],
        parse: function(o, cell, colIndex) {
          var txt = cell.textContent || cell.innerText;
          return txt;
        }
      },
      col_1: 'select',
      col_3: 'select',
      col_4: 'select',
      col_5: 'select',
      col_6: 'none',
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
  <br>
</body>
</html>
