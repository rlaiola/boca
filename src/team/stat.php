<?php
////////////////////////////////////////////////////////////////////////////////
// Universidade Federal do Espírito Santo (UFES)
// BOCA Online Contest Administrator - Extended Visualization Module
//
// Developed by Rodrigo Laiola Guimarães with assistance from ChatGPT (OpenAI)
//
// This file is part of the BOCA Online Contest Administrator system and provides
// visualization and analytical features such as dynamic pie charts and performance
// summaries for contests, teams, and users.
//
//    Copyright (C) 2025 Universidade Federal do Espírito Santo
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
// Last modified 19/oct/2025 by rodrigo.l.guimaraes@ufes.br
require_once('header.php');

// --- Configuration check ---
if (getenv("BOCA_ENABLE_PROBLEM_STATS") != "true") {
    return;
}

// --- User and contest info ---
$contest   = $_SESSION["usertable"]["contestnumber"];
$site      = $_SESSION["usertable"]["usersitenumber"];
$user      = $_SESSION["usertable"]["usernumber"];
$userKey   = $user . "-" . $site;

// --- Fetch data ---
$users     = DBAllUserInfo($contest, $site);
list($score, $data0) = DBScoreSite($contest, $site, 1, -1);
$problems  = DBGetProblems($contest);

// --- Helper functions ---

// Extract tags from problem fullname
function extractTags($fullname) {
    $fullname = str_replace("(DEL)", "", $fullname);
    $fullname = trim($fullname, "[]");
    $tags = explode(",", $fullname);
    $result = [];

    foreach ($tags as $tag) {
        $tag = trim($tag);
        if (strpos($tag, "#") === 0 && strpos($tag, "?") !== false) {
            list($key, $value) = explode("?", ltrim($tag, "#"), 2);
            $result[$key][] = $value;
        }
    }

    return $result;
}

// Exponential weight function
// function exponentialWeight($n, $lambda = 0.5) {
//     return exp(-$lambda * ($n - 1));
// }

// Median of an array
// function median($arr) {
//     sort($arr);
//     $count = count($arr);
//     if ($count === 0) return 0;
//     $mid = floor($count / 2);
//     return $count % 2 ? $arr[$mid] : ($arr[$mid - 1] + $arr[$mid]) / 2;
// }

// Average of an array
// function average($arr) {
//     return empty($arr) ? 0 : array_sum($arr) / count($arr);
// }

// Re-submission rate
// function resubmissionRate($subs) {
//     if (empty($subs)) return 0;
//     return count(array_filter($subs, fn($c) => $c > 1)) / count($subs) * 100;
// }

// --- Aggregate stats ---
$totalProblems = count($problems);
$totalSolved   = 0;
$groupStats    = [];

foreach ($problems as $p) {
    $pid      = $p["number"];
    $solved   = isset($score[$userKey]["problem"][$pid]["solved"]) && $score[$userKey]["problem"][$pid]["solved"] > 0;
    $subCount = $score[$userKey]["problem"][$pid]["count"] ?? 0;

    if ($solved) $totalSolved++;

    $tags = extractTags($p["fullname"]);

    foreach ($tags as $group => $values) {
        if (!isset($groupStats[$group])) $groupStats[$group] = [];

        foreach ($values as $value) {
            if (!isset($groupStats[$group][$value])) {
                $groupStats[$group][$value] = [
                    'total' => 0,
                    'solved' => 0,
                    'submissions' => [],
                    'acceptedSubmissions' => []
                ];
            }

            $groupStats[$group][$value]['total']++;
            if ($solved) {
                $groupStats[$group][$value]['solved']++;
                $groupStats[$group][$value]['acceptedSubmissions'][] = $subCount;
            }
            if ($subCount > 0) $groupStats[$group][$value]['submissions'][] = $subCount;
        }
    }
}

$overallPercent = $totalProblems > 0 ? round($totalSolved / $totalProblems * 100, 1) : 0;
$unsolved       = max(0, $totalProblems - $totalSolved);

// --- Overall re-submission rate ---
$allAccepted = [];
foreach ($groupStats as $group) {
    foreach ($group as $tag) {
        if (!empty($tag['acceptedSubmissions'])) {
            $allAccepted = array_merge($allAccepted, $tag['acceptedSubmissions']);
        }
    }
}
$re_submission_rate = resubmissionRate($allAccepted);

