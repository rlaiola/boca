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
$runviewphp='teamview.php';

if (isset($_FILES["sourcefile"]) && isset($_POST["problem"]) && isset($_POST["Submit"]) && isset($_POST["language"]) &&
    is_numeric($_POST["problem"]) && is_numeric($_POST["language"]) && $_FILES["sourcefile"]["name"]!="") {
	if ($_POST["confirmation"] == "confirm") {
		if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
			ForceLoad("../index.php");

		$prob = myhtmlspecialchars($_POST["problem"]);
		$lang = myhtmlspecialchars($_POST["language"]);

		$type=myhtmlspecialchars($_FILES["sourcefile"]["type"]);
		$size=myhtmlspecialchars($_FILES["sourcefile"]["size"]);
		$name=myhtmlspecialchars($_FILES["sourcefile"]["name"]);
		$temp=myhtmlspecialchars($_FILES["sourcefile"]["tmp_name"]);

		if ($size > $ct["contestmaxfilesize"]) {
	                LOGLevel("User {$_SESSION["usertable"]["username"]} tried to submit file " .
			"$name with $size bytes ({$ct["contestmaxfilesize"]} max allowed).", 1);
			MSGError("File size exceeds the limit allowed.");
			ForceLoad($runteam);
		}
		if(strpos($name,' ') === true || strpos($temp,' ') === true) {
			MSGError("File name cannot contain spaces.");
			ForceLoad($runteam);		
		}
		if (!is_uploaded_file($temp) || strlen($name)>100) {
			IntrusionNotify("file upload problem.");
			ForceLoad("../index.php");
		}


		//		$ac=array('contest','site','user','problem','lang','filename','filepath');
		//		$ac1=array('runnumber','rundate','rundatediff','rundatediffans','runanswer','runstatus','runjudge','runjudgesite',
		//			   'runjudge1','runjudgesite1','runanswer1','runjudge2','runjudgesite2','runanswer2',
		//			   'autoip','autobegindate','autoenddate','autoanswer','autostdout','autostderr','updatetime');
		$param = array('contest'=>$_SESSION["usertable"]["contestnumber"],
					   'site'=>$_SESSION["usertable"]["usersitenumber"],
					   'user'=>  $_SESSION["usertable"]["usernumber"],
					   'problem'=>$prob,
					   'lang'=>$lang,
					   'filename'=>$name,
					   'filepath'=>$temp);
		if($runteam=='team.php') $param['allowneg']=1;
		DBNewRun ($param);
		$_SESSION['forceredo']=true;
	}
	ForceLoad($runteam);
}

$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

$runtmp = $_SESSION["locr"] . $ds . "private" . $ds . "runtmp" . $ds . "run-contest" . $_SESSION["usertable"]["contestnumber"] . 
	"-site". $_SESSION["usertable"]["usersitenumber"] . "-user" . $_SESSION["usertable"]["usernumber"] . ".php";
