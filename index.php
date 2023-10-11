<?php
// load the Zabbix Php API which is included in this build (tested on Zabbix v6.4 and PhpZabbixApi v3.0.0 )
require_once __DIR__.'/vendor/autoload.php';

use Confirm\ZabbixApi\Exception;
use Confirm\ZabbixApi\ZabbixApi;

$token = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'; //zabbixuser1 token

// Connect to Zabbix API.
$api = new ZabbixApi(
    'http://example.com/api_jsonrpc.php', // url api zabbix server
    'zabbixuser1', 	// readonly zabbixuser1
    'xxxxxxxxxx', 	// passowrd zabbixuser1
    null, 			// if use HTTP Basic Authorization - enter a user
    null, 			// if use HTTP Basic Authorization - enter a pass
    $token
);

$api->setDefaultParams(array(
        'output' => 'extend',
));

?>
<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8">
        <title>Zabbix</title>
        <link rel="stylesheet" type="text/css" href="style/reset.css" />
        <link rel="stylesheet" type="text/css" href="style/theme-alt.css" />
        <script src="lib/js/jquery-2.2.0.min.js"></script>
        <!-- added the masonry js so all blocks are better alligned -->
        <script src="lib/js/masonry.pkgd.min.js"></script>
<body id="bg-two">
<!-- START GET RENDER DATE - Which will show date and time of generating this file -->
<div id="timestamp">
    <div id="date"><?php echo date("d F Y", time()); ?></div>
    <div id="time"><?php echo date("H:i", time()); ?></div>
</div>
<!-- END GET RENDER DATE -->

<!-- We could use the Zabbix HostGroup name here, but would not work in a nice way when using a dozen of hostgroups, yet! So we hardcoded it here. --> 
<div id="sheetname">Status</div>
<br />

<?php
// get hostgroupid with hosts
    $groupids = $api->hostgroupGet(array(
        //'output' => 'extend',
        'selectHosts' => 'extend',
    ));

// get all hosts from each groupid
    foreach($groupids as $groupid) {
        $groupname = $groupid['name'];
        $hosts = $groupid['hosts'];
        //$gid = $groupid['groupid'];

        if ($hosts) {

        $count = "0";
        //      echo "<div class=\"groupbox\">"; // Again, we dont want to use the groupfunction yet
        //      echo "<div class=\"title\">" . $groupname . "</div>";

            usort($hosts, function ($a, $b) {
                if ($a['name'] == $b) return 0;
                return ($a['name'] < $b['name'] ? -1 : 1);
            });

            // print all host IDs
                foreach($hosts as $host) {

                        // Check if host is not disabled, we don't want them!
                        $flaghost = $host['flags'];
                        if ($flaghost == "0" && $count == "0") {
                                echo "<br /><div class=\"title\">" . $groupname . "</div>";
                echo "<div class=\"groupbox js-masonry\" data-masonry-options='{ \"itemSelector\": \".hostbox\" }'\">";
                                $count++;
                        }

                        if ($flaghost == "0" && $count != "0") {

                        $hostid = $host['hostid'];
                                $hostname = $host['name'];
                                $maintenance = $host['maintenance_status'];
                                $trigger = $api->triggerGet(array(
                                        'output' => 'extend',
                                        'hostids' => $hostid,
                    'expandDescription' => 1,
                    'only_true' => 1,
                    'monitored' => 1,
                    'withLastEventUnacknowledged' => 1,
                    'sortfield' => 'priority',
                    'sortorder' => 'DESC',
                    'active' => 1, // include trigger state active not active
                    'withUnacknowledgedEvents' => 1 // show only unacknowledgeevents
                                ));

                                if ($trigger) {

                                        // Highest Priority error
                                        $hostboxprio = $trigger[0]['priority'];
                                        //First filter the hosts that are in maintenance and assign the maintenance class if is true
                                        if ($maintenance != "0") {
                                                echo "<div class=\"hostbox maintenance\">".PHP_EOL;
                                        }
                                        // If hosts are not in maintenance, check for trigger(s) and assign the appropriate class to the box 
                                        else {
                                                echo "<div class=\"hostbox blink nok" . $hostboxprio . "\">".PHP_EOL;
                                        }
                                        echo "<div class=\"title\">" . $hostname . "</div><div class=\"hostid\">" . $hostid . "</div>".PHP_EOL;
                                        $count = "0";
                                        foreach ($trigger as $event) {
                                                if ($count++ <= 2 ) { 
                                                $priority = $event['priority'];
                                                        $description = $event['description'];
                                        // Remove hostname or host.name in description
                                                        //$search = array('{HOSTNAME}', '{HOST.NAME}');
                                                        //$description = str_replace($search, "", $description);
                                        // View
                                                        echo "<div class=\"description nok" . $priority ."\" title=\"$description\">" .  mb_strimwidth($description, 0, 54, "..."). "</div>".PHP_EOL;
                                                } else {
                                                        break;
                                                }
                                        }
                                        }
                                        // If there are no trigger(s) for the host found, assign the "ok" class to the box
                                        else {
                                        echo "<div class=\"hostbox ok\">".PHP_EOL;
                                        echo "<div class=\"title\">" . $hostname . "</div><div class=\"hostid\">" . $hostid . "</div>".PHP_EOL;
                                }
                                echo "</div>";
                        }
                }
        if ($count != "0") {echo "</div>".PHP_EOL;}
        }
    }

?>
<!-- Second piece of js to gracefully reload the page (value in ms) -->
<script>
        function ReloadPage() {
           location.reload();
        };
        $(document).ready(function() {
          setTimeout("ReloadPage()", 31000);
        });
</script>
</body>
</html>