// --- Compute user rank ---
$rankList = [];
foreach ($users as $u) {
    $otherKey    = $u['usernumber'] . "-" . $u['usersitenumber'];
    $solvedOther = 0;

    foreach ($problems as $p) {
        $pid = $p['number'];
        if (isset($score[$otherKey]['problem'][$pid]['solved']) && $score[$otherKey]['problem'][$pid]['solved'] > 0)
            $solvedOther++;
    }

    $rankList[$otherKey] = $solvedOther;
}

arsort($rankList);
$userRank = array_search($userKey, array_keys($rankList)) + 1;

// --- Prepare run data ---
$runs = DBUserRuns($contest, $site, $user);

// Sort runs descending by "number"
usort($runs, fn($a, $b) => $b['number'] <=> $a['number']);

// Initialize arrays
$answerCounts           = [];
$answerColors           = [];
$totalRuns              = 0;

$languageCounts         = [];
$languageColors         = [];
$totalLanguageRuns      = 0;

$acceptedLanguageCounts = [];
$acceptedLanguageColors = [];
$totalAcceptedLanguageRuns = 0;

// --- Shades of green ---
$greenShades = [];
$maxShades   = 10;
for ($i = 0; $i < $maxShades; $i++) {
    $lightness = 25 + $i * (65 / max(1, $maxShades - 1)); // Darkest 25% → Lightest 90%
    $greenShades[] = "hsl(291, 100%, {$lightness}%, 80%)";
}

$languageOrder = [];

// --- Process each run ---
foreach ($runs as $r) {
    // --- Runs by answer ---
    $answer = trim($r["answer"]) ?: "Not answered yet";

    if (!isset($answerCounts[$answer])) {
        $answerCounts[$answer] = 0;
        $answerColors[$answer] = [];
    }

    $answerCounts[$answer]++;
    $answerColors[$answer][] = ($r["yes"] == 't') ? '#b0b0a0' : 'transparent';
    $totalRuns++;

    // --- Runs by language ---
    $language = trim($r["language"]) ?: "Unknown";

    if (!isset($languageCounts[$language])) {
        $languageCounts[$language] = 0;
        $languageColors[$language] = [];
        $languageOrder[] = $language;
    }

    $languageCounts[$language]++;
    $totalLanguageRuns++;

    // Assign consistent shade of green per language
    $shadeIndex = array_search($language, $languageOrder);
    $fillColor  = $greenShades[$shadeIndex % count($greenShades)];
    $languageColors[$language][] = $fillColor;

    // --- Accepted runs by language ---
    if (!isset($acceptedLanguageCounts[$language])) {
        $acceptedLanguageCounts[$language] = 0;
        $acceptedLanguageColors[$language] = [];
    }

    if ($r["yes"] == 't') {
        $acceptedLanguageCounts[$language]++;
        $totalAcceptedLanguageRuns++;
    }

    $acceptedLanguageColors[$language][] = $fillColor;
}

