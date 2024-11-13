<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2014 by BOCA Development Team (bocasystem@gmail.com)
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
require('header.php');

$authMethod = getenv("BOCA_AUTH_METHOD") ? getenv("BOCA_AUTH_METHOD") : "password";

if (isset($_GET["site"]) && isset($_GET["user"]) && is_numeric($_GET["site"]) && is_numeric($_GET["user"]) &&
    isset($_GET["logout"]) && $_GET["logout"] == 1) {
	DBLogOut($_SESSION["usertable"]["contestnumber"], $_GET["site"], $_GET["user"]);
        ForceLoad("user.php");
}
if (isset($_POST["usersitenumber"]) && isset($_POST["usernumber"]) && is_numeric($_POST["usersitenumber"]) && 
    is_numeric($_POST["usernumber"]) && isset($_POST["confirmation"]) && $_POST["confirmation"] == "delete") {
	if (!DBDeleteUser($_SESSION["usertable"]["contestnumber"], $_POST["usersitenumber"], $_POST["usernumber"]))
		MSGError("User could not be removed.");
        ForceLoad("user.php");
}

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
        ForceLoad("../index.php");
if($ct["contestlocalsite"]==$ct["contestmainsite"]) $main=true; else $main=false;

if (isset($_POST["username"]) && isset($_POST["userfullname"]) && isset($_POST["userdesc"]) && isset($_POST["userip"]) &&
    isset($_POST["usernumber"]) && isset($_POST["usersitenumber"]) && isset($_POST["userenabled"]) && isset($_POST["usericpcid"]) &&
    isset($_POST["usermultilogin"]) && isset($_POST["usertype"]) && isset($_POST["confirmation"]) &&
    isset($_POST["passwordn1"]) && isset($_POST["passwordn2"]) && isset($_POST["passwordo"]) && $_POST["confirmation"] == "confirm") {
	$param['user'] = htmlspecialchars($_POST["usernumber"]);
	$param['site'] = htmlspecialchars($_POST["usersitenumber"]);
	$param['username'] = htmlspecialchars($_POST["username"]);
	$param['usericpcid'] = htmlspecialchars($_POST["usericpcid"]);
	$param['enabled'] = htmlspecialchars($_POST["userenabled"]);
	$param['multilogin'] = htmlspecialchars($_POST["usermultilogin"]);
	$param['userfull'] = htmlspecialchars($_POST["userfullname"]);
	$param['userdesc'] = htmlspecialchars($_POST["userdesc"]);
	$param['type'] = htmlspecialchars($_POST["usertype"]);
	$param['permitip'] = htmlspecialchars($_POST["userip"]);
	$param['contest'] = $_SESSION["usertable"]["contestnumber"];
	$param['changepass']='t';
	if(isset($_POST['changepass']) && $_POST['changepass'] != 't') $param['changepass']='f';
/*
	$param['user'] = myhtmlspecialchars($_POST["usernumber"]);
	$param['site'] = myhtmlspecialchars($_POST["usersitenumber"]);
	$param['username'] = myhtmlspecialchars($_POST["username"]);
	$param['usericpcid'] = myhtmlspecialchars($_POST["usericpcid"]);
	$param['enabled'] = myhtmlspecialchars($_POST["userenabled"]);
	$param['multilogin'] = myhtmlspecialchars($_POST["usermultilogin"]);
	$param['userfull'] = unsanitizeText($_POST["userfullname"]); //myhtmlspecialchars($_POST["userfullname"]);
	$param['userdesc'] = unsanitizeText($_POST["userdesc"]); //myhtmlspecialchars($_POST["userdesc"]);
	$param['type'] = myhtmlspecialchars($_POST["usertype"]);
	$param['permitip'] = myhtmlspecialchars($_POST["userip"]);
*/


	$passcheck = $_POST["passwordo"];
	$a = DBUserInfo($_SESSION["usertable"]["contestnumber"], $_SESSION["usertable"]["usersitenumber"], $_SESSION["usertable"]["usernumber"], null, false);

  if ($authMethod != "password") {
    $p = $passcheck;
  }
  else {
    $p = myhash($a['userpassword'] . session_id());
  }

	if($p != $passcheck) {
		MSGError('Admin password is incorrect');
	} else {
		if ($_POST["passwordn1"] == $_POST["passwordn2"]) {
			$param['pass'] = bighexsub($_POST["passwordn1"],$a['userpassword']);
			while(strlen($param['pass']) < strlen($a['userpassword'])) $param['pass'] = '0' . $param['pass'];
			if($param['user'] != 1000)
				DBNewUser($param);
		}
		else MSGError ("Passwords don't match.");
	}
	ForceLoad("user.php");
}
else if (isset($_FILES["importfile"]) && isset($_POST["Submit"]) && $_FILES["importfile"]["name"]!="") {
        if ($_POST["confirmation"] == "confirm") {
                $type=myhtmlspecialchars($_FILES["importfile"]["type"]);
                $size=myhtmlspecialchars($_FILES["importfile"]["size"]);
                $name=myhtmlspecialchars($_FILES["importfile"]["name"]);
                $temp=myhtmlspecialchars($_FILES["importfile"]["tmp_name"]);
                if (!is_uploaded_file($temp)) {
                        IntrusionNotify("file upload problem.");
                        ForceLoad("../index.php");
                }

                if (($ar = file($temp)) === false) {
                        IntrusionNotify("Unable to open the uploaded file.");
                        ForceLoad("user.php");
                }
				$userlist=array();
				if(strtolower(substr($name,-4))==".tsv") {
					for ($i=0; $i < count($ar) && strpos($ar[$i], "File_Version\t1") === false; $i++) ;
					if($i >= count($ar)) MSGError('File format not recognized');
					$oklines=0;
					for ($i++; $i < count($ar); $i++) {
                        $x = explode("\t",trim($ar[$i]));
						if(count($x)==7) {
							$param['site']=trim($x[2]);
							$param['username']=trim($x[1]);
							$param['usericpcid']=trim($x[1]);
							$param['usernumber']=trim($x[1]);
							if(trim($x[5])!='')
								$param['userfull']=trim($x[3]) . ' - ' . trim($x[5]);
							else
								$param['userfull']=trim($x[3]);
							$param['userdesc']=trim($x[4]);
							$param['type']='team';
							$param['enabled']='t';
							$param['multilogin']='f';
							$userlist[$param['site'] . '-' . $param['usernumber']] = randstr(6,'0123456789');
							$param['pass']=myhash($userlist[$param['site'] . '-' . $param['usernumber']]);
							$param['changepass']='t';
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['site'] || $main)
								if($param['usernumber'] != 1000 && DBNewUser($param)) {
									$oklines++;
								} else {
									unset($userlist[$param['site'] . '-' . $param['usernumber']]);
									break;
								}
						}
					}
					MSGError($oklines . ' users included/updated successfully');
				} else if(strtolower(substr($name,-4))==".tab") {
					$oklines=0;
					for ($i=0; $i<count($ar); $i++) {
                        $x = explode("\t",trim($ar[$i]));
						if(count($x)==9) {
							$param=array();
							$param['site']=trim($x[1]);
							$param['username']=trim($x[0]);
							$param['usericpcid']=trim($x[0]);
							$param['usernumber']=trim($x[0]);
							if(trim($x[5])!='')
								$param['userfull']=trim($x[3]) . ' - ' . trim($x[5]);
							else
								$param['userfull']=trim($x[3]);
							$param['userdesc']=trim($x[4]);
							$param['type']='team';
							$param['enabled']='t';
							$param['multilogin']='f';
							$userlist[$param['site'] . '-' . $param['usernumber']] = randstr(6,'0123456789');
							$param['pass']=myhash($userlist[$param['site'] . '-' . $param['usernumber']]);
							$param['changepass']='t';
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['site'] || $main)
								if($param['usernumber'] != 1000 && DBNewUser($param)) {
									$oklines++;
								} else {
									unset($userlist[$param['site'] . '-' . $param['usernumber']]);
									break;
								}
						}
					}
					MSGError($oklines . ' users included/updated successfully');
				} else {
					for ($i=0; $i < count($ar) && strpos($ar[$i], "[user]") === false; $i++) ;
					if($i >= count($ar)) MSGError('File format not recognized');
					for ($i++; $i < count($ar) && $ar[$i][0] != "["; $i++) {
                        $x = trim($ar[$i]);
                        if (strpos($x, "user") !== false && strpos($x, "user") == 0) {
                        	$param = array();
							$param['changepass']='t';
							while (strpos($x, "user") !== false && strpos($x, "user") == 0) {
								$tmp = explode ("=", $x, 2);
								switch (trim($tmp[0])) {
									case "usersitenumber":    $param['site']=trim($tmp[1]); break;
									case "username":          $param['username']=trim($tmp[1]); break;
									case "usericpcid":        $param['usericpcid']=trim($tmp[1]); break;
									case "usernumber":        $param['usernumber']=trim($tmp[1]); break;
									case "userfullname":      $param['userfull']=trim($tmp[1]); break;
									case "userdesc":          $param['userdesc']=trim($tmp[1]); break;
									case "usertype":          $param['type']=trim($tmp[1]); break;
									case "userenabled":       $param['enabled']=trim($tmp[1]); break;
									case "usermultilogin":    $param['multilogin']=trim($tmp[1]); break;
									case "userpassword":      $param['pass']=myhash(trim($tmp[1])); break;
									case "userchangepassword": $param['changepass']=trim($tmp[1]); break;
									case "userip":            $param['permitip']=trim($tmp[1]); break;
								}
								$i++;
								if ($i>=count($ar)) break;
								$x = trim($ar[$i]);
							}
							$param['contest']=$_SESSION["usertable"]["contestnumber"];
							if($_SESSION["usertable"]["usersitenumber"] == $param['site'] || $main)
								if($param['usernumber'] != 1000) DBNewUser($param);
                        }
					}
				}
				if(count($userlist) > 0) {
?>
<center>
<br><u><b>TAKE NOTE OF THE USERS AND PASSWORDS AND KEEP THEM SECRET</b></u><br><br>
<table border=1>
 <tr>
  <td nowrap><b>Site</b></td><td><b>User #</b></td>
  <td><b>Password</b></td>
 </tr>
<?php
				  	foreach($userlist as $user => $pass) {
						$x = explode('-',$user);
						echo "<tr><td>" . $x[0] . "</td><td>" . $x[1] . "</td><td>$pass</td></tr>\n";
					}
?>
</table><br><br><u><b>TAKE NOTE OF THE USERS AND PASSWORDS AND KEEP THEM SECRET</b></u></center></body></html>
<?php
	  exit;
				}
        }
        ForceLoad("user.php");
}

