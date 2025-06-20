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
// Last modified 08/aug/2015 by cassio@ime.usp.br
if (!isset($_POST["confirmation"]) || $_POST["confirmation"] != "confirm")
	unset($_POST['noflush']);

require('header.php');
if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");

if (isset($_GET["delete"]) && is_numeric($_GET["delete"]) && isset($_GET["input"])) {
	$param = array();
	$param['number']=$_GET["delete"];
	$param['inputfilename']=myrawurldecode($_GET["input"]);
	if(!DBDeleteProblem ($_SESSION["usertable"]["contestnumber"], $param)) {
		MSGError('Error deleting problem');
		LogError('Error deleting problem');
	}
	ForceLoad("problem.php");
}

if(isset($_POST['Submit5']) && $_POST['Submit5']=='Build')
	ForceLoad("buildproblem.php");

if(isset($_POST['Submit5']) && $_POST['Submit5']=='Send') {
	if(isset($_POST['basename']) &&
	   isset($_POST['fullname']) &&
	   isset($_POST['timelimit']) &&
	   $_POST["confirmation"] == "confirm") {
		if ($_FILES["probleminput"]["name"] != "") {
			$type=myhtmlspecialchars($_FILES["probleminput"]["type"]);
			$size=myhtmlspecialchars($_FILES["probleminput"]["size"]);
			$name=myhtmlspecialchars($_FILES["probleminput"]["name"]);
			$temp=myhtmlspecialchars($_FILES["probleminput"]["tmp_name"]);
			if (!is_uploaded_file($temp)) {
				ob_end_flush();
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name = "";
		if ($_FILES["problemsol"]["name"] != "") {
			$type1=myhtmlspecialchars($_FILES["problemsol"]["type"]);
			$size1=myhtmlspecialchars($_FILES["problemsol"]["size"]);
			$name1=myhtmlspecialchars($_FILES["problemsol"]["name"]);
			$temp1=myhtmlspecialchars($_FILES["problemsol"]["tmp_name"]);
			if (!is_uploaded_file($temp1)) {
				ob_end_flush();
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name1 = "";
		if (isset($_FILES["problemdesc"]) && $_FILES["problemdesc"]["name"] != "") {
			$type2=myhtmlspecialchars($_FILES["problemdesc"]["type"]);
			$size2=myhtmlspecialchars($_FILES["problemdesc"]["size"]);
			$name2=myhtmlspecialchars($_FILES["problemdesc"]["name"]);
			$temp2=myhtmlspecialchars($_FILES["problemdesc"]["tmp_name"]);
			if (!is_uploaded_file($temp2)) {
				ob_end_flush();
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name2 = "";

		$ds = DIRECTORY_SEPARATOR;
		if($ds=="") $ds = "/";
		$tmpdir = getenv("TMP");
		if($tmpdir=="") $tmpdir = getenv("TMPDIR");
		if($tmpdir[0] != $ds) $tmdir = $ds . "tmp";
		if($tmpdir=="") $tmpdir = $ds . "tmp";
		$locr = $_SESSION["locr"];
		$tfile = tempnam($tmpdir, "problem");
		if(@mkdir($tfile . "_d", 0700)) {
			$dir = $tfile . "_d";
			@mkdir($dir . $ds . 'limits');
			@mkdir($dir . $ds . 'compare');
			@mkdir($dir . $ds . 'compile');
			@mkdir($dir . $ds . 'run');
			@mkdir($dir . $ds . 'input');
			@mkdir($dir . $ds . 'output');
			@mkdir($dir . $ds . 'tests');
			@mkdir($dir . $ds . 'description');
			$filea = array('compare' . $ds . 'c','compare' . $ds . 'cc','compare' . $ds . 'java','compare' . $ds . 'py2','compare' . $ds . 'py3',
						   'compile' . $ds . 'c','compile' . $ds . 'cc','compile' . $ds . 'java','compile' . $ds . 'py2','compile' . $ds . 'py3',
						   'run' . $ds . 'c','run' . $ds . 'cc','run' . $ds . 'java','run' . $ds . 'py2','run' . $ds . 'py3');
			foreach($filea as $file) {
				$rfile=$locr . $ds . '..' . $ds . 'doc' . $ds . 'problemexamples' . $ds . 'problemtemplate' . $ds . $file;
				if(is_readable($rfile)) {
					@copy($rfile, $dir . $ds . $file);
				} else {
					@unlink($tfile);
					cleardir($dir);
					ob_end_flush();
					MSGError('Could not read problem template file ' . $rfile);
					ForceLoad('problem.php');
				}
			}
			$tl = explode(',',$_POST['timelimit']);
			if(!isset($tl[1]) || !is_numeric(trim($tl[1]))) $tl[1]='1';
			$str = "echo " . trim($tl[0]) . "\necho " . trim($tl[1]) . "\necho 512\necho " . floor(10 + $size1 / 512) . "\nexit 0\n";
			file_put_contents($dir . $ds . 'limits' . $ds . 'c',$str);
			file_put_contents($dir . $ds . 'limits' . $ds . 'cc',$str);
			file_put_contents($dir . $ds . 'limits' . $ds . 'java',$str);
            file_put_contents($dir . $ds . 'limits' . $ds . 'py2',$str);
            file_put_contents($dir . $ds . 'limits' . $ds . 'py3',$str);
			$str = "basename=" . trim($_POST['basename']) . "\nfullname=" . trim($_POST['fullname']);
			if($name2) {
				@copy($temp2, $dir . $ds . 'description' . $ds . $name2);
				@unlink($temp2);
				$str .= "\ndescfile=" . $name2;
			}
			$str .= "\n";
			file_put_contents($dir . $ds . 'description' . $ds . 'problem.info',$str);
			if($name && $name1) {
				@copy($temp, $dir . $ds . 'input' . $ds . 'file1');
				@unlink($temp);
				@copy($temp1, $dir . $ds . 'output' . $ds . 'file1');
				@unlink($temp1);
			} else {
				@unlink($tfile);
				cleardir($dir);
				ob_end_flush();
				MSGError('Could not read problem input/output files');
				ForceLoad('problem.php');
			}
			$ret=create_zip($dir, glob($dir . $ds . '*'),$dir . '.zip');
			cleardir($dir);
			if($ret <= 0) {
				@unlink($tfile);
				@unlink($dir . '.zip');
				ob_end_flush();
				MSGError('Could not write to zip file');
				ForceLoad('problem.php');
			}
			$str = file_get_contents($dir . '.zip');
			@unlink($dir . '.zip');
			@unlink($tfile);
			header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
			header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header ("Cache-Control: no-cache, must-revalidate");
			header ("Pragma: no-cache");
			header ("Content-transfer-encoding: binary\n");
			header ("Content-type: application/force-download");
			header ("Content-Disposition: attachment; filename=" . basename($dir . '.zip'));
			ob_end_flush();
			echo $str;
			exit;
		} else {
			@unlink($tfile);
			ob_end_flush();
			MSGError('Could not write to temporary directory');
		}
	}
	ForceLoad('problem.php');
}

if (isset($_POST["Submit3"]) && isset($_POST["problemnumber"]) && is_numeric($_POST["problemnumber"]) && 
    isset($_POST["problemname"]) && $_POST["problemname"] != "") {
	if(strpos(trim($_POST["problemname"]),' ')!==false) {
		$_POST["confirmation"]='';
		MSGError('Problem short name cannot have spaces');
	} else {
	if ($_POST["confirmation"] == "confirm") {
		if ($_FILES["probleminput"]["name"] != "") {
			$type=myhtmlspecialchars($_FILES["probleminput"]["type"]);
			$size=myhtmlspecialchars($_FILES["probleminput"]["size"]);
			$name=myhtmlspecialchars($_FILES["probleminput"]["name"]);
			$temp=myhtmlspecialchars($_FILES["probleminput"]["tmp_name"]);
			if (!is_uploaded_file($temp)) {
				IntrusionNotify("file upload problem.");
				ForceLoad("../index.php");
			}
		} else $name = "";

		$param = array();
		$param['number'] = $_POST["problemnumber"];
		$param['name'] = trim($_POST["problemname"]);
		$param['inputfilename'] = $name;
		$param['inputfilepath'] = $temp;
		$param['fake'] = 'f';
		$param['colorname'] = trim($_POST["colorname"]);
		$param['color'] = trim($_POST["color"]);
		$autojudge_value = 0;
		if (isset ($_POST["autojudge_new_sel"]) && in_array ($_POST['autojudge_new_sel'], array ('all', 'custom', 'none'))) {
			$all_answers = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
			for ($g = 0; $g < count ($all_answers); $g++) {
				if ($all_answers[$g]['fake'] == 't') continue;
				$campo = 'autojudge_chc_new_'.$all_answers[$g]['number'];
				
				if ($_POST['autojudge_new_sel'] == 'all') {
					$autojudge_value |= pow (2, $g);
				} else if ($_POST['autojudge_new_sel'] == 'custom' && isset ($_POST[$campo]) && $_POST[$campo] == "1") {
					$autojudge_value |= pow (2, $g);
				}
			}
		}
		$param['autojudge'] = $autojudge_value;
		DBNewProblem ($_SESSION["usertable"]["contestnumber"], $param);
	}
	}
	ForceLoad("problem.php");
}

$prob = DBGetFullProblemData($_SESSION["usertable"]["contestnumber"],true);
for ($i=0; $i<count($prob); $i++) {
  if($prob[$i]["fake"]!='t') {
    if (isset($_POST["SubmitProblem" . $prob[$i]['number']]) && $_POST["SubmitProblem" . $prob[$i]['number']] == 'Update' &&
	isset($_POST["colorname" . $prob[$i]['number']]) && strlen($_POST["colorname" . $prob[$i]['number']]) <= 100 && 
	isset($_POST["color" . $prob[$i]['number']]) && strlen($_POST["color" . $prob[$i]['number']]) <= 6 && 
	isset($_POST["problemname" . $prob[$i]['number']]) && $_POST["problemname" . $prob[$i]['number']] != "" && strlen($_POST["problemname" . $prob[$i]['number']]) <= 50) {
      if(strpos(trim($_POST["problemname" . $prob[$i]['number']]),' ')!==false) {
	MSGError('Problem short name cannot have spaces');
      } else {
	$param = array();
	$param['number'] = $prob[$i]['number'];
	$param['name'] = trim($_POST["problemname" . $prob[$i]['number']]);
	$param['fake'] = 'f';
	$param['colorname'] = trim($_POST["colorname" . $prob[$i]['number']]);
	$param['color'] = trim($_POST["color" . $prob[$i]['number']]);
	DBNewProblem ($_SESSION["usertable"]["contestnumber"], $param);
      }
      ForceLoad("problem.php");
    }
  }
}

// Update AutoJudge Setting
for ($i=0; $i<count($prob); $i++) {
	if($prob[$i]["fake"]=='t') continue;
	
	$sel_name = "autojudge_" . $prob[$i]['number']. "_sel";
	if (isset($_POST["SubmitProblemAJ" . $prob[$i]['number']]) && $_POST["SubmitProblemAJ" . $prob[$i]['number']] == 'Update' && isset ($_POST[$sel_name]) && in_array ($_POST[$sel_name], array ('all', 'custom', 'none'))) {
		$all_answers = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
		$value = 0;
		for ($g = 0; $g < count ($all_answers); $g++) {
			if ($all_answers[$g]['fake'] == 't') continue;
			$campo = 'autojudge_chc_'.$prob[$i]['number'].'_'.$all_answers[$g]['number'];
			
			if ($_POST[$sel_name] == 'all') {
				$value |= pow (2, $g);
			} else if ($_POST[$sel_name] == 'custom' && isset ($_POST[$campo]) && $_POST[$campo] == "1") {
				$value |= pow (2, $g);
			}
		}
		$param = array();
		$param['number'] = $prob[$i]['number'];
		$param['name'] = trim($prob[$i]['name']);
		$param['fake'] = 'f';
		$param['colorname'] = trim($prob[$i]['colorname']);
		$param['color'] = trim($prob[$i]['color']);
		$param['autojudge'] = ((integer) $value);
		DBNewProblem ($_SESSION["usertable"]["contestnumber"], $param);
		ForceLoad("problem.php");
	}
}

require_once("../freport.php");
$d = DBRunReport(
  $_SESSION["usertable"]["contestnumber"],
  $_SESSION["usertable"]["usersitenumber"]
);


function exponentialWeight($n, $lambda = 0.5) {
	return exp(-$lambda * ($n - 1));
}

function sigmoidWeight($n, $c = 3) {
	return 1 / (1 + exp($n - $c));
}

function median($values) {
	// Sort the array in ascending order
	sort($values);

	$count = count($values);
	$middle = floor($count / 2);

	if ($count % 2) {
		// Odd count: return the middle value
		return $values[$middle];
	} else {
		// Even count: return the average of the two middle values
		return ($values[$middle - 1] + $values[$middle]) / 2;
	}
}

function average($values) {
	// Check if the array is empty to avoid division by zero
	if (empty($values)) {
		return 0; // Return 0 or null as the average for an empty array
	}

	// Calculate the sum of the array elements
	$sum = array_sum($values);

	// Calculate the count of the array elements
	$count = count($values);

	// Return the average
	return $sum / $count;
}

function resubmissionRate($submissions) {
	// Check if the array is empty to avoid division by zero
	if (empty($submissions)) {
		return 0; // Return 0 for an empty array
	}

	// Count users with more than one submission
	$resubmittingUsers = array_filter($submissions, function ($count) {
		return $count > 1;
	});

	// Total number of users
	$totalUsers = count($submissions);

	// Number of users who re-submitted
	$resubmittingCount = count($resubmittingUsers);

	// Calculate the re-submission rate
	return ($resubmittingCount / $totalUsers) * 100;
}

$usr = DBAllUserInfo($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"]);
// print_r($usr);
$total_users = count($usr); // Total number of users in the site

list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
                        $_SESSION["usertable"]["usersitenumber"], 1, -1);
// print_r(array_keys($score));
$userkey=$_SESSION["usertable"]["usernumber"] . "-" . $_SESSION["usertable"]["usersitenumber"];
// echo $userkey;
$solvedproblems = $score[$userkey]["problem"];
// echo print_r($score);
?>
<br>
  <script language="javascript">
    function conf2(url) {
      if (confirm("Confirm the DELETION of the PROBLEM and ALL data associated to it?")) {
		  if (confirm("Are you REALLY sure about what you are doing? DATA CANNOT BE RECOVERED!")) {
			  document.location=url;
		  } else {
			  document.location='problem.php';
		  }
      } else {
        document.location='problem.php';
      }
    }
    function conf3(url) {
      if (confirm("Confirm the UNDELETION of the PROBLEM?")) {
		  document.location=url;
	  } else {
		  document.location='problem.php';
	  }
    }
  </script>
<form name="form0" enctype="multipart/form-data" method="post" action="problem.php" style="margin: 0">
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Problem #</b></td>
  <td><b>Short Name</b></td>
  <td><b><?php if (getenv("BOCA_ENABLE_PROBLEM_TAGS") == "true") echo "Tags"; else echo "Fullname"; ?></b></td>
  <td><b><?php if (getenv("BOCA_ENABLE_PROBLEM_STATS") == "true") echo "Statistics"; else echo "Basename"; ?></b></td>
  <td><b>Descfile</b></td>
  <td><b>Package file</b></td>
<!--  <td><b>Compare file</b></td>
  <td><b>Timelimit</b></td>-->
  <td><b>Color</b></td>
  <td><b>AutoJudge Setting</b></td>
 </tr>
<?php
$all_answers = DBGetAnswers($_SESSION["usertable"]["contestnumber"]);
for ($i=0; $i<count($prob); $i++) {
  $problemnumber = $prob[$i]["number"];
  $problemname = $prob[$i]["problem"];
  // echo $problemnumber . " " . $problemname;

  $userSubmissions = array();         // Number of submissions by users
  $userAcceptedSubmissions = array(); // Number of submissions accepted by users
  $N = 0;                             // Number of users who attempted the problem
  $NAccepted = 0;                     // Number of users who solved the problem
  // Weighted Acceptance Rate
  // - Last Submission Logic: Only the weight of the last submission is considered for the numerator if it is successful.
  // - All Submissions in Denominator: All submissions (accepted or not) contribute to the denominator, penalizing users with excessive attempts.
  // - Emphasizes Early Attempts: Using sigmoid or exponential weighting ensures early submissions have higher weights.
  $totalWeightedAccepted = 0;
  $totalWeightedSubmissions = 0;
  foreach($score as $usersite => $userdata) {
    $Si = 0;  // Total number of submissions by the ith user for the problem

    if ($userdata["problem"][$problemnumber]["count"] > 0) {
      $N++;
      $Si = $userdata["problem"][$problemnumber]["count"];
      array_push($userSubmissions, $Si);

      // Aggressively penalize excessive submissions and heavily emphasize early attempts
      for ($j = 1; $j <= $Si; $j++) {
        // Weight applied to the jth submission of the ith user
        $wij = exponentialWeight($j);;
        // $wij = sigmoidWeight($j);
        // Add weight for all submissions to the denominator
        $totalWeightedSubmissions += $wij;
      }

      // Check if the last submission is successful
      if ($userdata["problem"][$problemnumber]["solved"] > 0) {
        $NAccepted++;
        array_push($userAcceptedSubmissions, $Si);

        // Add weight for accepted run of the ith user in the jth submission
        $totalWeightedAccepted += exponentialWeight($Si);
        // $totalWeightedAccepted += sigmoidWeight($Si);
      }
    }
  }

  // Calculate solve rate
  $successRate = $N > 0 ?
  	($NAccepted / $N) * 100 : 0;
  // Calculate weighted acceptance rate
  $weightedAcceptanceRate = $totalWeightedSubmissions > 0 ?
  	($totalWeightedAccepted / $totalWeightedSubmissions) * 100 : 0;
  // echo "Weighted Acceptance Rate: " . number_format($weightedAcceptanceRate, 2) . "%\n";
  // Calculate engagement rate
  $engagementRate = $total_users > 0 ?
  	($N / $total_users) * 100 : 0;

  echo " <tr>\n";
  if($prob[$i]["fake"]!='t') {
	  if(strpos($prob[$i]["fullname"],"(DEL)") !== false) {
		  echo "  <td><a href=\"javascript: conf3('problem.php?delete=" . $prob[$i]["number"] . "&input=" . myrawurlencode($prob[$i]["inputfilename"]) . 
			  "')\">" . $prob[$i]["number"];
		  echo "(deleted)";
	  } else {
		  echo "  <td><a href=\"javascript: conf2('problem.php?delete=" . $prob[$i]["number"] . "&input=" . myrawurlencode($prob[$i]["inputfilename"]) . 
			  "')\">" . $prob[$i]["number"];
	  }
	  echo "</a></td>\n";
	  echo "<input type=hidden name=\"problemname" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["name"] . "\" />";
	  echo "  <td nowrap>" . $prob[$i]["name"] . "</td>\n";
	  //echo "  <td nowrap>";
	  //echo "<input type=\"text\" name=\"problemname" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["name"] . "\" size=\"4\" maxlength=\"50\" />";
	  //echo "</td>\n";
  } else {
    echo "  <td nowrap>" . $prob[$i]["number"] . " (fake)</td>\n";
    echo "  <td nowrap>" . $prob[$i]["name"] . "</td>\n";
  }

  // Check whether fullname should be formatted as tags
  if (getenv("BOCA_ENABLE_PROBLEM_TAGS") == "true") {
    // Input string
    $input = $prob[$i]["fullname"];
    // Replace the substring if it exists
    $input = str_replace("(DEL)", "", $input);
    // Remove brackets from the input string
    $trimmed = trim($input, "[]");
    // Split the string into individual components
    $tags = explode(",", $trimmed);
    // Initialize an array to group tags by key
    $groupedTags = [];

    // Group tags by their key
    foreach ($tags as $tag) {
      if (strpos($tag, "?") !== false) {
        list($key, $value) = explode("?", ltrim($tag, "#"), 2);
        $groupedTags[$key][] = "<span class='tag' data-key='$key'>$value</span>";
      }
    }

    echo "  <td nowrap>";
    // Output grouped tags
    foreach ($groupedTags as $key => $htmlTags) {
      echo "<div class='tag-group' data-group='$key'>";
      // echo "<h4>" . ucfirst($key) . "</h4>"; // Optional header for each group
      echo implode("\n", $htmlTags);
      echo "</div>\n";
    }
    echo "</td>\n";
  } else {
    echo "  <td nowrap>" . $prob[$i]["fullname"] . "&nbsp;</td>\n";
  }

  if ($prob[$i]["fake"]!='t' && getenv('BOCA_ENABLE_PROBLEM_STATS') == 'true') {
    echo "  <td nowrap>";
    echo "    <div class='tags'>";
    //
    echo "      <div class='tag-group' data-group='stat'>";
    echo "        <span class='tag' data-key='stat'>User engagement";
    echo "          <sup class='tooltip' title='Percentage of all platform users who attempted the problem.'>(?)</sup>";
    echo ": " . number_format($engagementRate, 2) . "%"; ;
         "        </span>";
    echo "      </div>";
    // Success Rate: Percentage of users who solved the problem
    echo "      <div class='tag-group' data-group='stat'>";
    echo "        <span class='tag' data-key='stat'>Success rate";
    echo "          <sup class='tooltip' title='Percentage of users who attempted and solved the problem.'>(?)</sup>";
    echo ": " . number_format($successRate, 2) . "%";
         "        </span>";
    echo "      </div>";
    // Weighted Success Rate: Weighted percentage of successful runs
    echo "      <div class='tag-group' data-group='stat'>";
    echo "        <span class='tag' data-key='stat'>Weighted success rate";
    echo "          <sup class='tooltip' title='Weighted percentage of successful runs for the problem. Emphasizes early attempts.'>(?)</sup>";
    echo ": " . number_format($weightedAcceptanceRate, 2) . "%";
    echo "        </span>";
    echo "      </div>";
    // Median Successful Runs: Median runs for users who passed
    echo "      <div class='tag-group' data-group='stat'>";
    echo "        <span class='tag' data-key='stat'>Median attempts-to-success";
    echo "          <sup class='tooltip' title='Median number of submissions made by users who solved the problem.'>(?)</sup>";
    echo ": " . median($userAcceptedSubmissions);
    echo "        </span>";
    echo "      </div>";
    // Average Successful Runs: Average runs for users who passed
    echo "      <div class='tag-group' data-group='stat'>";
    echo "        <span class='tag' data-key='stat'>Avg. attempts-to-success";
    echo "          <sup class='tooltip' title='Average number of submissions made by users who eventually solved the problem.'>(?)</sup>";
    echo ": " . number_format(average($userAcceptedSubmissions), 2);
    echo "        </span>";
    echo "      </div>";
    // Re-submission Rate: Percentage of users who re-submitted
    echo "      <div class='tag-group' data-group='stat'>";
    echo "        <span class='tag' data-key='stat'>Re-submission rate";
    echo "          <sup class='tooltip' title='Percentage of users with multiple submissions for the problem.'>(?)</sup>";
    echo ": " . number_format(resubmissionRate($userSubmissions), 2) . "%";
    echo "        </span>";
    echo "      </div>";
    echo "    </div>";
    echo "  </td>\n";
  }
  else {
    echo "  <td nowrap>" . $prob[$i]["basefilename"] . "&nbsp;</td>\n";
  }

  if (isset($prob[$i]["descoid"]) && $prob[$i]["descoid"] != null && isset($prob[$i]["descfilename"])) {
	  echo "  <td nowrap><a target=\"_blank\" href=\"../filedownload.php?" . filedownload($prob[$i]["descoid"], $prob[$i]["descfilename"]) . "\">" . 
		  basename($prob[$i]["descfilename"]) . "</td>\n";
  }
  else
    echo "  <td>&nbsp;</td>\n";
  if ($prob[$i]["inputoid"] != null) {
    $tx = $prob[$i]["inputhash"];
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["inputoid"] ,$prob[$i]["inputfilename"]) ."\">" .
		$prob[$i]["inputfilename"] . "</a> " . 
		"<img title=\"hash: $tx\" alt=\"$tx\" width=\"25\" src=\"../images/bigballoontransp-hash.png\" />" . 
        "</td>\n";
  }
  else
    echo "  <td nowrap>&nbsp;</td>\n";
/*
  if ($prob[$i]["soloid"] != null) {
    $tx = $prob[$i]["solhash"];
    echo "  <td nowrap><a href=\"../filedownload.php?" . filedownload($prob[$i]["soloid"],$prob[$i]["solfilename"]) ."\">" . 
	$prob[$i]["solfilename"] . "</a> ".
	"<img title=\"hash: $tx\" alt=\"$tx\" width=\"25\" src=\"../images/bigballoontransp-hash.png\" />" . 
	"</td>\n";
  }
  else
    echo "  <td nowrap>&nbsp;</td>\n";
  if ($prob[$i]["timelimit"]!="")
    echo "  <td nowrap>" . $prob[$i]["timelimit"] . "</td>\n";
  else
    echo "  <td nowrap>&nbsp;</td>\n";
*/
  echo "  <td nowrap>";
  if($prob[$i]["fake"]!='t') {
    if ($prob[$i]["color"]!="") {
      echo "<img title=\"".$prob[$i]["color"]."\" alt=\"".$prob[$i]["colorname"]."\" width=\"25\" src=\"" . 
	balloonurl($prob[$i]["color"]) . "\" />\n";
    }
    echo "<input type=\"text\" name=\"colorname" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["colorname"] . "\" size=\"10\" maxlength=\"100\" />";
    echo "<input type=\"text\" name=\"color" . $prob[$i]['number'] . "\" value=\"" . $prob[$i]["color"]. "\" size=\"6\" maxlength=\"6\" />";
    echo "<input type=\"submit\" name=\"SubmitProblem" . $prob[$i]["number"] . "\" value=\"Update\">";
  } else echo "&nbsp;";
  echo "</td>\n";
  
  // Print the autojudge setting with a INPUT SELECT BOX + small boxes
  echo "  <td nowrap>";
  if($prob[$i]["fake"]!='t') {
	$autojudge_int_val = ((integer) $prob[$i]['autojudge']);
	$sel_name = "autojudge_" . $prob[$i]['number']. "_sel";
	printf ('<select id="%s" name="%s">', $sel_name, $sel_name);
	
	$all_mask = 0;
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		$all_mask |= pow (2, $g);
	}
	if ($autojudge_int_val == $all_mask) {
		$dis = true;
		$dis_value = true;
		$sel_value = 'all';
	} else if ($autojudge_int_val == 0) {
		$dis = true;
		$dis_value = false;
		$sel_value = 'none';
	} else {
		$dis = false;
		$sel_value = 'custom';
	}
	
	$opts = array ('Everything' => 'all', 'Custom' => 'custom', 'None' => 'none');
	foreach ($opts as $display_name => $opt) {
		if ($opt == $sel_value) {
			printf ('<option value="%s" selected="selected">%s</option>', $opt, $display_name);
		} else {
			printf ('<option value="%s">%s</option>', $opt, $display_name);
		}
	}
	
	echo "</select><br />\n";
	
	echo "<table><tr>\n";
	
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		echo "<td>\n";
		printf ('<input type="checkbox" id=autojudge_chc_%s_%s name="autojudge_chc_%s_%s" value="1" ', $i, $g, $i, $g);
		if ($sel_value == 'all') {
			printf ('disabled="disabled" checked="checked" />');
		} else if ($sel_value == 'none') {
			printf ('disabled="disabled" />');
		} else {
			$mask = pow (2, $all_answers[$g]['number']);
			if (($autojudge_int_val & $mask) == $mask) {
				printf ('checked="checked" />');
			} else {
				printf (' />');
			}
		}
		echo "\n</td>\n";
	}
	echo "</tr><tr>\n";
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		printf ('<td><label for="autojudge_chc_%s_%s">', $i, $g);
		printf ('<abbr title="%s">%s</abbr>', $all_answers[$g]['desc'], $all_answers[$g]['short']);
		echo "</label></td>\n";
	}
	echo "</tr></table>\n";
	?>
	<script type="text/javascript">
		function <?php echo "f_change_".$sel_name; ?> () {
			var sel = document.getElementById("<?php echo $sel_name; ?>");
			var v = sel.value;
			var max_ans = <?php echo count($all_answers) ?>;
			var dis = false;
			if (v == "all") {
				dis = true;
				sel = true;
			} else if (v == "none") {
				dis = true;
				sel = false;
			}
			
			for (g = 1; g < max_ans; g++) {
				var c = document.getElementById ("<?php echo 'autojudge_chc_'.$prob[$i]['number'].'_';?>" + g);
				if (dis == true) {
					c.checked = sel;
					c.disabled = true;
				} else {
					c.disabled = false;
				}
			}
		}
		var sel = document.getElementById("<?php echo $sel_name; ?>");
		sel.onchange = <?php echo "f_change_".$sel_name; ?>;
		<?php echo "f_change_".$sel_name; ?> ();
	</script>
<?php
    echo "<input type=\"submit\" name=\"SubmitProblemAJ" . $prob[$i]["number"] . "\" value=\"Update\">";
  } else echo "&nbsp;";
  echo "</td>\n";
  
  echo " </tr>\n";
}
echo "</table></form>";
if (count($prob) == 0) echo "<br><center><b><font color=\"#ff0000\">NO PROBLEMS DEFINED</font></b></center>";

?>
<?php
// Check if the table filter should be enabled
if (getenv("BOCA_ENABLE_TABLE_FILTER") == "true") {
?>
<div id="externalToolbar" <?php if (count($prob) == 0) echo "style=\"display: none\""; ?>></div>
<style>
  td {
    word-wrap: break-word;
  }

  div.grd_headTblCont table thead tr td,
  table.bocaTable > tbody > tr > td {
    position: sticky;
    z-index: 1;
  }

  div.grd_headTblCont table thead tr td:nth-child(-n + 2),
  table.bocaTable > tbody > tr > td:nth-child(-n + 3) {
    position: sticky;
    left: 0;
    z-index: 2;
    background-color: #e0e0d0;
  }

  /* Fake row */
  table.bocaTable > tbody > tr:nth-child(1) > td:nth-child(3) {
    z-index: 1;
  }

  div.grd_headTblCont table thead tr td:nth-child(2),
  table.bocaTable > tbody > tr > td:nth-child(3),
  table.bocaTable > tbody > tr:nth-child(1) > td:nth-child(2) {
    left: 152px;
  }
</style>
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
      '150px', '215px', '275px',
      '300px', '250px', '250px',
      '275px', '300px'
    ],
    col_types: [
      'number', 'customstring', 'customstring',
      'customstring', 'customstring', 'customstring',
      'none', 'none'
    ],
    col_6: 'none',
    col_7: 'none',
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
		<?php if (getenv("BOCA_ENABLE_PROBLEM_STATS") != "true") { ?>
        // hide column Basename
        at_start: [3],
		<?php } ?>
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

<br><br><center><b>Clicking on a problem number will delete it.<br>
WARNING: deleting a problem will remove EVERYTHING related to it.<br>
It is NOT recommended to change anything while the contest is running.<br>
To import a problem, fill in the following fields.<br>
To replace the data of a problem, proceed as if it did not exist (data will be replaced without removing it).</b></center>

<form name="form1" enctype="multipart/form-data" method="post" action="problem.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf() {
			if(document.form1.problemname.value=="") {
				alert('Sorry, mandatory fields are empty');
			} else {
/*
				var s1 = String(document.form1.problemdesc.value);
				var l = s1.length;
				if(l >= 3 && (s1.substr(l-3,3).toUpperCase()==".IN" ||
							 s1.substr(l-4,4).toUpperCase()==".OUT" ||
							 s1.substr(l-4,4).toUpperCase()==".SOL" ||
							 s1.substr(l-2,2).toUpperCase()==".C" ||
							 s1.substr(l-2,2).toUpperCase()==".H" ||
							 s1.substr(l-3,3).toUpperCase()==".CC" ||
							 s1.substr(l-3,3).toUpperCase()==".GZ" ||
							 s1.substr(l-4,4).toUpperCase()==".CPP" ||
							 s1.substr(l-4,4).toUpperCase()==".HPP" ||
							 s1.substr(l-4,4).toUpperCase()==".ZIP" ||
							 s1.substr(l-4,4).toUpperCase()==".TGZ" ||
							 s1.substr(l-5,5).toUpperCase()==".JAVA")) {
					alert('Description file has invalid extension: ...'+s1.substr(l-3,3));
				} else {
*/
				var s2 = String(document.form1.probleminput.value);
				if(s2.length > 4) {
					if (confirm("Confirm?")) {
						document.form1.confirmation.value='confirm';
					}
				} else {
					alert('File package must be given');
				}
			}
    }
  </script>
  <center>
    <table border="0">
      <tr>
        <td width="35%" align=right>Number:</td>
        <td width="65%">
          <input type="text" name="problemnumber" value="" size="20" maxlength="20" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Short Name (usually a letter, no spaces):</td>
        <td width="65%">
          <input type="text" name="problemname" value="" size="20" maxlength="50" />
        </td>
      </tr>
<!--
      <tr>
        <td width="35%" align=right>Problem Fullname:</td>
        <td width="65%">
          <input type="text" name="fullname" value="" size="50" maxlength="100" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Problem Basename (a.k.a. name of class expected to have the main):</td>
        <td width="65%">
          <input type="text" name="basename" value="" size="50" maxlength="100" />
        </td>
      </tr>
      <tr>
	 <td width="35%" align=right>Description file (PDF, txt, ...):</td>
        <td width="65%">
          <input type="file" name="problemdesc" value="" size="40" />
        </td>
      </tr>
-->
      <tr>
	 <td width="35%" align=right>Problem package (ZIP):</td>
        <td width="65%">
          <input type="file" name="probleminput" value="" size="40" />
        </td>
      </tr>
<!--
      <tr>
	 <td width="35%" align=right>Compare file archive (ZIP):</td>
        <td width="65%">
          <input type="file" name="problemsol" value="" size="40" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Timelimit (in sec):</td>
        <td width="65%">
          <input type="text" name="timelimit" value="" size="10" />
(optional: use a , followed by the number of repetitions to run)
        </td>
      </tr>
-->
      <tr>
        <td width="35%" align=right>Color name:</td>
        <td width="65%">
          <input type="text" name="colorname" value="" size="40" maxlength="100" />
        </td>
      </tr>
      <tr>
        <td width="35%" align=right>Color (RGB HTML format):</td>
        <td width="65%">
          <input type="text" name="color" value="" size="6" maxlength="6" />
        </td>
      </tr>
      <tr>
      	<td width="35%" align=right>Autojudge Setting:</td>
        <td width="65%">
          <select name="autojudge_new_sel" id="autojudge_new_sel">
          	<option value="all" selected="selected">Everything</option>
          	<option value="custom">Custom</option>
          	<option value="none">None</option>
          </select>
          <table><tr>
<?php
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		echo "<td>\n";
		printf ('<input type="checkbox" id=autojudge_chc_new_%s name="autojudge_chc_new_%s" value="1" disabled="disabled" checked="checked" />', $g, $g);
		echo "\n</td>\n";
	}
	echo "</tr><tr>\n";
	for ($g = 0; $g < count($all_answers); $g++) {
		if ($all_answers[$g]['fake'] == 't') continue;
		printf ('<td><label for="autojudge_chc_new_%s">', $g);
		printf ('<abbr title="%s">%s</abbr>', $all_answers[$g]['desc'], $all_answers[$g]['short']);
		echo "</label></td>\n";
	}
	echo "</tr></table>\n";
	?>
	<script type="text/javascript">
		function f_change_new () {
			var sel = document.getElementById("autojudge_new_sel");
			var v = sel.value;
			var max_ans = <?php echo count($all_answers) ?>;
			var dis = false;
			if (v == "all") {
				dis = true;
				sel = true;
			} else if (v == "none") {
				dis = true;
				sel = false;
			}
			
			for (g = 1; g < max_ans; g++) {
				var c = document.getElementById ("autojudge_chc_new_" + g);
				if (dis == true) {
					c.checked = sel;
					c.disabled = true;
				} else {
					c.disabled = false;
				}
			}
		}
		var sel = document.getElementById("autojudge_new_sel");
		sel.onchange = f_change_new;
		</script>
        </td>
      </tr>
    </table>
  </center>
  <center>
      <input type="submit" name="Submit3" value="Send" onClick="conf()">
      <input type="reset" name="Submit4" value="Clear">
  </center>

	 <br><br><br><center>To build a problem package from files, use this link:
      <input type="submit" name="Submit5" value="Build">
</center>
</form>

</body>
</html>