// --- Pie chart JS helper ---
function drawPieChartJS($canvasId, $data, $colorsPerRun, $total) {
    return "<script>
        (function(){
            var data = " . json_encode($data) . ";
            var colorsPerRun = " . json_encode($colorsPerRun) . ";
            var total = $total;
            var canvas = document.getElementById('$canvasId');
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var cx = canvas.width / 2, cy = canvas.height / 2, r = Math.min(cx, cy) - 5;
            var start = -Math.PI / 2;

            // Draw pie slices
            for (var label in data) {
                if (!data.hasOwnProperty(label)) continue;
                var val = data[label];
                var angle = (val / total) * 2 * Math.PI;
                var end = start + angle;
                var fill = (colorsPerRun[label] && colorsPerRun[label][0]) ? colorsPerRun[label][0] : '#CCC';
                ctx.beginPath();
                ctx.moveTo(cx, cy);
                ctx.arc(cx, cy, r, start, end);
                ctx.closePath();
                ctx.fillStyle = fill;
                ctx.fill();
                ctx.strokeStyle = '#999';
                ctx.lineWidth = 1;
                ctx.stroke();
                start = end;
            }

            // Outer border circle
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, 2 * Math.PI);
            ctx.strokeStyle = '#999';
            ctx.lineWidth = 1;
            ctx.stroke();

            // --- Draw tick marks like in progressChart ---
            var fullCircle = 2 * Math.PI;
            var ticks = 5; // 20% each tick
            var tickSize = fullCircle / ticks;
            for (var i = 0; i < ticks; i++) {
                var angle = -Math.PI / 2 + (i * tickSize);
                var x1 = cx + (r - 5) * Math.cos(angle);
                var y1 = cy + (r - 5) * Math.sin(angle);
                var x2 = cx + r * Math.cos(angle);
                var y2 = cy + r * Math.sin(angle);
                ctx.beginPath();
                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);
                ctx.strokeStyle = 'rgba(0,0,0,0.3)';
                ctx.lineWidth = 1;
                ctx.stroke();
            }

            // Tooltip summary (hover)
            var tooltip = 'Total runs: ' + total + '\\n';
            for (var label in data) {
                if (!data.hasOwnProperty(label)) continue;
                var percent = total > 0 ? Math.round((data[label] / total) * 1000) / 10 : 0;
                tooltip += label + ': ' + data[label] + ' (' + percent + '%)\\n';
            }
            canvas.title = tooltip;
        })();
    </script>";
}

// --- Styles ---
echo "<style>
    .flex-row {
        display: flex;
        flex-wrap: wrap;
        width: 100%;
        text-align: center;
        justify-content: space-evenly;
        gap: 20px;
        margin: auto;
    }
    legend {
        font-family: 'Courier New', Courier, mono;
        font-size: 16px;
        font-weight: bold;
        padding: 0 10px;
    }
    h4, .tag-group-stats h4 {
        font-family: 'Courier New', Courier, mono;
        font-size: 14px;
        font-weight: bold;
        margin-bottom: 6px;
    }
    .progress-chart {
        flex: 0 0 30%;
        display: grid;
        align-self: start;
        text-align: center;
        border: 1px solid transparent;
        border-radius: 6px;
        gap: 10px;
    }
    .legend {
        font-family: 'Courier New', Courier, mono;
        font-size: 12px;
        color: #555;
    }
    .tag-groups-stats {
        display: flex;
        flex-wrap: initial;
        gap: 10px;
        justify-content: center;
        margin: auto;
    }
    .tag-group-stats {
        display: flow;
        width: 30%;
        border: 1px solid transparent;
        border-radius: 6px;
        padding: 5px;
        overflow: auto;
    }
    .tag-stats {
        display: inline-grid;
        font-family: 'Courier New', Courier, mono;
        font-size: 14px;
        font-weight: normal;
        margin: auto 5px;
        margin-bottom: 6px;
        cursor: help;
    }
    .progress-bar {
        position: relative;
        width: 100px;
        height: 8px;
        border: 1px solid #aaa;
        border-radius: 8px;
        margin-top: 2px;
    }
    .tick {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 1px;
        background-color: rgba(0,0,0,0.2);
    }
    .pie-charts {
        display: flex;
        flex-wrap: wrap;
        margin: auto;
        margin-top: 15px;
        margin-bottom: 15px;
        gap: 20px;
        justify-content: space-evenly;
    }
    .pie-chart {
        display: inline-table;
        flex: 0 0 225px;
        text-align: center;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    #progressChart, #runsByAnswerChart, #runsByLanguageChart, #acceptedRunsByLanguageChart {
        cursor: help;
    }
</style>";

// --- Display layout ---
echo "<fieldset style='border:1px solid #ccc; border-radius:6px; margin-top:20px;'>";
echo "<legend>Activity Summary</legend>";

// --- Pie charts row ---
echo "<div class='pie-charts'>";

// --- Overall progress canvas + draw script (replace previous progressChart block) ---
echo "<div class='pie-chart'>
        <h4 style='margin-top:0;'>Overall Progress</h4>
        <canvas id='progressChart' width='125' height='125' style='margin:auto;'></canvas>
      </div>";

