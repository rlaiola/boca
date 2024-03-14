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

ob_start();
header ("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache");
header ("Content-Type: text/html; charset=utf-8");
session_start();
$_SESSION["loc"] = dirname($_SERVER['PHP_SELF']);
if($_SESSION["loc"]=="/") $_SESSION["loc"] = "";
$_SESSION["locr"] = dirname(__FILE__);
if($_SESSION["locr"]=="/") $_SESSION["locr"] = "";

require_once("globals.php");
require_once("db.php");
require_once("ldap.php");
require_once("googleclient.php");

if (!isset($_GET["name"])) {
	if (ValidSession())
	  DBLogOut($_SESSION["usertable"]["contestnumber"], 
		   $_SESSION["usertable"]["usersitenumber"], $_SESSION["usertable"]["usernumber"],
		   $_SESSION["usertable"]["usertype"]=='admin');
	session_unset();
	session_destroy();
	session_start();
	$_SESSION["loc"] = dirname($_SERVER['PHP_SELF']);
	if($_SESSION["loc"]=="/") $_SESSION["loc"] = "";
	$_SESSION["locr"] = dirname(__FILE__);
	if($_SESSION["locr"]=="/") $_SESSION["locr"] = "";
}
if(isset($_GET["getsessionid"])) {
	echo session_id();
	exit;
}

$coo = array();
if(isset($_COOKIE['biscoitobocabombonera'])) {
  $coo = explode('-',$_COOKIE['biscoitobocabombonera']);
  if(count($coo) != 2 ||
     strlen($coo[1])!=strlen(myhash('xxx')) ||
     !is_numeric($coo[0]) ||
     !ctype_alnum($coo[1]))
    $coo = array();
}
if(count($coo) != 2)
  setcookie('biscoitobocabombonera',time() . '-' . myhash(time() . rand() . time() . rand()),time() + 240*3600);

ob_end_flush();

require_once('version.php');

$authMode = getenv("BOCA_AUTH_METHOD") ? getenv("BOCA_AUTH_METHOD") : "password";

?>
<title>BOCA Online Contest Administrator <?php echo $BOCAVERSION; ?> - Login</title>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel=stylesheet href="Css.php" type="text/css">
<script language="JavaScript" src="sha256.js"></script>
<script language="JavaScript">

function submitForm() {
    const authMode = "<?php echo $authMode; ?>";

    if (authMode === 'password') {
      computeHASH();
    } else {
      document.form1.method = 'post';
      document.form1.action = 'index.php';

      document.form1.submit();
    }
}
function computeHASH()
{
	var userHASH, passHASH;
	userHASH = document.form1.name.value;
	passHASH = js_myhash(js_myhash(document.form1.password.value)+'<?php echo session_id(); ?>');
	document.form1.name.value = '';
	document.form1.password.value = '                                                                                 ';
	document.location = 'index.php?name='+userHASH+'&password='+passHASH;
}
</script>
<?php
if ($authMode == 'google') {
  $googleClient = new GoogleClient();
}
$_SESSION["google_authorized"] = isset($googleClient) && $googleClient->authorized();