if($main)
	$usr = DBAllUserInfo($_SESSION["usertable"]["contestnumber"]);
else
	$usr = DBAllUserInfo($_SESSION["usertable"]["contestnumber"],$_SESSION["usertable"]["usersitenumber"]);
?>

  <script language="javascript">
    function conf2(url) {
      if (confirm("Are you sure?")) {
        document.location=url;
      } else {
        document.location='user.php';
      }
    }
  </script>
<br>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td nowrap><b>User #</b></td>
  <td><b>Site</b></td>
  <td><b>Username</b></td>
  <td><b>ICPC ID</b></td>
  <td><b>Type</b></td>
  <td><b>IP</b></td>
  <td><b>LastLogin</b></td>
  <td><b>LastLogout</b></td>
  <td><b>Enabled</b></td>
  <td><b>Multi</b></td>
  <td><b>Fullname</b></td>
  <td><b>Description</b></td>
 </tr>
<?php
for ($i=0; $i < count($usr); $i++) {
  echo " <tr>\n";
  if(($usr[$i]["usersitenumber"] == $_SESSION["usertable"]["usersitenumber"] || $main==true) && 
	 //$usr[$i]["usertype"] != 'site' && 
	 ($usr[$i]["usernumber"] != $_SESSION["usertable"]["usernumber"] || 
	  $usr[$i]["usersitenumber"] != $_SESSION["usertable"]["usersitenumber"]))
	  echo "  <td nowrap><a href=\"user.php?site=" . $usr[$i]["usersitenumber"] . "&user=" .
		  $usr[$i]["usernumber"] . "\">" . $usr[$i]["usernumber"] . "</a>";
  else
	  echo "  <td nowrap>" . $usr[$i]["usernumber"];
  if($usr[$i]['userenabled'] != 't' && $usr[$i]['userlastlogin'] < 1) echo "(inactive)";
  echo "</td>\n";

  echo "  <td nowrap>" . $usr[$i]["usersitenumber"] . "</td>\n";
  echo "  <td nowrap>" . $usr[$i]["username"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $usr[$i]["usericpcid"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $usr[$i]["usertype"] . "&nbsp;</td>\n";
  if ($usr[$i]["userpermitip"]!="")
    echo "  <td nowrap>" . $usr[$i]["userpermitip"] . "*&nbsp;</td>\n";
  else
    echo "  <td nowrap>" . $usr[$i]["userip"] . "&nbsp;</td>\n";
  if ($usr[$i]["userlastlogin"] < 1)
    echo "  <td nowrap>never</td>\n";
  else
    echo "  <td nowrap>" . dateconv($usr[$i]["userlastlogin"]) . "</td>\n";
  if ($usr[$i]["usersession"] != "")
    echo "  <td nowrap><a href=\"javascript: conf2('user.php?logout=1&site=" . $usr[$i]["usersitenumber"] . "&user=" .
         $usr[$i]["usernumber"] . "')\">Force Logout</a></td>\n";
  else {
    if ($usr[$i]["userlastlogout"] < 1)
      echo "  <td nowrap>never</td>\n";
    else
      echo "  <td nowrap>" . dateconv($usr[$i]["userlastlogout"]) . "</td>\n";
  }
  if ($usr[$i]["userenabled"] == "t")
    echo "  <td nowrap>Yes</td>\n";
  else
    echo "  <td nowrap>No</td>\n";
  if ($usr[$i]["usermultilogin"] == "t")
    echo "  <td nowrap>Yes</td>\n";
  else
    echo "  <td nowrap>No</td>\n";
  echo "  <td nowrap>" . $usr[$i]["userfullname"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $usr[$i]["userdesc"] . "&nbsp;</td>\n";
  echo "</tr>";
}
echo "</table>\n";
if (count($usr) == 0) echo "<br><center><b><font color=\"#ff0000\">NO USERS DEFINED</font></b></center>";

unset($u);
if (isset($_GET["site"]) && isset($_GET["user"]) && is_numeric($_GET["site"]) && is_numeric($_GET["user"]))
  $u = DBUserInfo($_SESSION["usertable"]["contestnumber"], $_GET["site"], $_GET["user"]);

?>
<div id="externalToolbar" <?php if (count($usr) == 0) echo "style=\"display: none\""; ?>></div>
<?php if (count($usr) != 0) { ?>
<style>
  div.grd_headTblCont table thead tr td,
  table.bocaTable tbody tr td {
    position: sticky;
    z-index: 1;
  }

  div.grd_headTblCont table thead tr td:nth-child(-n + 3),
  table.bocaTable tbody tr td:nth-child(-n + 3) {
    position: sticky;
    left: 0;
    z-index: 2;
    background-color: #e0e0d0;
  }

  div.grd_headTblCont table thead tr td:nth-child(2),
  table.bocaTable tbody tr td:nth-child(2) {
    left: 202px;
  }

  div.grd_headTblCont table thead tr td:nth-child(3),
  table.bocaTable tbody tr td:nth-child(3) {
    left: 294px;
  }
</style>
<?php } ?>
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

  // Custom date caster
  function customDateCaster(val) {
    var date = moment(val, 'HH:mm:ss Z - DD/MMM/YYYY', true);
    return date.isValid() ?
      date : val === "never" ?
        moment().add(5, 'years') :
        moment().add(Number.POSITIVE_INFINTITY, 'years');
  }

  // Custom date sorter
  function customDateSorter(n1, n2) {
    if (n1.value < n2.value) {
      return -1;
    }
    if (n2.value < n1.value) {
      return 1;
    }
    return 0;
  }

  var tfConfig = {
    base_path: '../vendor/tablefilter/0.7.3/',
    col_widths: [
      '200px', '90px', '150px',
      '150px', '90px', '175px',
      '275px', '275px', '90px',
      '90px', '275px', '750px'
    ],
    col_types: [
      'number', 'number', 'customstring',
      'customstring', 'customstring', 'ipaddress',
      'customdate', 'customdate', 'customstring',
      'customstring', 'customstring', 'customstring'
    ],
    col_1: 'select',
    col_4: 'select',
    col_8: 'select',
    col_9: 'select',
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
    <?php if (count($usr) != 0) { ?>
    no_results_message: {
      content: '<?php echo "<center><b><font color=\"#ff0000\">NO USERS FOUND</font></b></center>" ?>',
    },
    <?php } ?>
    paging: {
      results_per_page: ['Records: ', [50, 200, 1000, 1000000]],
    },
    // grid layout customisation
    grid_layout: {
      width: '100%',
      <?php if (count($usr) != 0) { ?>
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
          sort.addSortType('customdate', customDateCaster, customDateSorter);
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
<script language="JavaScript" src="../sha256.js"></script>
<script language="JavaScript" src="../hex.js"></script>
<script language="JavaScript">
function computeHASH()
{
	document.form3.passwordn1.value = bighexsoma(js_myhash(document.form3.passwordn1.value),js_myhash(document.form3.passwordo.value));
	document.form3.passwordn2.value = bighexsoma(js_myhash(document.form3.passwordn2.value),js_myhash(document.form3.passwordo.value));
	document.form3.passwordo.value = js_myhash(js_myhash(document.form3.passwordo.value)+'<?php echo session_id(); ?>');
//	document.form3.passwordn1.value = js_myhash(document.form3.passwordn1.value);
//	document.form3.passwordn2.value = js_myhash(document.form3.passwordn2.value);
}

function validatePasswords() {
	const errorMessage = document.getElementById("error-message");
	errorMessage.innerText = "";

	if (document.form3.passwordn1.value !== document.form3.passwordn2.value) {
		document.form3.passwordn2.classList.add("error");
		const msg = document.createElement('p');
		msg.innerText = String.fromCharCode(0x274C) + " User password and confirmation do not match.";
		errorMessage.append(msg);
	} else {
		document.form3.passwordn2.classList.remove("error");
	}
}
</script>

<br><br><center><b>Clicking on a user number will bring the user data for edition.<br>
To import the users, just fill in the import file field.<br>
The file must be in the format defined in the admin's manual.</b></center>

<form name="form1" enctype="multipart/form-data" method="post" action="user.php">
  <input type=hidden name="confirmation" value="noconfirm" />
  <center>
    <table border="0">
      <tr>
        <td width="25%" align=right>Import file:</td>
        <td width="75%">
          <input type="file" name="importfile" size="40">
        </td>
      </tr>
    </table>
  </center>
  <script language="javascript">
    function conf() {
      if (confirm("Confirm?")) {
        document.form1.confirmation.value='confirm';
      }
    }
  </script>
  <center>
      <input type="submit" name="Submit" value="Import" onClick="conf()">
      <input type="reset" name="Submit2" value="Clear">
  </center>
</form>

  <br><br>
  <center>
<b>To create/edit one user, enter the data below.<br>
Note that any changes will overwrite the already defined data.<br>
(Specially care if you use a user number that is already existent.)<br>
<br>
</b>
  </center>

<form name="form3" action="user.php" method="post">
  <input type=hidden name="confirmation" value="noconfirm" />
  <script language="javascript">
    function conf3() {
      <?php if ($authMethod === "password") { ?>
      if (document.form3.passwordo.value === "") {
        alert("Admin password is required.");
        return false;
      }
      <?php } ?>

      if (document.form3.passwordn1.value != document.form3.passwordn2.value) {
        alert("User password and confirmation do not match. Please try again.");
        return false;
      }

      computeHASH();
      if (confirm("Confirm?")) {
        document.form3.confirmation.value='confirm';
      }
    }
<?php 
if (isset($u)) {
  echo "    function conf4() {\n";
  echo "      if (confirm('Confirm the deletion?')) {\n";
  echo "       document.form3.confirmation.value='delete';\n";
  echo "      }\n";
  echo "    }\n";
  $usite = $u['usersitenumber'];
} else
  $usite = $ct['contestlocalsite'];
?>
    function conf5() {
      document.form3.confirmation.value='noconfirm';
      document.form3.passwordn2.classList.remove("error");
    }
  </script>
   <center>
    <table border="0">
      <tr> 
        <td width="35%" align=right>User Site Number:</td>
        <td width="65%">
	  <input type="text" name="usersitenumber" <?php if(!$main) echo "readonly "; echo "value=\"" . $usite . "\""; ?> size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User Number:</td>
        <td width="65%">
	  <input type="text" name="usernumber" value="<?php if(isset($u)) echo $u["usernumber"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Username:</td>
        <td width="65%">
	  <input type="text" name="username" value="<?php if(isset($u)) echo $u["username"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>ICPC ID:</td>
        <td width="65%">
	  <input type="text" name="usericpcid" value="<?php if(isset($u)) echo $u["usericpcid"]; ?>" size="20" maxlength="50" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Type:</td>
        <td width="65%">
		<select name="usertype">
		<option <?php if(!isset($u) || $u["usertype"] == "team") echo "selected"; ?> value="team">Team</option>
		<option <?php if(isset($u)) if($u["usertype"] == "judge") echo "selected"; ?> value="judge">Judge</option>
		<option <?php if(isset($u)) if($u["usertype"] == "admin") echo "selected"; ?> value="admin">Admin</option>
		<option <?php if(isset($u)) if($u["usertype"] == "staff") echo "selected"; ?> value="staff">Staff</option>
		<option <?php if(isset($u)) if($u["usertype"] == "score") echo "selected"; ?> value="score">Score</option>
		<?php if(1 || $main) { ?>
        <option <?php if(isset($u)) if($u["usertype"] == "site") echo "selected"; ?> value="site">Site</option>
        <?php } ?>
		</select>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Enabled:</td>
        <td width="65%">
		<select name="userenabled">
		<option <?php if(!isset($u) || $u["userenabled"] != "f") echo "selected"; ?> value="t">Yes</option>
		<option <?php if(isset($u) && $u["userenabled"] == "f") echo "selected"; ?> value="f">No</option>
		</select>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>MultiLogins (local teams should be set to <b>No</b>):</td>
        <td width="65%">
		<select name="usermultilogin">
		<option <?php if(isset($u) && $u["usermultilogin"] == "t") echo "selected"; ?> value="t">Yes</option>
		<option <?php if(!isset($u) || $u["usermultilogin"] != "t") echo "selected"; ?> value="f">No</option>
		</select>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User Full Name:</td>
        <td width="65%">
	  <input type="text" name="userfullname" value="<?php if(isset($u)) echo $u["userfullname"]; ?>" size="50" maxlength="200" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User Description:</td>
        <td width="65%">
	  <input type="text" name="userdesc" value="<?php if(isset($u)) { 
if($u['usershortinstitution']!='')
  echo '[' . $u['usershortinstitution'] .']';
if($u['userflag']!='') {
  echo '[' . $u['userflag'];
  if($u['usersitename']!='') echo ',' . $u['usersitename'];
  echo ']';
}
echo $u["userdesc"]; } ?>" size="50" maxlength="300" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User IP:</td>
        <td width="65%">
          <input type="text" name="userip" value="<?php if(isset($u)) echo $u["userpermitip"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr <?php if ($authMethod != "password") echo "style='display: none;'"?>> 
        <td width="35%" align=right>Password:</td>
        <td width="65%">
          <input type="password" name="passwordn1" value="" size="20" maxlength="200" />
          <i class="bi bi-eye-slash" id="toggleNewPassword" style="display: none;"></i>
          <script>
            const toggleNewPassword = document.querySelector("#toggleNewPassword");
            const passwordn1 = document.form3.passwordn1;

            passwordn1.addEventListener("keyup", function() {
              if (!this.value) {
                toggleNewPassword.style.display = "none";
              } else {
                toggleNewPassword.style.display = "";
              }
            });

            toggleNewPassword.addEventListener("click", function () {
              // toggle the type attribute
              const type = passwordn1.getAttribute("type") === "password" ? "text" : "password";
              passwordn1.setAttribute("type", type);
              
              // toggle the icon
              this.classList.toggle("bi-eye");
            });
          </script>
        </td>
      </tr>
      <tr <?php if ($authMethod != "password") echo "style='display: none;'"?>> 
        <td width="35%" align=right>Retype Password:</td>
        <td width="65%">
          <input type="password" name="passwordn2" value="" size="20" maxlength="200" />
          <i class="bi bi-eye-slash" id="toggleNewPassword2" style="display: none;"></i>
          <script>
            const toggleNewPassword2 = document.querySelector("#toggleNewPassword2");
            const passwordn2 = document.form3.passwordn2;

            passwordn2.addEventListener("keyup", function() {
              if (!this.value) {
                toggleNewPassword2.style.display = "none";
              } else {
                toggleNewPassword2.style.display = "";
              }
            });

            toggleNewPassword2.addEventListener("click", function () {
              // toggle the type attribute
              const type = passwordn2.getAttribute("type") === "password" ? "text" : "password";
              passwordn2.setAttribute("type", type);
              
              // toggle the icon
              this.classList.toggle("bi-eye");
            });
          </script>
        </td>
      </tr>
      <tr <?php if ($authMethod != "password") echo "style='display: none;'"?>> 
        <td width="35%" align=right>Allow password change:</td>
        <td width="65%">
		<select name="changepass">
		<option <?php if(isset($u) && $u["changepassword"]) echo "selected"; ?> value="t">Yes</option>
		<option <?php if(!isset($u) || !$u["changepassword"]) echo "selected"; ?> value="f">No</option>
		</select>
        </td>
      </tr>
      <tr <?php if ($authMethod != "password") echo "style='display: none;'"?>> 
        <td width="35%" align=right>Admin (this user) Password:</td>
        <td width="65%">
          <input type="password" name="passwordo" value="" size="20" maxlength="200" />
          <i class="bi bi-eye-slash" id="toggleAdminPassword" style="display: none;"></i>
          <script>
            const toggleAdminPassword = document.querySelector("#toggleAdminPassword");
            const passwordo = document.form3.passwordo;

            passwordo.addEventListener("keyup", function() {
              if (!this.value) {
                toggleAdminPassword.style.display = "none";
              } else {
                toggleAdminPassword.style.display = "";
              }
            });
            
            toggleAdminPassword.addEventListener("click", function () {
              // toggle the type attribute
              const type = passwordo.getAttribute("type") === "password" ? "text" : "password";
              passwordo.setAttribute("type", type);
              
              // toggle the icon
              this.classList.toggle("bi-eye");
            });
          </script>
        </td>
      </tr>
    </table>
    <script>
      document.form3.passwordo.addEventListener("keyup", validatePasswords);
      document.form3.passwordn1.addEventListener("keyup", validatePasswords);
      document.form3.passwordn2.addEventListener("keyup", validatePasswords);
    </script>
  </center>
  <center>
      <input type="submit" name="Submit" value="Send" onClick="return conf3()">
<?php if(isset($u)) { ?>
      <input type="submit" name="Delete" value="Delete" onClick="conf4()">
<?php } ?>
      <input type="submit" name="Clear" value="Clear" onClick="conf5()">
<?php if(isset($u)) { ?>
<br><br><b>WARNING: deleting a user will completely remove EVERYTHING related to it (including runs, clarifications, etc).<b><br>
<?php } ?>
  </center>
</form>
<div id="error-message"></div>
</body>
</html>