// echo JS after the canvas so the element exists when we draw
?>
<script>
(function(){
  // Safely inject PHP values
  var solved = <?php echo json_encode($totalSolved); ?>;
  var unsolved = <?php echo json_encode($unsolved); ?>;
  var overallPercent = <?php echo json_encode($overallPercent); ?>;
  var allGroupStats = <?php echo json_encode($groupStats); ?>;
  var reSubRate = <?php echo json_encode(round($re_submission_rate, 1)); ?>;

  // compute weightedSuccess using same logic you used in PHP
  function expWeight(n, lambda) { return Math.exp(-lambda * (n - 1)); }

  var lambda = 0.5;
  var allAttempts = [], acceptedAttempts = [];
  for (var group in allGroupStats) {
    for (var tag in allGroupStats[group]) {
      var d = allGroupStats[group][tag];
      if (d.acceptedSubmissions && d.acceptedSubmissions.length > 0) {
        allAttempts = allAttempts.concat(d.submissions || []);
        acceptedAttempts = acceptedAttempts.concat(d.acceptedSubmissions);
      }
    }
  }

  var totalWeightedSub = 0, totalWeightedAcc = 0;
  allAttempts.forEach(function(sub){
    for (var i = 1; i <= sub; i++) totalWeightedSub += expWeight(i, lambda);
  });
  acceptedAttempts.forEach(function(sub){
    totalWeightedAcc += expWeight(sub, lambda);
  });
  var weightedSuccess = totalWeightedSub > 0 ? Math.round((totalWeightedAcc / totalWeightedSub) * 1000) / 10 : 0;

  function median(v) {
    v = (v || []).slice().sort(function(a,b){return a-b;});
    if (v.length === 0) return 0;
    var mid = Math.floor(v.length / 2);
    return v.length % 2 ? v[mid] : (v[mid-1] + v[mid]) / 2;
  }
  function average(v) {
    if (!v || !v.length) return 0;
    return v.reduce((a,b)=>a+b,0)/v.length;
  }

  var medAttempts = Math.round(median(acceptedAttempts) * 100) / 100;
  var avgAttempts = Math.round(average(acceptedAttempts) * 100) / 100;

  // draw pie
  var canvas = document.getElementById('progressChart');
  if (!canvas) return; // be defensive
  var ctx = canvas.getContext('2d');
  var cx = canvas.width / 2, cy = canvas.height / 2, r = Math.min(cx, cy) - 5;
  var fullCircle = 2 * Math.PI;
  var solvedAngle = (overallPercent / 100) * fullCircle;

  // solved slice
  ctx.beginPath();
  ctx.moveTo(cx, cy);
  ctx.arc(cx, cy, r, -Math.PI/2, -Math.PI/2 + solvedAngle, false);
  ctx.closePath();
  ctx.fillStyle = '#b0b0a0';
  ctx.fill();

  // remaining slice (transparent)
  ctx.beginPath();
  ctx.moveTo(cx, cy);
  ctx.arc(cx, cy, r, -Math.PI/2 + solvedAngle, 1.5 * Math.PI, false);
  ctx.closePath();
  ctx.fillStyle = 'transparent';
  ctx.fill();

  // border
  ctx.beginPath();
  ctx.arc(cx, cy, r, 0, fullCircle);
  ctx.strokeStyle = '#999';
  ctx.lineWidth = 1;
  ctx.stroke();

  // ticks (optional)
  var ticks = 5;
  var tickSize = fullCircle / ticks;
  for (var i = 0; i < ticks; i++) {
    var angle = -Math.PI/2 + (i * tickSize);
    var x1 = cx + (r - 5) * Math.cos(angle);
    var y1 = cy + (r - 5) * Math.sin(angle);
    var x2 = cx + r * Math.cos(angle);
    var y2 = cy + r * Math.sin(angle);
    ctx.beginPath();
    ctx.moveTo(x1, y1);
    ctx.lineTo(x2, y2);
    ctx.strokeStyle = 'rgba(0,0,0,0.3)';
    ctx.lineWidth = 1;
    ctx.stroke();
  }

  // tooltip summary on title
  var total = solved + unsolved;
  var tooltipText =
    'Solved: ' + solved + ' / ' + total + ' (' + overallPercent + '%)\n' +
    'Weighted success rate: ' + weightedSuccess + '%\n' +
    'Median attempts-to-success: ' + medAttempts + '\n' +
    'Avg. attempts-to-success: ' + avgAttempts + '\n' +
    'Re-submission rate: ' + reSubRate + '%';
  canvas.title = tooltipText;
})();
</script>