$redo = TRUE;
if(!isset($_SESSION['forceredo']) || $_SESSION['forceredo']==false) {
	$actualdelay = 30;
	if(file_exists($runtmp)) {
		if(isset($strtmp) || (($strtmp = file_get_contents($runtmp,FALSE,NULL,0,1000000)) !== FALSE)) {
			list($d) = sscanf($strtmp,"%*s %d");
			if($d > time() - $actualdelay) {
				$conf=globalconf();
				$strtmp = decryptData(substr($strtmp,strpos($strtmp,"\n")+1),$conf["key"],'runtmp');
				if($strtmp !== false)
					$redo = FALSE;
			}
		}
	}
}
if($redo) {
	$_SESSION['forceredo']=false;
	if(($st = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
        ForceLoad("../index.php");
	$strtmp="<br>\n<table class=\"bocaTable\" width=\"100%\" border=1 style=\"width: 100%\">\n <tr>\n  <td><b>Run #</b></td>\n<td><b>Time</b></td>\n".
		"  <td><b>Problem</b></td>\n  <td><b>Language</b></td>\n  <td><b>Answer</b></td>\n  <td><b>File</b></td>\n </tr>\n";
	$strcolors = "0";
	$run = DBUserRuns($_SESSION["usertable"]["contestnumber"],
					  $_SESSION["usertable"]["usersitenumber"],
					  $_SESSION["usertable"]["usernumber"]);
	for ($i=0; $i<count($run); $i++) {
		$strtmp .= " <tr>\n";
		if (getenv("BOCA_ENABLE_VIEW_RUNS") == "true") {
			$strtmp .= "<td nowrap><a href=\"" . $runviewphp . "?runnumber=".$run[$i]["number"]."&runsitenumber=".$_SESSION["usertable"]["usersitenumber"] .
       "\">" . $run[$i]["number"] . "</a></td>\n";
		}
		else {
			$strtmp .= "  <td nowrap>" . $run[$i]["number"] . "</td>\n";
		}
		$strtmp .= "  <td nowrap>" . dateconvminutes($run[$i]["timestamp"]) . "</td>\n";
		$strtmp .= "  <td nowrap>" . $run[$i]["problem"] . "</td>\n";
		$strtmp .= "  <td nowrap>" . $run[$i]["language"] . "</td>\n";
//  $strtmp .= "  <td nowrap>" . $run[$i]["status"] . "</td>\n";
		if (trim($run[$i]["answer"]) == "") { 
			$run[$i]["answer"] = "Not answered yet";
			$strtmp .= "  <td>Not answered yet"; 
		}
		else {
			$strtmp .= "  <td>" . $run[$i]["answer"]; 
			if(false) {
				if(strpos($run[$i]["autoanswer"],"OKs") > 0)
					$strtmp .= ' ' . substr($run[$i]["autoanswer"],strrpos($run[$i]["autoanswer"],'('));
			}
			if($run[$i]['yes']=='t') {
				$strtmp .= " <img alt=\"".$run[$i]["colorname"]."\" width=\"15\" ".
					"src=\"" . balloonurl($run[$i]["color"]) ."\" />";
				$strcolors .= "\t" . $run[$i]["colorname"] . "\t" . $run[$i]["color"];
			}
		}
		$strtmp .= "</td>\n";
		$strtmp .= "<td nowrap><a href=\"../filedownload.php?" . filedownload($run[$i]["oid"],$run[$i]["filename"]) . "\">";
		$strtmp .= $run[$i]["filename"] . "</a>";
		
		$strtmp .= "</td>\n";
		
		$strtmp .= " </tr>\n";
		
		if ($run[$i]["anstime"]>$_SESSION["usertable"]["userlastlogin"]-$st["sitestartdate"] && $run[$i]["anstime"] < $st['sitelastmileanswer'] &&
			$run[$i]["ansfake"]!="t" && !isset($_SESSION["popups"]['run' . $i . '-' . $run[$i]["anstime"]])) {
			$_SESSION["popups"]['run' . $i . '-' . $run[$i]["anstime"]] = "Run ".$run[$i]["number"]." result: ".$run[$i]["answer"]. "<br>";
		}
	}
$strtmp .= "</table>";
if (count($run) == 0) $strtmp .= "<br><center><b><font color=\"#ff0000\">NO RUNS AVAILABLE</font></b></center>";

$strtmp .= "<div id=\"externalToolbar\"" . (count($run) == 0 ? " style=\"display: none\"" : "") . "></div>\n";

$strtmp .= "<br><br>";
if (getenv("BOCA_ENABLE_VIEW_RUNS") == "true") {
	$strtmp .= "<center><b>Click on the number of a run to visualize it.</b></center>";
}
$strtmp .="<center><b>To submit a program, just fill in the following fields:</b></center>\n".
"<form name=\"form1\" enctype=\"multipart/form-data\" method=\"post\" action=\"". $runteam ."\">\n".
"  <input type=hidden name=\"confirmation\" value=\"noconfirm\" />\n".
"  <center>\n".
"    <table border=\"0\">\n".
"      <tr> \n".
"        <td width=\"25%\" align=right>Problem:</td>\n".
"        <td width=\"75%\">\n".
"          <select name=\"problem\" onclick=\"Arquivo()\">\n";
$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usertype"]=='judge');
for ($i=0;$i<count($prob);$i++)
	$strtmp .= "<option value=\"" . $prob[$i]["number"] . "\">" . $prob[$i]["problem"] . "</option>\n";
$strtmp .= "	  </select>\n".
"        </td>\n".
"      </tr>\n".
"      <tr> \n".
"        <td width=\"25%\" align=right>Language:</td>\n".
"        <td width=\"75%\"> \n".
"          <select name=\"language\" onclick=\"Arquivo()\">\n";
$lang = DBGetLanguages($_SESSION["usertable"]["contestnumber"]);
for ($i=0;$i<count($lang);$i++)
	$strtmp .= "<option value=\"" . $lang[$i]["number"] . "\">" . $lang[$i]["name"] . "</option>\n";
$strtmp .= "	  </select>\n".
"        </td>\n".
"      </tr>\n".
"      <tr> \n".
"        <td width=\"25%\" align=right>Source code:</td>\n".
"        <td width=\"75%\">\n".
"	  <input type=\"file\" name=\"sourcefile\" size=\"40\" onclick=\"Arquivo()\">\n".
"        </td>\n".
"      </tr>\n".
"    </table>\n".
"  </center>\n".
"  <script language=\"javascript\">\n".
"    function conf() {\n".
"      if (confirm(\"Confirm submission?\")) {\n".
"        document.form1.confirmation.value='confirm';\n".
"      }\n".
"    }\n".
"  </script>\n".
"  <center>\n".
"      <input type=\"submit\" name=\"Submit\" value=\"Send\" onClick=\"conf()\">\n".
"      <input type=\"reset\" name=\"Submit2\" value=\"Clear\">\n".
"  </center>\n".
"</form>\n";
    $conf=globalconf();
    $strtmp1 = "<!-- " . time() . " --> <?php exit; ?>\t" . encryptData($strcolors,$conf["key"],false) . "\n" . encryptData($strtmp,$conf["key"],false);
	$randnum = session_id() . "_" . rand();
	if(file_put_contents($runtmp . "_" . $randnum, $strtmp1,LOCK_EX)===FALSE) {
		if(!isset($_SESSION['writewarn'])) {
			LOGError("Cannot write to the user-run cache file $runtmp -- performance might be compromised");
			$_SESSION['writewarn']=true;
		}
	}
	@rename($runtmp . "_" . $randnum, $runtmp);
}
echo $strtmp;
?>
<br>
<?php
// Check if the table filter should be enabled
if (getenv("BOCA_ENABLE_TABLE_FILTER") == "true") {
?>
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
		'8%', '7%', '20%',
		'15%', '30%', '20%'
		],
		col_types: [
		'number', 'number', 'customstring',
		'customstring', 'customstring', 'customstring'
		],
		/* cell_parser delegate used for filtering images in a column */
		cell_parser: {
		cols: [2, 3],
		parse: function(o, cell, colIndex) {
			var txt = cell.textContent || cell.innerText;
			return txt;
		}
		},
		col_2: 'select',
		col_3: 'select',
		col_4: 'select',
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
