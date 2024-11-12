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
require('header.php');

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
?>

<?php
/*
<br>General information: <a href="https://global.naquadah.com.br/boca/info_sheet.pdf">info_sheet.pdf</a>

<br>Timelimits:
<a href="https://global.naquadah.com.br/boca/contest_times.pdf">contest_times.pdf</a> 
 */

if(is_readable('/var/www/boca/src/sample/secretcontest/maratona.pdf')) {
?>
<b>PLAIN FILES:</b>  <b>CONTEST</b> (
<a href='https://global.naquadah.com.br/boca/secretcontest/maratona.pdf'>PT</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/maratona_es.pdf'>ES</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/maratona_en.pdf'>EN</a>
)
&nbsp;&nbsp;&nbsp; 
<b>Info Sheet</b> (
<a href='https://global.naquadah.com.br/boca/secretcontest/info_maratona.pdf'>PT</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/info_maratona_es.pdf'>ES</a> |
<a href='https://global.naquadah.com.br/boca/secretcontest/info_maratona_en.pdf'>EN</a>
)

<?php
}
?>


<br>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Name</b></td>
  <td><b>Basename</b></td>
  <td><b>Fullname</b></td>
  <td><b>Descfile</b></td>
 </tr>
<?php

list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
                        $_SESSION["usertable"]["usersitenumber"], 1, -1);
// print_r(array_keys($score));
$userkey=$_SESSION["usertable"]["usernumber"] . "-" . $_SESSION["usertable"]["usersitenumber"];
// echo $userkey;
$solvedproblems = $score[$userkey]["problem"];
// echo print_r($solvedproblems);

$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"]);
for ($i=0; $i<count($prob); $i++) {
  if (array_key_exists($prob[$i]["number"], $solvedproblems)) {
    echo " <tr bgcolor=\"#b0b0a0\">\n";
  }
  else {
    echo " <tr>\n";
  }
//  echo "  <td nowrap>" . $prob[$i]["number"] . "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["problem"];
  if($prob[$i]["color"] != "")
          echo " <img alt=\"".$prob[$i]["colorname"]."\" width=\"20\" ".
			  "src=\"" . balloonurl($prob[$i]["color"]) ."\" />\n";
  echo "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["basefilename"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $prob[$i]["fullname"] . "&nbsp;</td>\n";
  if (isset($prob[$i]["descoid"]) && $prob[$i]["descoid"] != null && isset($prob[$i]["descfilename"])) {
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["descoid"], $prob[$i]["descfilename"]) .
		"\">" . basename($prob[$i]["descfilename"]) . "</a></td>\n";
  }
  else
    echo "  <td nowrap>no description file available</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($prob) == 0) echo "<br><center><b><font color=\"#ff0000\">NO PROBLEMS AVAILABLE YET</font></b></center>";

?>
<div id="externalToolbar" <?php if (count($prob) == 0) echo "style=\"display: none\""; ?>></div>
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
      '20%', '20%',
      '40%', '20%'
    ],
    col_types: [
      'customstring', 'customstring',
      'customstring', 'customstring'
    ],
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
    <?php if (count($prob) != 0) { ?>
    no_results_message: {
      content: '<?php echo "<center><b><font color=\"#ff0000\">NO PROBLEMS FOUND</font></b></center>" ?>',
    },
    <?php } ?>
    paging: {
      results_per_page: ['Records: ', [50, 200, 1000, 1000000]],
    },
    // grid layout customisation
    grid_layout: {
      width: '100%',
      <?php if (count($prob) != 0) { ?>
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
  var tf = new TableFilter(
    document.querySelector('.bocaTable'),
    tfConfig
  );
  tf.init();
</script>
<br><center><b><font>Problems with a shadow overlay indicate they have already been solved.</font></b></center>
</body>
</html>