if(function_exists("globalconf") && function_exists("sanitizeVariables")) {
  if((isset($_GET["name"]) && $_GET["name"] != "") || (isset($_POST["name"]) && $_POST["name"] != "") || $_SESSION["google_authorized"]) {
    
    if ($_SESSION["google_authorized"]) {
      $_SESSION["google_token"] = $googleClient->client->getAccessToken();

      $userData = $googleClient->data;
      $username = substr($userData->email, 0, strpos($userData->email, '@'));
      $userdomain = $userData->hd ? $userData->hd : substr($userData->email, strpos($userData->email, '@')+1, strlen($userData->email));

      $allowedDomains = getenv("BOCA_AUTH_ALLOWED_DOMAINS") ? getenv("BOCA_AUTH_ALLOWED_DOMAINS") : "gmail.com";

      // echo $userData->email . "<br>";
      // echo $username . "<br>";
      // echo $userdomain . "<br>";
      // echo $allowedDomains . "<br>";
      // exit;

      // if ($allowedDomains) {
        if (in_array($userdomain, explode(",", $allowedDomains))) {
          $usertable = DBLogIn($username, null);
        } else {
          $usertable = false;
          MSGError('User is not authorized.');
        }
      // } else {
      //   $usertable = DBLogIn($username, null);
      // }
    } 
    else {
      $name = isset($_GET["name"]) ? $_GET["name"] : $_POST["name"];
      $password = isset($_GET["password"]) ? $_GET["password"] : $_POST["password"];
      
      $usertable = DBLogIn($name, $password);
    }
    
    if(!$usertable) {
      if ($_SESSION["google_authorized"]) $googleClient->logout();
      ForceLoad("index.php");
    }
    else {
      if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null) {
        if ($_SESSION["google_authorized"]) $googleClient->logout();
        ForceLoad("index.php");
      }
      if($ct["contestlocalsite"]==$ct["contestmainsite"]) $main=true; else $main=false;
      if(isset($_GET['action']) && $_GET['action'] == 'transfer') {
        echo "TRANSFER OK";
      } else {
        if($main && $_SESSION["usertable"]["usertype"] == 'site') {
          MSGError('Direct login of this user is not allowed');
          unset($_SESSION["usertable"]);
          if ($_SESSION["google_authorized"]) $googleClient->logout();
          ForceLoad("index.php");
          exit;
        }
        echo "<script language=\"JavaScript\">\n";
        echo "document.location='" . $_SESSION["usertable"]["usertype"] . "/index.php';\n";
        echo "</script>\n";
      }
      exit;
    }
  }
} else {
  echo "<script language=\"JavaScript\">\n";
  echo "alert('Unable to load config files. Possible file permission problem in the BOCA directory.');\n";
  echo "</script>\n";
}
?>
</head>
<body onload="document.form1.name.focus()">
<table width="100%" height="100%" border="0">
  <tr align="center" valign="middle"> 
    <td> 
      <form name="form1" action="javascript:submitForm()">
        <div align="center"> 
          <table border="0" align="center">
            <tr> 
              <td nowrap>
                <div align="center"><font face="Verdana, Arial, Helvetica, sans-serif" size="+1">
				BOCA Login</font></div>
              </td>
            </tr>
            <tr>
              <td valign="top" id="localLogin" <?php if ($authMode == 'google') { ?> style="display: none;" <?php } ?>> 
                <table border="0" align="left">
                  <tr> 
                    <td><font face="Verdana, Arial, Helvetica, sans-serif" > 
                      Name
                      </font></td>
                    <td> 
                      <input type="text" name="name">
                    </td>
                  </tr>
                  <tr> 
                    <td><font face="Verdana, Arial, Helvetica, sans-serif" >Password</font></td>
                    <td> 
                      <input type="password" name="password">
                    </td>
                  </tr>
                </table>
                <input type="submit" name="Submit" value="Login">
              </td>
            </tr>
          </table>
        </div>
      </form>

      <?php if ($authMode == 'google') { ?>

      <form>
        <input id="googleLogin" 
               type="button" 
               value="Login with Google" 
               style="
                    background-image: url(https://accounts.scdn.co/sso/images/new-google-icon.72fd940a229bc94cf9484a3320b3dccb.svg);
                    padding-left: 25px;
                    background-repeat: no-repeat;
                    background-size: contain;
                    padding-left: 25px;"
               onclick="window.location.href='<?php echo $googleClient->generateAuthUrl(); ?>'">
      </form>
      <a id="localLoginLink" style="color: initial;" href="javascript:toggleLoginMethod();">
        <font face="Verdana, Arial, Helvetica, sans-serif" size="-1">
        Login with local user
        </font>
      </a>
      <a id="googleLoginLink" style="color: initial; display: none;" href="javascript:toggleLoginMethod();">
        <font face="Verdana, Arial, Helvetica, sans-serif" size="-1">
        Login with Google
        </font>
      </a>
      <script>
      function toggleLoginMethod() {
        const localLogin = document.getElementById("localLogin");
        const usernameField = document.getElementsByTagName("input")[0];
        const googleLoginLink = document.getElementById("googleLoginLink");
        const googleLogin = document.getElementById("googleLogin");
        const localLoginLink = document.getElementById("localLoginLink");

        localLogin.style.display = localLogin.style.display === "none" ? "block" : "none";
        googleLoginLink.style.display = googleLoginLink.style.display === "none" ? "block" : "none";
        googleLogin.style.display = googleLogin.style.display === "none" ? "block" : "none";
        localLoginLink.style.display = localLoginLink.style.display === "none" ? "block" : "none";
        // Set focus
        if (localLogin.style.display === "block")
          usernameField.focus();
        else googleLogin.focus();
      }

      document.getElementById("googleLogin").focus();
      </script>

      <?php } ?>
    </td>
  </tr>
</table>
<?php include('footnote.php'); ?>
