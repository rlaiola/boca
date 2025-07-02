<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2013 by BOCA System (bocasystem@gmail.com)
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
//Last updated 24/oct/2017 by cassio@ime.usp.br
require_once("db.php");

if(isset($_SESSION["locr"]))
	$locr=$_SESSION["locr"];
else
	$locr='.';

if(isset($_GET["clock"]) && $_GET["clock"]==1) {
	ob_start();
	header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
	header ("Content-Type: text/html; charset=utf-8");
	session_start();
	ob_end_flush();

	if(!isset($contest) || !isset($localsite)) {
		$ct=DBGetActiveContest();
		$contest=$ct['contestnumber'];
		$localsite=$ct['contestlocalsite'];
	}
	if (($blocal = DBSiteInfo($contest, $localsite)) == null) {
		echo "0";
		exit;
	}
	if(isset($blocal['currenttime']))
		echo $blocal["currenttime"];
	else echo "0";
	exit;
}
if(isset($_GET['remote'])) {
	ob_start();
	header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header ("Cache-Control: no-cache, must-revalidate");
	header ("Pragma: no-cache");
	header ("Content-Type: text/html; charset=utf-8");
	session_start();
	ob_end_flush();

	if (isset($_SESSION["usertable"])) {
        $tmp = DBUserInfo($_SESSION["usertable"]["contestnumber"],
			  $_SESSION["usertable"]["usersitenumber"], $_SESSION["usertable"]["usernumber"]);
		$_SESSION["usertable"]['usersessionextra'] = $tmp['usersessionextra'];
	} else {
		IntrusionNotify("scoretable1");
        ForceLoad("index.php");
	}
	if(!isset($_SESSION['usertable']['usertype']) || $_SESSION["usertable"]["usertype"] != "site") {
		IntrusionNotify("scoretable2");
        ForceLoad("index.php");
	}
}

if(!ValidSession()) {
	InvalidSession("scoretable.php");
	ForceLoad("index.php");
}
$loc = $_SESSION["loc"];
if(!isset($detail)) $detail=true;
if(!isset($final)) $final=false;
$scoredelay["admin"] = 2;
$scoredelay["score"] = 30;
$scoredelay["team"] = 10;
$scoredelay["judge"] = 3;
$scoredelay["staff"] = 2;
$actualdelay = 30;
if(isset($scoredelay[$_SESSION["usertable"]["usertype"]])) $actualdelay = $scoredelay[$_SESSION["usertable"]["usertype"]];
$ds = DIRECTORY_SEPARATOR;
if($ds=="") $ds = "/";

$scoretmp = $_SESSION["locr"] . $ds . "private" . $ds . "scoretmp" . $ds . $_SESSION["usertable"]["usertype"] . '-' . $_SESSION["usertable"]["username"] . ".php";
$redo = TRUE;
if(file_exists($scoretmp)) {
	if(($strtmp = file_get_contents($scoretmp,FALSE,NULL,0,5000000)) !== FALSE) {
		list($d) = sscanf($strtmp,"%*s %d");
		if($d > time() - $actualdelay) {
			$redo = FALSE;
		}
	}
}

