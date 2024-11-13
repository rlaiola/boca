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

if(isset($_GET["order"]))
$order = myhtmlspecialchars($_GET["order"]);
else $order='';
if(isset($_GET["user"]))
$user = myhtmlspecialchars($_GET["user"]);
else $user='';
if(isset($_GET["site"]))
$site = myhtmlspecialchars($_GET["site"]);
else $site='';
if(isset($_GET["type"]))
$type = myhtmlspecialchars($_GET["type"]);
else $type='';
if(isset($_GET["ip"]))
$ip = myhtmlspecialchars($_GET["ip"]);
else $ip='';
$get="&order=${order}&user=${user}&site=${site}&type=${type}&ip=${ip}";
if (isset($_GET["limit"]) && $_GET["limit"]>0)
  $limit = myhtmlspecialchars($_GET["limit"]);
// else $limit = 50;
else $limit = 1000000;
$log = DBGetLogs($order, $_SESSION["usertable"]["contestnumber"], 
		$site, $user, $type, $ip, $limit);
?>
<br>
<table class="bocaTable" width="100%" border=1 style="width: 100%">
 <tr>
  <td><b>Site</b></td>
  <td nowrap><b>User #</b></td>
  <td><b>IP</b></td>
  <td><b>Type</b></td>
  <td><b>Date</b></td>
  <td><b>Description</b></td>
  <td><b>Status</b></td>
 </tr>
<?php
for ($i=0; $i<count($log); $i++) {
  echo " <tr>\n";
  echo "  <td nowrap><a href=\"log.php?site=" . $log[$i]["site"] . "&limit=$limit\">" . $log[$i]["site"] . "</a></td>\n";
  echo "  <td nowrap><a href=\"log.php?user=" . $log[$i]["user"] . "&limit=$limit\">" . $log[$i]["user"] . "</a></td>\n";
  echo "  <td nowrap><a href=\"log.php?ip=" . $log[$i]["ip"] . "&limit=$limit\">" . $log[$i]["ip"] . "</a></td>\n";
  echo "  <td nowrap><a href=\"log.php?type=" . $log[$i]["type"] . "&limit=$limit\">" . $log[$i]["type"] . "</a></td>\n";
  echo "  <td nowrap>" . dateconv($log[$i]["date"]) . "</td>\n";
  echo "  <td nowrap>" . $log[$i]["data"] . "</td>\n";
  echo "  <td nowrap>" . $log[$i]["status"] . "</td>\n";
  echo "</tr>\n";
}
echo "</table>\n";
if (count($log) == 0) echo "<br><center><b><font color=\"#ff0000\">NO LOGS AVAILABLE</font></b></center>";

?>
<div id="externalToolbar" <?php if (count($log) == 0) echo "style=\"display: none\""; ?>></div>
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
    return date;
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
      '7%', '10%', '10%',
      '7%', '20%', '39%',
      '7%'
    ],
    col_types: [
      'number', 'number', 'ipaddress',
      'customstring', 'customdate', 'customstring',
      'customstring'
    ],
    col_0: 'select',
    col_1: 'select',
    col_3: 'select',
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
    <?php if (count($log) != 0) { ?>
    no_results_message: {
      content: '<?php echo "<center><b><font color=\"#ff0000\">NO LOGS FOUND</font></b></center>" ?>',
    },
    <?php } ?>
    paging: {
      results_per_page: ['Records: ', [50, 200, 1000, 1000000]],
    },
    // grid layout customisation
    grid_layout: {
      width: '100%',
      <?php if (count($log) != 0) { ?>
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

<br>
<center>
<!-- <a href="log.php?limit=50<?php echo $get; ?>">50</a>
<a href="log.php?limit=200<?php echo $get; ?>">200</a>
<a href="log.php?limit=1000<?php echo $get; ?>">1000</a>
<a href="log.php?limit=1000000<?php echo $get; ?>">no limit</a> -->
</body>
</html>
