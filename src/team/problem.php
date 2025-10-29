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
require_once('stat.php');

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
?>

<?php

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
  <td><b><?php if (getenv("BOCA_ENABLE_PROBLEM_STATS") == "true") echo "Statistics"; else echo "Basename"; ?></b></td>
  <td><b><?php if (getenv("BOCA_ENABLE_PROBLEM_TAGS") == "true") echo "Tags"; else echo "Fullname"; ?></b></td>
  <td><b>Descfile</b></td>
 </tr>
<?php

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

// Workaround: no need for the admin to login first for teams to see problems
$prob = DBGetFullProblemData($_SESSION["usertable"]["contestnumber"],true);
$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"]);

if (getenv("BOCA_SHOW_UNSOLVED_FIRST") == "true") {
  // Sort problems to show unsolved problems first
  usort($prob, function ($p1, $p2) use ($solvedproblems) {
    $solved1 = isset($solvedproblems[$p1["number"]]) && $solvedproblems[$p1["number"]]["solved"];
    $solved2 = isset($solvedproblems[$p2["number"]]) && $solvedproblems[$p2["number"]]["solved"];

    // First criteria: solved problems go to the end
    if ($solved1 != $solved2) {
        return $solved1 ? 1 : -1;
    }

    // Second criteria: sort by number
    return $p1["number"] <=> $p2["number"];
  });
}

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

  if (isset($solvedproblems[$prob[$i]["number"]]) &&
      $solvedproblems[$prob[$i]["number"]]["solved"] > 0) {
    echo " <tr class='solved'>\n";
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
  
  if (getenv('BOCA_ENABLE_PROBLEM_STATS') == 'true') {
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
    echo ": " . number_format(resubmissionRate($userAcceptedSubmissions), 2) . "%";
    echo "        </span>";
    echo "      </div>";
    echo "    </div>";
    echo "  </td>\n";
  }
  else {
    echo "  <td nowrap>" . $prob[$i]["basefilename"] . "&nbsp;</td>\n";
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
    echo "    <div class='tags'>";
    // Output grouped tags
    foreach ($groupedTags as $key => $htmlTags) {
      echo "<div class='tag-group' data-group='$key'>";
      // echo "<h4 style='margin: 0'>" . ucfirst($key) . ":</h4>"; // Optional header for each group
      echo implode("\n", $htmlTags);
      echo "</div>\n";
    }
  echo "</div>";

    echo "    </div>";
    echo "  </td>\n";
  } else {
    echo "  <td nowrap>" . $prob[$i]["fullname"] . "&nbsp;</td>\n";
  }

  if (isset($prob[$i]["descoid"]) && $prob[$i]["descoid"] != null && isset($prob[$i]["descfilename"])) {
    echo "  <td nowrap><a target=\"_blank\" href=\"../filedownload.php?" . filedownload($prob[$i]["descoid"], $prob[$i]["descfilename"]) .
		"\">" . basename($prob[$i]["descfilename"]) . "</a></td>\n";
  }
  else
    echo "  <td nowrap>no description file available</td>\n";
  echo " </tr>\n";
}
echo "</table>";
if (count($prob) == 0) echo "<br><center><b><font color=\"#ff0000\">NO PROBLEMS AVAILABLE YET</font></b></center>";

?>
<?php
// Check if the table filter should be enabled
if (getenv("BOCA_ENABLE_TABLE_FILTER") == "true") {
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
      '25%', '25%',
      '25%', '25%'
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
      results_per_page: ['Records: ', [10, 50, 200, 1000, 1000000]],
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
        at_start: [1],
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
<br><center><b><font>Problems with a shadow overlay indicate they have already been solved.</font></b></center>
</body>
</html>
