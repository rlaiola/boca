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

// This page cannot be accessed if the environment variable BOCA_DISABLE_BACKUP is not set to true
if (getenv("BOCA_DISABLE_BACKUP") == "true") {
  MSGError("This feature is disabled.");
  ForceLoad("../index.php");
}

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");

if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
   DBBkpDelete($_GET["delete"],$_SESSION["usertable"]["usersitenumber"],$_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usernumber"]);
   ForceLoad("files.php");
}

if (isset($_FILES["sourcefile"]) && isset($_POST["Submit"]) && $_FILES["sourcefile"]["name"]!="") {
	if ($_POST["confirmation"] == "confirm") {
		$type=myhtmlspecialchars($_FILES["sourcefile"]["type"]);
		$size=myhtmlspecialchars($_FILES["sourcefile"]["size"]);
		$name=myhtmlspecialchars($_FILES["sourcefile"]["name"]);
		$temp=myhtmlspecialchars($_FILES["sourcefile"]["tmp_name"]);

		if ($size > $ct["contestmaxfilesize"]) {
	                LOGLevel("User {$_SESSION["usertable"]["username"]} tried to submit file " .
			"$name with $size bytes ({$ct["contestmaxfilesize"]} max allowed).", 1);
			MSGError("File size exceeds the limit allowed.");
			ForceLoad("run.php");
		}
		if (!is_uploaded_file($temp) || strlen($name)>100) {
			IntrusionNotify("file upload problem.");
			ForceLoad("../index.php");
		}

		DBNewBkp ($_SESSION["usertable"]["contestnumber"],
	                  $_SESSION["usertable"]["usersitenumber"],
	                  $_SESSION["usertable"]["usernumber"],
			  $name,
			  $temp, $size);
	}
	ForceLoad("files.php");
}
?>
<br>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Bkp #</b></td>
  <td><b>Time</b></td>
  <td><b>File</b></td>
 </tr>
<?php
$run = DBUserBkps($_SESSION["usertable"]["contestnumber"],
		  $_SESSION["usertable"]["usersitenumber"],
		  $_SESSION["usertable"]["usernumber"]);

for ($i=0; $i<count($run); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap><a href=\"javascript:conf2('files.php?delete=" . $run[$i]["number"] .
           "')\">" . $run[$i]["number"] . "</a></td>\n";

  echo "  <td nowrap>" . dateconv($run[$i]["timestamp"]) . "</td>\n";
  echo "<td nowrap><a href=\"../filedownload.php?". filedownload($run[$i]["oid"],$run[$i]["filename"]) . "\">";
  echo $run[$i]["filename"] . "</a>";

  echo "</td>\n";
  echo " </tr>\n";

}
echo "</table>";
if (count($run) == 0) echo "<br><center><b><font color=\"#ff0000\">NO BACKUPS AVAILABLE</font></b></center>";

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
    col_types: [
      'number', 'date', 'customstring'
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
    <?php if (count($run) != 0) { ?>
    no_results_message: {
      content: '<?php echo "<center><b><font color=\"#ff0000\">NO BACKUPS FOUND</font></b></center>" ?>',
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

<br><br><center><b>To erase a file, click on its number. To download a file, click on its name.
To submit a new backup file, just fill in the following fields:</b></center>
<form name="form1" enctype="multipart/form-data" method="post" action="files.php">
<!--<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $ct["contestmaxfilesize"] ?>">-->
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr> 
        <td width="35%" align=right>File (size restrictions apply):</td>
        <td width="65%">
	  <input type="file" name="sourcefile" size="40">
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm submission?")) {
        document.form1.confirmation.value='confirm';
      }
    }
    function conf2(url) {
      if (confirm("Confirm?")) {
        document.location=url;
      } else {
        document.location='files.php';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Send" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>

</body>
</html>
