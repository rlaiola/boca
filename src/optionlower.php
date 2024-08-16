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
$loc = $_SESSION['loc'];

if (isset($_GET["username"]) && isset($_GET["userfullname"]) && isset($_GET["userdesc"]) && 
    isset($_GET["passwordo"]) && isset($_GET["passwordn"])) {
  if($_SESSION["usertable"]["usertype"] == 'team') {
    MSGError('Updates are not allowed');
    ForceLoad("option.php");
  }    

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

    // Obter valores dos campos do formulário
    username = document.form1.username.value;
    userdesc = document.form1.userdesc.value;
    userfull = document.form1.userfull.value;

    // Obter elementos de senha
    var passwordo = document.form1.passwordo.value;
    var passwordn1 = document.form1.passwordn1.value;
    var passwordn2 = document.form1.passwordn2.value;

    // Verificar se as novas senhas correspondem
    if (passwordn1 !== passwordn2) {
        // Exibir mensagem de erro se as senhas não correspondem
        var errorMessage = document.getElementById('error-message');
        errorMessage.innerText = 'New passwords do not match. Please try again.';
        errorMessage.style.display = 'block';
        return; // Não prosseguir com a alteração da senha
    }

    // Resetar a mensagem de erro caso as senhas correspondam
    var errorMessage = document.getElementById('error-message');
    errorMessage.style.display = 'none';

    // Prosseguir com a lógica existente para computar os hashes e enviar a solicitação
    passHASHo = js_myhash(js_myhash(passwordo) + '<?php echo session_id(); ?>');
    passHASHn = bighexsoma(js_myhash(passwordn2), js_myhash(passwordo));

    // Limpar os campos de senha por segurança
    document.form1.passwordo.value = ' ';
    document.form1.passwordn1.value = ' ';
    document.form1.passwordn2.value = ' ';

    // Redirecionar para a página de atualização com os parâmetros hash
    document.location = 'option.php?username=' + username + '&userdesc=' + userdesc + '&userfullname=' + userfull + '&passwordo=' + passHASHo + '&passwordn=' + passHASHn;
}

// Função para redefinir o formulário e limpar a mensagem de erro
function clearForm() {
    document.form1.reset();
    var errorMessage = document.getElementById('error-message');
    errorMessage.style.display = 'none';
}
</script>

<br><br>
<!-- Aviso de erro -->
<div id="error-message" style="display: none; color: red; text-align: center; margin-bottom: 10px;"></div>

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
	  <input type="password" name="passwordo" size="20" maxlength="200" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>New Password:</td>
        <td width="65%">
	  <input type="password" name="passwordn1" size="20" maxlength="200" />
        </td>
      </tr>
      <tr> 
        <td width="35%" align=right>Retype New Password:</td>
        <td width="65%">
	  <input type="password" name="passwordn2" size="20" maxlength="200" />
        </td>
      </tr>
    </table>
  </center>
  <center>
      <input type="submit" name="Submit" value="Send">
      <input type="button" name="Clear" value="Clear" onclick="clearForm()">
  </center>
</form>

</body>
</html>