<?php
// Runs by answer
echo "<div class='pie-chart'>
    <h4>All Runs by Answer</h4>
    <canvas id='runsByAnswerChart' width='125' height='125'></canvas>
    " . drawPieChartJS('runsByAnswerChart', $answerCounts, $answerColors, $totalRuns) . "
</div>";

// Runs by language
echo "<div class='pie-chart'>
    <h4>All Runs by Language</h4>
    <canvas id='runsByLanguageChart' width='125' height='125'></canvas>
    " . drawPieChartJS('runsByLanguageChart', $languageCounts, $languageColors, $totalLanguageRuns) . "
</div>";

// Accepted runs by language
echo "<div class='pie-chart'>
    <h4>Accepted Runs by Language</h4>
    <canvas id='acceptedRunsByLanguageChart' width='125' height='125'></canvas>
    " . drawPieChartJS('acceptedRunsByLanguageChart', $acceptedLanguageCounts, $acceptedLanguageColors, $totalAcceptedLanguageRuns) . "
</div>";

echo "</div>"; // End pie charts row

// --- Tag Groups ---
if (getenv("BOCA_ENABLE_PROBLEM_TAGS") != "true") {
    return;
}

echo "<div class='flex-row'><div class='tag-groups-stats'>";
$groupsToShow = array_filter(array_keys($groupStats), fn($g) => $g !== 'lang');

foreach ($groupsToShow as $group) {
    echo "<div class='tag-group-stats'>";
    echo "<h4 style='margin:2px 0 6px 0;'>" . ucfirst(htmlspecialchars($group)) . "</h4>";

    // Sort by $value if $group == 'domain'
    if ($group === 'domain') {
        ksort($groupStats[$group], SORT_NATURAL | SORT_FLAG_CASE);
    }

    foreach ($groupStats[$group] as $value => $data) {
        $percent = $data['total'] > 0 ? round($data['solved'] / $data['total'] * 100, 1) : 0;
        $totalWeightedSub = $totalWeightedAccepted = 0;

        foreach ($data['submissions'] as $sub) for ($i = 1; $i <= $sub; $i++) $totalWeightedSub += exponentialWeight($i);
        foreach ($data['acceptedSubmissions'] as $sub) $totalWeightedAccepted += exponentialWeight($sub);

        $weightedSuccess = $totalWeightedSub > 0 ? round(($totalWeightedAccepted / $totalWeightedSub) * 100, 1) : 0;
        $accMed = round(median($data['acceptedSubmissions']), 2);
        $accAvg = round(average($data['acceptedSubmissions']), 2);
        $resub = round(resubmissionRate($data['acceptedSubmissions']), 1);

        $tooltip = "Solved: {$data['solved']} / {$data['total']} ({$percent}%)\nWeighted success rate: {$weightedSuccess}%\nMedian attempts-to-success: {$accMed}\nAvg. attempts-to-success: {$accAvg}" . ($resub > 0 ? "\nRe-submission rate: {$resub}%" : "");

        echo "<div class='tag-stats' title=\"$tooltip\">" . htmlspecialchars($value);

        // --- Progress bar colors ---
        switch ($group) {
            case 'group':
                $border = '#057471cc'; $bgcolor = '#057471cc'; $color = '#057471cc'; break;
            case 'level':
                $border = '#024b84cc'; $bgcolor = '#024b84cc'; $color = '#024b84cc'; break;
            case 'domain':
                $border = '#c37400cc'; $bgcolor = '#c37400cc'; $color = '#875000cc'; break;
            default:
                $border = $bgcolor = $color = '#4CAF50cc'; break;
        }

        echo "<div class='progress-bar' style='background:linear-gradient(to right, {$bgcolor} {$percent}%, transparent {$percent}%); border-color: {$border}; color: {$color};'>";
        for ($i = 1; $i < 5; $i++) {
            $pos = $i * 20; 
            echo "<div class='tick' style='left:calc({$pos}% - 0.5px);'></div>";
        }
        echo "</div></div>";
    }
    echo "<br /></div>";
}

echo "</div></div>"; // End tag groups row
echo "</fieldset>"; // End main fieldset

// require('footer.php');
?>