if(isset($_GET["remote"])) {
  $privatedir = $_SESSION['locr'] . $ds . "private";
  $remotedir = $_SESSION['locr'] . $ds . "private" . $ds . "remotescores";
  $destination = $remotedir . $ds ."scores.zip";
  if(is_writable($remotedir)) {
	if($redo || !is_readable($destination)) {
	  if(($fp = @fopen($destination . ".lck",'x')) !== false) {

		if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
			ForceLoad("index.php");
		
		$level=$s["sitescorelevel"];
		$data0 = array();
		if($level>0) {
			list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
											  $_SESSION["usertable"]["usersitenumber"], 0, -1);
		}
		$ct=DBGetActiveContest();
		$localsite=$ct['contestlocalsite'];
		$fname = $privatedir . $ds . "score_localsite_" . $localsite . "_x"; // . md5($_SERVER['HTTP_HOST']);
		@file_put_contents($fname . ".tmp",base64_encode(serialize($data0)));
		@rename($fname . ".tmp",$fname . ".dat");

		$data0 = array();
		if($level>0) {
			list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
											  $_SESSION["usertable"]["usersitenumber"], 1, -1);
		}
		$ct=DBGetActiveContest();
		$localsite=$ct['contestlocalsite'];
		$fname = $remotedir . $ds . "score_site" . $localsite . "_" . $localsite . "_x"; // . md5($_SERVER['HTTP_HOST']);
		@file_put_contents($fname . ".tmp",base64_encode(serialize($data0)));
		@rename($fname . ".tmp",$fname . ".dat");
		//scoretransfer($fname . ".dat", $localsite);
		
		if(@create_zip($remotedir,glob($remotedir . '/*.dat'),$fname . ".tmp") != 1) {
			LOGError("Cannot create score zip file");
			if(@create_zip($remotedir,array(),$fname . ".tmp") == 1)
				@rename($fname . ".tmp",$destination);
		} else {
			@rename($fname . ".tmp",$destination);
		}
		@fclose($fp);

		//getMainXML($_SESSION["usertable"]["contestnumber"]);
		
		@unlink($destination . ".lck");
	  } else {
			if(file_exists($destination . ".lck") && filemtime($destination . ".lck") < time() - 180)
				@unlink($destination . ".lck");
	  }
	}
  }
  if(is_numeric($_GET["remote"])) {
		if($_GET["remote"]==-42) {
			echo file_get_contents($destination);
		} else {
			if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
				ForceLoad("index.php");
			
			$level=$s["sitescorelevel"];
			$score = array();
			if($level>0) {
				list($score,$data0) = DBScoreSite($_SESSION["usertable"]["contestnumber"], 
								  $_SESSION["usertable"]["usersitenumber"], 1, -1, $_GET["remote"]);
			}
			echo base64_encode(serialize($score));
		}
  } else {
    echo base64_encode(serialize(array()));
  }
  exit;
}

