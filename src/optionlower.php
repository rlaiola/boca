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

//optionlower.php: parte de baixo da tela de option.php, que eh igual para
//			todos os usuarios
require_once("globals.php");

if(!ValidSession()) { // || $_SESSION["usertable"]["usertype"] == 'team') {
        InvalidSession("optionlower.php");
        ForceLoad("index.php");
}

if ($_SESSION["usertable"]["authmethod"] != "password") {
  echo "<br><br><center><b>UPDATES ARE NOT ALLOWED</b></center>"; 
  exit;
}

$loc = $_SESSION['loc'];

if (isset($_GET["username"]) && isset($_GET["userfullname"]) && isset($_GET["userdesc"]) && 
    isset($_GET["passwordo"]) && isset($_GET["passwordn"])) {
  // if($_SESSION["usertable"]["usertype"] == 'team') {
  //   MSGError('Updates are not allowed');
  //   ForceLoad("option.php");
  // }    

	$username = myhtmlspecialchars($_GET["username"]);
	$userfullname = myhtmlspecialchars($_GET["userfullname"]);
	$userdesc = myhtmlspecialchars($_GET["userdesc"]);
	$passwordo = $_GET["passwordo"];
	$passwordn = $_GET["passwordn"];
	DBUserUpdate($_SESSION["usertable"]["contestnumber"],
		     $_SESSION["usertable"]["usersitenumber"],
		     $_SESSION["usertable"]["usernumber"],
		     $_SESSION["usertable"]["username"], // $username, but users should not change their names
		     $userfullname,
		     $userdesc,
		     $passwordo,
		     $passwordn);
	ForceLoad("option.php");
}

$a = DBUserInfo($_SESSION["usertable"]["contestnumber"],
                $_SESSION["usertable"]["usersitenumber"],
                $_SESSION["usertable"]["usernumber"]);

?>

<script language="JavaScript" src="<?php echo $loc; ?>/sha256.js"></script>
<script language="JavaScript" src="<?php echo $loc; ?>/hex.js"></script>
<script language="JavaScript">
function computeHASH()
{
	var username, userdesc, userfull, passHASHo, passHASHn;
	if (document.form1.passwordn1.value == document.form1.passwordo.value) {
		alert("New password is the same as the old one. Please choose a different password.");
		return;
	}
	if (document.form1.passwordn1.value != document.form1.passwordn2.value) {
		alert("New password and confirmation do not match. Please try again.");
		return;
	}

	username = document.form1.username.value;
	userdesc = document.form1.userdesc.value;
	userfull = document.form1.userfull.value;

	passHASHo = js_myhash(js_myhash(document.form1.passwordo.value)+'<?php echo session_id(); ?>');
	passHASHn = bighexsoma(js_myhash(document.form1.passwordn2.value),js_myhash(document.form1.passwordo.value));
	document.form1.passwordo.value = '                                                         ';
	document.form1.passwordn1.value = '                                                         ';
	document.form1.passwordn2.value = '                                                         ';
	document.location='option.php?username='+username+'&userdesc='+userdesc+'&userfullname='+userfull+'&passwordo='+passHASHo+'&passwordn='+passHASHn;
}

function validatePasswords() {
	const errorMessage = document.getElementById("error-message");
	errorMessage.innerText = "";

	if (document.form1.passwordn1.value !== "" &&
		document.form1.passwordn1.value === document.form1.passwordo.value) {
		document.form1.passwordn1.classList.add("error");
		const msg = document.createElement('p');
		msg.innerText = String.fromCharCode(0x274C) + " New password is the same as the old one.";
		errorMessage.append(msg);
	} else {
		document.form1.passwordn1.classList.remove("error");
	}

	if (document.form1.passwordn1.value !== document.form1.passwordn2.value) {
		document.form1.passwordn2.classList.add("error");
		const msg = document.createElement('p');
		msg.innerText = String.fromCharCode(0x274C) + " New password and confirmation do not match.";
		errorMessage.append(msg);
	} else {
		document.form1.passwordn2.classList.remove("error");
	}
}
</script>

<br><br>
<form name="form1" action="javascript:computeHASH()">
  <center>
    <table border="0">
      <tr> 
        <td width="35%" align=right>Username:</td>
        <td width="65%">
	  <input type="text" readonly name="username" value="<?php echo $a["username"]; ?>" size="20" maxlength="20" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User Full Name:</td>
        <td width="65%">
	  <input type="text" readonly name="userfull" value="<?php echo $a["userfullname"]; ?>" size="50" maxlength="50" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>User Description:</td>
        <td width="65%">
	  <input type="text" name="userdesc" value="<?php echo $a["userdesc"]; ?>" size="50" maxlength="250" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Old Password:</td>
        <td width="65%">
        <input type="password" id="passwordo" name="passwordo" size="20" maxlength="200" required />
        <i class="bi bi-eye-slash" id="toggleOldPassword" style="display: none;"></i>
        <script>
          const toggleOldPassword = document.querySelector("#toggleOldPassword");
          const passwordo = document.form1.passwordo;

          passwordo.addEventListener("keyup", function() {
            if (!this.value) {
              toggleOldPassword.style.display = "none";
            } else {
              toggleOldPassword.style.display = "";
            }
          });
          
          toggleOldPassword.addEventListener("click", function () {
            // toggle the type attribute
            const type = passwordo.getAttribute("type") === "password" ? "text" : "password";
            passwordo.setAttribute("type", type);
            
            // toggle the icon
            this.classList.toggle("bi-eye");
          });
        </script>
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>New Password:</td>
        <td width="65%">
        <input type="password" id="passwordn1" name="passwordn1" size="20" maxlength="200" required />
        <i class="bi bi-eye-slash" id="toggleNewPassword" style="display: none;"></i>
        <script>
          const toggleNewPassword = document.querySelector("#toggleNewPassword");
          const passwordn1 = document.form1.passwordn1;

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
      <tr> 
        <td width="35%" align=right>Retype New Password:</td>
        <td width="65%">
        <input type="password" id="passwordn2" name="passwordn2" size="20" maxlength="200" required />
        <i class="bi bi-eye-slash" id="toggleNewPassword2" style="display: none;"></i>
        <script>
          const toggleNewPassword2 = document.querySelector("#toggleNewPassword2");
          const passwordn2 = document.form1.passwordn2;

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
    </table>
    <script>
      document.form1.passwordo.addEventListener("keyup", validatePasswords);
      document.form1.passwordn1.addEventListener("keyup", validatePasswords);
      document.form1.passwordn2.addEventListener("keyup", validatePasswords);
    </script>
  </center>
  <center>
      <input type="submit" name="Submit" value="Send">
      <input type="reset" name="Clear" value="Clear">
  </center>
</form>
<div id="error-message"></div>

</body>
</html>
