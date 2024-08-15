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

if(($ct = DBContestInfo($_SESSION["usertable"]["contestnumber"])) == null)
	ForceLoad("../index.php");
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

<br><br><br>

<!-- Adiciona o botão de direção de ordenação -->
<div>
    <button id="sort-direction">⬆</button> Sort by Name
</div>

<table width="100%" border=1 id="problem-table">
 <thead>
  <tr>
    <th><b>Name</b><br><input class='filter-input' type='text' placeholder='Filter Name' onkeyup='filterTable(this, 0)'></th>
    <th><b>Basename</b><br><input class='filter-input' type='text' placeholder='Filter Basename' onkeyup='filterTable(this, 1)'></th>
    <th><b>Fullname</b><br><input class='filter-input' type='text' placeholder='Filter Fullname' onkeyup='filterTable(this, 2)'></th>
    <th><b>Descfile</b><br><input class='filter-input' type='text' placeholder='Filter Descfile' onkeyup='filterTable(this, 3)'></th>
  </tr>
 </thead>
 <tbody>
<?php
// Conteúdo da tabela gerado dinamicamente
$prob = DBGetProblems($_SESSION["usertable"]["contestnumber"]);
for ($i=0; $i<count($prob); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap>" . $prob[$i]["problem"];
  if($prob[$i]["color"] != "")
          echo " <img alt=\"".$prob[$i]["colorname"]."\" width=\"20\" ".
              "src=\"" . balloonurl($prob[$i]["color"]) ."\" />\n";
  echo "</td>\n";
  echo "  <td nowrap>" . $prob[$i]["basefilename"] . "&nbsp;</td>\n";
  echo "  <td nowrap>" . $prob[$i]["fullname"] . "&nbsp;</td>\n";
  if (isset($prob[$i]["descoid"]) && $prob[$i]["descoid"] != null && isset($prob[$i]["descfilename"])) {
    echo "  <td nowrap><a class='descfile-link' href=\"../filedownload.php?" . filedownload($prob[$i]["descoid"], $prob[$i]["descfilename"]) .
        "\">" . basename($prob[$i]["descfilename"]) . "</a></td>\n";
  }
  else
    echo "  <td nowrap>no description file available</td>\n";
  echo " </tr>\n";
}
echo "</tbody>";
echo "</table>";
if (count($prob) == 0) echo "<br><center><b><font color=\"#ff0000\">NO PROBLEMS AVAILABLE YET</font></b></center>";

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('sort-direction').addEventListener('click', toggleSortDirection);
});

let sortDirection = true; // true for ascending, false for descending

function toggleSortDirection() {
    sortDirection = !sortDirection;
    document.getElementById('sort-direction').innerText = sortDirection ? '⬆' : '⬇';
    sortTable();
}

function sortTable() {
    const table = document.getElementById('problem-table');
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const index = 0; // Order by name
    
    rows.sort((a, b) => {
        const cellA = a.children[index].innerText.toLowerCase();
        const cellB = b.children[index].innerText.toLowerCase();
        
        if (!isNaN(cellA) && !isNaN(cellB)) {
            return sortDirection ? cellA - cellB : cellB - cellA;
        } else {
            return sortDirection ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable(input, column) {
    const filter = input.value.toLowerCase();
    const table = document.getElementById('problem-table');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        if (cells[column]) {
            const txtValue = cells[column].textContent || cells[column].innerText;
            rows[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }
}
</script>

</body>
</html>