if(!$redo) {
	$conf=globalconf();
	if(isset($conf['doenc']) && $conf['doenc'])
	  $strtmp = decryptData(substr($strtmp,strpos($strtmp,"\n")),$conf["key"],'score');
	else $strtmp = substr($strtmp,strpos($strtmp,"\n"));
	if($strtmp=="") $redo=TRUE;
}
if($redo) {
	$strtmp = "<script language=\"JavaScript\" src=\"" . $loc . "/hide.js\"></script>\n";
	$pr = DBGetProblems($_SESSION["usertable"]["contestnumber"]);

	$ct=DBGetActiveContest();
	$contest=$ct['contestnumber'];
	$duration=$ct['contestduration'];

	if(!isset($hor)) $hor = -1;
	if($hor>$duration) $hor=$duration;

	if (($s = DBSiteInfo($ct['contestnumber'],$_SESSION["usertable"]["usersitenumber"])) == null)
		ForceLoad("index.php");
	$level=$s["sitescorelevel"];
	if($level<=0) $level=-$level;
	else {
		$des=true;
	}

	if (($s = DBSiteInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"])) == null)
		ForceLoad("index.php");
	$score = DBScore($_SESSION["usertable"]["contestnumber"], $ver, $hor*60, $s["siteglobalscore"]);
	
	if ($_SESSION["usertable"]["usertype"]!="score" && $_SESSION["usertable"]["usertype"]!="admin" && $level>3) $level=3;

	$minu = 3;
	$rn = DBRecentNews($_SESSION["usertable"]["contestnumber"],
					   $_SESSION["usertable"]["usersitenumber"], $ver, $minu);
	if(count($rn)>0 && $level>3) {
		$strtmp .= "<table border=0><tr>";
		$strtmp .= "<td>News (last ${minu}'): &nbsp;</td>\n";
		for($i=0; $i<count($rn); $i++) {
			$strtmp .= "<td width=200>";
			if($rn[$i]["yes"]=='t') {
				$strtmp .= "<img alt=\"".$rn[$i]["colorname"].":\" width=\"28\" ".
					"src=\"" . balloonurl($rn[$i]["color"]) ."\" />";
			}
			else
				$strtmp .= "<img alt=\"\" width=\"22\" ".
					"src=\"$loc/images/bigballoontransp-blink.gif\" />\n";
			$strtmp .= $rn[$i]["problemname"] . ": " . $rn[$i]["userfullname"] . " (" . ((int) ($rn[$i]["time"]/60)) . "')";
			$strtmp .= "</td>\n";
		}
		$strtmp .= "</tr></table>";
	}
	if($hor>=0) {
		$strtmp .= "<center>As of $hor minutes. Next: ";
		for($h=-30; $h<40; $h+=10) {
			if($hor+$h>=0 && $h!=0) {
				$strtmp .= "<a href=\"$loc/" . $_SESSION['usertable']['usertype'] . "/report/score.php?p=0&hor=" . ($hor+$h) . "\">";
//				$strtmp .= "<a href=\"$loc/admin/report/score.php?p=0&hor=" . ($hor+$h) . "\">";
				if($h>0) $strtmp .= "+";
				$strtmp .= "$h</a>&nbsp;";
			}
		}
		$strtmp .= "</center><br>";
	}
	if(is_readable($_SESSION["locr"] . $ds . 'private' . $ds . 'score.sep')) {
		$rf=file($_SESSION["locr"] . $ds . 'private' . $ds . 'score.sep');
		$fta=true;
		for($rfi=1;$rfi<=count($rf);$rfi++) {
			$lin = explode('#',trim($rf[$rfi-1]));
			if(isset($lin[1]) && $_SESSION["usertable"]["usertype"]!='admin') {
				$arr=explode(' ',trim($lin[1]));
				for($arri=0;$arri<count($arr);$arri++)
					if($arr[$arri] != '' && preg_match($arr[$arri],$_SESSION["usertable"]["username"])) break;
				if($arri>=count($arr)) continue;
			}
			$lin = trim($lin[0]);
			if($lin=='') continue;
		//if($fta) { $fta=false; $strtmp .= "<br><img src=\"$loc/images/smallballoontransp.png\" alt=\"\" onload=\"javascript:toggleGroup($rfi)\"> <b>Available scores:</b> \n"; }
            $grname=explode(' ',$lin);
			$class=1;
			reset($score);
			while(1) {
				$e=key($score);
				$c=current($score);
				if(!isset($score[$e]['classingroup'])) $score[$e]['classingroup']=array();
				for($k=1;$k<count($grname);$k++) {
					if($score[$e]['site']==$grname[$k]) {
						$score[$e]['classingroup'][$rfi]=$class;
						$class++;
					}
					else if(strpos($grname[$k],'/') >= 1) {
						$u1 = explode('/',$grname[$k]);
						if(isset($u1[1]) && $score[$e]['user'] >= $u1[0] && $score[$e]['user'] <= $u1[1]) {
							if(!isset($u1[2]) || $u1[2]==$score[$e]['site']) {
							$score[$e]['classingroup'][$rfi]=$class;
							$class++;
							}
						}
					}

				}
				if (next($score) === false)
					break;
			}

			if($class>1) {
			  if($fta) { $fta=false; $strtmp .= "<br><img src=\"$loc/images/smallballoontransp.png\" alt=\"\" onload=\"javascript:toggleGroup($rfi)\"> <b>Available scores:</b> \n"; }
				$strtmp .= "<a href=\"#\" onclick=\"javascript:toggleGroup($rfi)\">" . $grname[0] . "</a> ";
			}
/*
			if($class>1)
				$strtmp .= "<a href=\"#\" onclick=\"javascript:toggleGroup($rfi)\">" . $grname[0] . "</a> ";
*/
		}
		if (isset($n) && $n !== 0) $strtmp .= "<br>\n";
	} else {
		reset($score);
		$class = 1;
		while(1) {
			$e=key($score);
			$c=current($score);
			$score[$e]['classingroup'][1]=$class;
			$class++;
			if (next($score)=== false)
				break;
		}
	}

	$strtmp .= "<br>\n<table class=\"bocaTable\" id=\"myscoretable\" width=\"100%\" style=\"width: 100%\" border=1>\n <tr>\n  <td><b>#</b></td>\n  <td><b>User/Site</b></td>\n  <td><b>Name</b></td>\n";
	if(!$des) {
		if($level>0)
			$strtmp .= "<td><b>Problems</b></td>";
	} else if($detail) {
		for($i=0;$i<count($pr);$i++)
			// $strtmp .= "<td nowrap><b>" . $pr[$i]["problem"] . " &nbsp;</b></td>";
			$strtmp .= "<td title=\"" . $pr[$i]["problem"] . "\"><b>" . $pr[$i]["problem"] . "</b></td>";
	} 
	$strtmp .= "<td><b>Total</b></td>\n";
	$strtmp .= "</tr>\n";
	$n=0;
	reset($score);
	while(1) {
		$e=key($score);
		$c=current($score);
		if(!isset($score[$e]['username'])) break;
	if(!isset($score[$e]['classingroup'])) continue;  
	  reset($score[$e]['classingroup']);
 	  while(1) {
		  $cg1=key($score[$e]['classingroup']);
		  $cg2=current($score[$e]['classingroup']);
		  if(empty($cg2))
			  if(next($score[$e]['classingroup'])===false)
				  break;
		// Adiciona a classe `highlighted-row` se o usuário do scoreboard corresponder ao usuário logado
		$rowClass = ($score[$e]['username'] == $_SESSION["usertable"]["username"]) ? 'highlight' : '';
  	    $strtmp .= " <tr class=\"";
		$strtmp .= "sitegroup" . $cg1 . " " . $rowClass . "\">";
		$strtmp .= "<td>" . $cg2 . "</td>\n";
/*
		if($level>3 && !$final && $score[$e]["site"]==$ct['contestlocalsite'] &&
		   ((isset($_SESSION["scorepos"][$score[$e]["username"]."-".$score[$e]["site"]]) &&
			 $_SESSION["scorepos"][$score[$e]["username"]."-".$score[$e]["site"]] > $cg2) || 
			(isset($_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]) &&
			 $_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]>time()))) {
			$strtmp .= "  <td nowrap bgcolor=\"#b0b0a0\">" . $score[$e]["username"]."/".$score[$e]["site"];
			$strtmp .= "<td bgcolor=\"#b0b0a0\">" . $score[$e]["userfullname"];
			if(!isset($_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]) ||
				$_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]==0) {
				$_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]] = time()+1;
			}
		}
		else {
*/
			$_SESSION["scoreblink"][$score[$e]["username"]."-".$score[$e]["site"]]=0;
			if( $score[$e]["userflag"] != '')
			  $strtmp .= "  <td nowrap><img alt=\"" .  $score[$e]["userflag"]. "\" width=\"18\" src=\"" . $loc. '/images/flags/' . 
			    // $score[$e]["userflag"] . ".png\"> " . $score[$e]["username"]."/".$score[$e]["usersitename"] . " </td>";
				$score[$e]["userflag"] . ".png\"> " . 
				(
				  $_SESSION["usertable"]["usertype"] == "admin" || 
				  $score[$e]["user"] == $_SESSION["usertable"]["usernumber"] ||
				  getenv('BOCA_ANONYMIZED_SCORE') != 'true' ? 
					$score[$e]["username"] : "####"
				) . 
				"/".$score[$e]["usersitename"] . " </td>";
			else
			  // $strtmp .= "  <td nowrap>" . $score[$e]["username"]."/".$score[$e]["usersitename"] . " </td>";
			  $strtmp .= "  <td nowrap>" . 
			  (
				$_SESSION["usertable"]["usertype"] == "admin" || 
				$score[$e]["user"] == $_SESSION["usertable"]["usernumber"] ||
				getenv('BOCA_ANONYMIZED_SCORE') != 'true' ? 
				  $score[$e]["username"] : "####"
			  ) . 
			  "/".$score[$e]["usersitename"] . " </td>";

	//		if($score[$e]['usershortinstitution'] != '') 
	//		  $strtmp .= "<td>[" . $score[$e]['usershortinstitution'] . '] ' . $score[$e]["userfullname"];
	//		else
			  // $strtmp .= "<td>" . $score[$e]["userfullname"];
			  $strtmp .= "<td>" . 
			  	(
				  $_SESSION["usertable"]["usertype"] == "admin" || 
				  $score[$e]["user"] == $_SESSION["usertable"]["usernumber"] ||
				  getenv('BOCA_ANONYMIZED_SCORE') != 'true' ? 
				    $score[$e]["userfullname"] : "####"
				);
//		}
		$_SESSION["scorepos"][$score[$e]["username"]."-".$score[$e]["site"]] = $cg2;

//    $strtmp .= "(" . $score[$e]["site"] . ")";
//    $strtmp .= "</td>\n";
//    if(!$detail && $score[$e]["userdesc"]!="")
//        $strtmp .= "(" . $score[$e]["userdesc"] . ")";
		$strtmp .= "</td>";
		if($level > 0) {
			if(!$des) $strtmp .= "<td>";
			for($h=0;$h<count($pr);$h++) {
				$ee = $pr[$h]["number"];
				if($detail) {
					if($des) {
						$strtmp .= "<td nowrap>";
//					$name=$score[$e]["problem"][$ee]["name"];
						if(isset($score[$e]["problem"][$ee]["solved"]) && $score[$e]["problem"][$ee]["solved"]) {
							$strtmp .= "<img alt=\"".$score[$e]["problem"][$ee]["colorname"].":\" width=\"18\" ".
								"src=\"" . balloonurl($score[$e]["problem"][$ee]["color"]) ."\" />";
						}
						else {
							if($level>3 && isset($score[$e]["problem"][$ee]["judging"]) && $score[$e]["problem"][$ee]["judging"])
								$strtmp .= "<img alt=\"\" width=\"18\" ".
									"src=\"$loc/images/bigballoontransp-blink.gif\" />\n";
							else
								$strtmp .= "&nbsp;";
						}
					}
					if ($ver && $level<3) {
						if(isset($score[$e]["problem"][$ee]["solved"]) && $score[$e]["problem"][$ee]["solved"]) {
							if ($level==1) {
								$strtmp .= "/". $score[$e]["problem"][$ee]["time"] . "\n";
							}
							else
								$strtmp .= $score[$e]["problem"][$ee]["count"] . "/" . 
									$score[$e]["problem"][$ee]["time"] . "\n";					
						} else if($des) $strtmp .= "&nbsp;";
					}
					else {
						if (isset($score[$e]["problem"][$ee]['count']) && $score[$e]["problem"][$ee]["count"]!=0) {
							$tn = $score[$e]["problem"][$ee]["count"];
							if (isset($score[$e]["problem"][$ee]["solved"]) && $score[$e]["problem"][$ee]["solved"]) $t = $score[$e]["problem"][$ee]["time"];
							else $t = "-";
							$strtmp .= "<font size=\"-2\">" . $tn . "/${t}" . "</font>\n";
						} else if($des) $strtmp .= "&nbsp;";
					}
					if($des)
						$strtmp .= "</td>";
				}
			}
			if(!$des) $strtmp .= "&nbsp;</td>\n";
		}
		$strtmp .= "  <td nowrap>" . 
			$score[$e]["totalcount"] . " (" . $score[$e]["totaltime"] . ")</td>\n";
		$strtmp .= " </tr>\n";
		$n++;
		if(next($score[$e]['classingroup'])===false)
			break;
	  }
	  if(next($score)===false)
		  break;
	}
	$strtmp .= "</table>";
	if (!isset($n) || $n === 0) $strtmp .= "<br><center><b><font color=\"#ff0000\">SCOREBOARD IS EMPTY</font></b></center>";
	else {
		if(!$des) 
			if($level>0) $strtmp .= "<br><font color=\"#ff0000\">P.S. Problem names are hidden.</font>";
			else  $strtmp .= "<br><font color=\"#ff0000\">P.S. Problem data are hidden.</font>";
	}

	$conf=globalconf();
	if(isset($conf['doenc']) && $conf['doenc'])
	  $strtmp = "<!-- " . time() . " --> <?php exit; ?>\n" . encryptData($strtmp,$conf["key"],false);
	else $strtmp = "<!-- " . time() . " --> <?php exit; ?>\n" . $strtmp;
	$randnum = session_id() . "_" . rand();
	if(file_put_contents($scoretmp . "_" . $randnum, $strtmp,LOCK_EX)===FALSE) {
		if($_SESSION["usertable"]["usertype"] == 'admin') {
			MSGError("Cannot write to the score cache file -- performance might be compromised");
		}
		LOGError("Cannot write to the ".$_SESSION["usertable"]["usertype"]."-score cache file -- performance might be compromised");
	} else {
	  @rename($scoretmp . "_" . $randnum, $scoretmp);
	}
	$conf=globalconf();
	if(isset($conf['doenc']) && $conf['doenc'])
	  $strtmp = decryptData(substr($strtmp,strpos($strtmp,"\n")),$conf["key"]);
	else $strtmp = substr($strtmp,strpos($strtmp,"\n"));
}
echo $strtmp;
?>

<?php
// Check if the table filter should be enabled
if (getenv("BOCA_ENABLE_TABLE_FILTER") == "true") {
?>
<div id="externalToolbar" <?php if (!isset($n) || $n === 0) echo "style=\"display: none\""; ?>></div>
<style>
  td {
    word-wrap: break-word;
  }

  div.grd_headTblCont table thead tr td,
  table.bocaTable tbody tr td {
    z-index: 1;
    <?php if (isset($n) && $n !== 0) { ?>
    position: sticky;
    width: 100px;
    <?php } else { ?>
    width: 15%;
    <?php } ?>
    padding: 0;
  }

  div.grd_headTblCont table thead tr td:nth-child(-n + 3),
  table.bocaTable tbody tr td:nth-child(-n + 3) {
    <?php if (isset($n) && $n !== 0) { ?>
    position: sticky;
    <?php } ?>
    left: 0;
    z-index: 2;
    background-color: #e0e0d0;
  }

  table.bocaTable tbody tr.sitegroup1.highlight td:nth-child(-n + 3) {
    <?php if (isset($n) && $n !== 0) { ?>
    position: sticky;
    left: 0;
	<?php } ?>
    z-index: 2;
    background-color: #e0e0d0;
  }

  table.bocaTable tbody tr.sitegroup1.highlight td:nth-child(-n + 3) {
    <?php if (isset($n) && $n !== 0) { ?>
    position: sticky;
    left: 0;
	<?php } ?>
    z-index: 2;
    background-color: #ffff00;
  }

  div.grd_headTblCont table thead tr td:nth-child(2),
  table.bocaTable tbody tr td:nth-child(2),
  table.bocaTable tbody tr.sitegroup1.highlight td:nth-child(2) {
    <?php if (isset($n) && $n !== 0) { ?>
    left: 52px;
     <?php } else { ?>
    width: 30%;
    <?php } ?>
  }

  div.grd_headTblCont table thead tr:nth-child(2) {
	display: none;
  }

  div.grd_headTblCont table thead tr td:nth-child(3),
  table.bocaTable tbody tr td:nth-child(3),
  table.bocaTable tbody tr.sitegroup1.highlight td:nth-child(3) {
    <?php if (isset($n) && $n !== 0) { ?>
    left: 254px;
     <?php } else { ?>
    width: 40%;
    <?php } ?>
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
    <?php if (isset($n) && $n !== 0) { ?>
        '50px', '200px', '350px',
    <?php } else { ?>
        '15%', '30%', '40%', '15%',
    <?php } ?>
    ],
    col_types: [
      'number', 'customstring', 'customstring'
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
    <?php if (isset($n) && $n !== 0) { ?>
    no_results_message: {
      content: '<?php echo "<center><b><font color=\"#ff0000\">NO MATCHES FOUND</font></b></center>" ?>',
    },
    <?php } ?>
    paging: {
      results_per_page: ['Records: ', [50, 200, 1000, 1000000]],
    },
    // grid layout customisation
    grid_layout: {
      width: '100%',
      <?php if (isset($n) && $n !== 0) { ?>
      height: 'auto'
      <?php } else { ?>
      height: 'auto'
      <?php } ?>
    },
    extensions: [
      {
        name: 'colsVisibility',
        enable_tick_all: true,
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
