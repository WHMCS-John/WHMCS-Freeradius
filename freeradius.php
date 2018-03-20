<?php

use WHMCS\Database\Capsule;

function freeradius_ConfigOptions()
{
    $configarray = array(
     "Group Name" => array( "Type" => "text", "Size" => "25", ),
    );
    return $configarray;
}
function secondsToTime($logintime) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$logintime");
    return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
}

function freeradius_download($params) {

   header( 'Location: http://example.io/VPNClient.msi' ) ;
}

function freeradius_ClientAreaCustomButtonArray() {
    $buttonarray = array(
	 "Download Client" => "download",
	);
	return $buttonarray;
}

function freeradius_ClientArea($params)
{

    $username = $params["username"];
    $serviceid = $params["serviceid"];

    $data = Capsule::table('tblhosting')
        ->where('id', '=', $serviceid)
        ->first(array('nextduedate', 'billingcycle'));

    $nextduedate = $data->nextduedate;
    $billingcycle = $data->billingcycle;
    $year = substr($nextduedate, 0, 4);
    $month = substr($nextduedate, 5, 2);
    $day = substr($nextduedate, 8, 2);

    if ($billingcycle=="Monthly") {
        $new_time=mktime(0, 0, 0, $month-1, $day, $year);
    } elseif ($billingcycle=="Quarterly") {
        $new_time=mktime(0, 0, 0, $month-3, $day, $year);
    } elseif ($billingcycle=="Semi-Annually") {
        $new_time=mktime(0, 0, 0, $month-6, $day, $year);
    } elseif ($billingcycle=="Annually") {
        $new_time=mktime(0, 0, 0, $month, $day, $year-1);
    } elseif ($billingcycle=="Biennially") {
        $new_time=mktime(0, 0, 0, $month, $day, $year-2);
    }

    $startdate = '0000-00-00';
    $enddate = "";

    if (isset($new_time)) {
        $startdate = date("Y-m-d", $new_time);

        if (date("Ymd", $new_time)>=date("Ymd")) {
            if ($billingcycle=="Monthly") {
                $new_time=mktime(0, 0, 0, $month-2, $day, $year);
            } elseif ($billingcycle=="Quarterly") {
                $new_time=mktime(0, 0, 0, $month-6, $day, $year);
            } elseif ($billingcycle=="Semi-Annually") {
                $new_time=mktime(0, 0, 0, $month-12, $day, $year);
            } elseif ($billingcycle=="Annually") {
                $new_time=mktime(0, 0, 0, $month, $day, $year-2);
            } elseif ($billingcycle=="Biennially") {
                $new_time=mktime(0, 0, 0, $month, $day, $year-4);
            }
            $startdate = date("Y-m-d", $new_time);
            if ($billingcycle=="Monthly") {
                $new_time=mktime(0, 0, 0, $month-1, $day, $year);
            } elseif ($billingcycle=="Quarterly") {
                $new_time=mktime(0, 0, 0, $month-3, $day, $year);
            } elseif ($billingcycle=="Semi-Annually") {
                $new_time=mktime(0, 0, 0, $month-6, $day, $year);
            } elseif ($billingcycle=="Annually") {
                $new_time=mktime(0, 0, 0, $month, $day, $year-1);
            } elseif ($billingcycle=="Biennially") {
                $new_time=mktime(0, 0, 0, $month, $day, $year-2);
            }
            $enddate = date("Y-m-d", $new_time);
        }
    }

    $freeradiussql = freeradius_DatabaseConnect($params);
    if (is_string($freeradiussql)) {
        return $freeradiussql; // Error condition
    }

    $query = $freeradiussql
        ->from('radacct')
        ->selectRaw("COUNT(*) AS logins,SUM(radacct.AcctSessionTime) AS logintime,SUM(radacct.AcctInputOctets) AS uploads,SUM(radacct.AcctOutputOctets) AS downloads")
        ->where('radacct.Username', '=', $username)
        ->where('radacct.AcctStartTime', '>=', $startdate);

    if ($enddate) {
        $query = $query->where('radacct.AcctStartTime', '<=', $enddate);
    }

    $row = $query->first();
    $username = $params["username"];
    $password = $params["password"];
    $logins = $row->logins;
    $logintime = $row->logintime;
    $uploads = $row->uploads;
    $downloads = $row->downloads;
    $uploadsmb = round($uploads/1024/1024, 2);
    $downloadsmb = round($downloads/1024/1024, 2);
    if ($uploadsmb>=1) {
        $uploads = "$uploadsmb MB";
    } else {
        $uploads = round($uploads/1024, 2) . " KB";
    }
    if ($downloadsmb>=1) {
        $downloads = "$downloadsmb MB";
    } else {
        $downloads = round($downloads/1024, 2) . " KB";
    }
    if (empty($logintime)) {
	$logintime = "0";
} 

$logintime = secondsToTime($logintime);

    $code = "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.6.0/clipboard.min.js\"></script>

  <table class='table table-bordered table-striped'>
  <tr><th>VPN Username</th><td>

  <div class='input-group'>
    <input type='text' id='username' onClick='this.select();' class='form-control' readonly='' value='$username'>
    <span class='input-group-btn'>
        <button type='button' id='but' class='btn btn-default' data-clipboard-action='copy' data-clipboard-target='#username'>Copy</button>
    </span>
  </div>

  <script>
  new Clipboard('#but');
  </script>

  </td></tr>
  <tr><th>VPN Password</th><td>

  <div class='input-group'>
    <input type='text' id='password' onClick='this.select();' class='form-control' readonly='' value='$password'>
    <span class='input-group-btn'>
        <button type='button' id='pw' class='btn btn-default' data-clipboard-action='copy' data-clipboard-target='#password'>Copy</button>
    </span>
  </div>

  <script>
  new Clipboard('#pw');
  </script>

  </td></tr>
  <tr><th>Amount Of Logins</th><td>$logins</td></tr>
  <tr><th>Total Time Online</th><td>$logintime</td></tr>
  <tr><th>Uploads</th><td>$uploads</td></tr>
  <tr><th>Downloads</th><td>$downloads</td></tr>

  </table>";


    return $code;
}

function freeradius_genUsername($email)
{
    $emaillen = strlen($email);

    $usernameExists = function($email) {
        return Capsule::table('tblhosting')
             ->where('username', '=', $email)
             ->exists();
    };

    $suffix = 0;

    while ($usernameExists($email)) {
        $suffix++;
        $email = substr($email, 0, $emaillen) . $suffix;
    }

    return $email;
}

function freeradius_CreateAccount($params)
{

    $username = $params["username"];
    $password = $params["password"];
    $groupname = $params["configoption1"];
    $email = $params["clientsdetails"]["email"];

    if (!$username) {
        if (isset($params['customfields']['Username'])) {
            $username = $params['customfields']['Username'];
        }
        if (!$username) {
            $username = freeradius_genUsername($email);
        }

        Capsule::table('tblhosting')
            ->where('id', '=', $params["serviceid"])
            ->update(array(
                "username" => $username,
            ));
    }

    $freeradiussql = freeradius_DatabaseConnect($params);
    if (is_string($freeradiussql)) {
        return $freeradiussql; // Error condition
    }

    try {
        $count = $freeradiussql
            ->from('radcheck')
            ->where('username', '=', $username)
            ->count();

        if ($count > 0) {
            return "Username Already Exists";
        }

        $freeradiussql
            ->from('radcheck')
            ->insert(array(
                'username' => $username,
                'attribute' => 'User-Password',
                'value' => $password,
                'op' => ':=',
            ));

        $freeradiussql
            ->from('radusergroup')
            ->insert(array(
                'username' => $username,
                'groupname' => $groupname,
            ));

    } catch (\Exception $e) {
        return "FreeRadius Database Query Error: " . $e->getMessage();
    }

    return "success";

}

function freeradius_SuspendAccount($params)
{

    $username = $params["username"];

    $freeradiussql = freeradius_DatabaseConnect($params);
    if (is_string($freeradiussql)) {
        return $freeradiussql; // Error condition
    }

    try {
        $count = $freeradiussql
            ->from('radcheck')
            ->where('username', '=', $username)
            ->count();

        if (!$count) {
            return "User Not Found";
        }

        $userCount = $freeradiussql
            ->from('radcheck')
            ->where('username', '=', $username)
            ->where('attribute', '=', 'Expiration')
            ->count();

        if ($userCount > 0) {
            /**
             * Illuminate 4.x does not have insertOrUpdate() method
             */
            $freeradiussql
                ->from('radcheck')
                ->where('username', '=', $username)
                ->where('attribute', '=', 'Expiration')
                ->update(
                    array(
                        'value' => date("d F Y"),
                        'op' => ':=',
                    )
                );
        } else {
            $freeradiussql
                ->from('radcheck')
                ->insert(
                    array(
                        'username' => $username,
                        'attribute' => 'Expiration',
                        'value' => date("d F Y"),
                        'op' => ':=',
                    )
                );
        }

    } catch (\Exception $e) {
        return "FreeRadius Database Query Error: " . mysql_error();
    }

    return "success";

}

function freeradius_UnsuspendAccount($params)
{
    $username = $params["username"];

    $freeradiussql = freeradius_DatabaseConnect($params);
    if (is_string($freeradiussql)) {
        return $freeradiussql; // Error condition
    }

    try {
        $affectedRows = $freeradiussql
            ->from('radcheck')
            ->where('username', '=', $username)
            ->where('attribute', '=', 'Expiration')
            ->delete();

        if (!$affectedRows) {
            return "User Not Currently Suspended";
        }

    } catch (\Exception $e) {
        return "FreeRadius Database Query Error: " . mysql_error();
    }

    return "success";

}

function freeradius_TerminateAccount($params)
{

    $username = $params["username"];

    $freeradiussql = freeradius_DatabaseConnect($params);
    if (is_string($freeradiussql)) {
        return $freeradiussql; // Error condition
    }

    try {
        $freeradiussql
            ->from('radreply')
            ->where('username', '=', $username)
            ->delete();

        $freeradiussql
            ->from('radusergroup')
            ->where('username', '=', $username)
            ->delete();

        $freeradiussql
            ->from('radcheck')
            ->where('username', '=', $username)
            ->delete();

    } catch (\Exception $e) {
        return "FreeRadius Database Query Error: " . $e->getMessage();
    }

    return "success";

}

function freeradius_ChangePassword($params)
{

    $username = $params["username"];
    $password = $params["password"];

    $freeradiussql = freeradius_DatabaseConnect($params);
    if (is_string($freeradiussql)) {
        return $freeradiussql; // Error condition
    }

    try {
        $count = $freeradiussql
            ->from('radcheck')
            ->where('username', '=', $username)
            ->count();

        if (!$count) {
            return "User Not Found";
        }

        $freeradiussql
            ->from('radcheck')
            ->where('username', '=', $username)
            ->where('attribute', '=', 'User-Password')
            ->update(array(
                'value' => $password,
            ));

    } catch (\Exception $e) {
        return "FreeRadius Database Query Error: " . mysql_error();
    }

    return "success";

}

function freeradius_ChangePackage($params)
{
    $username = $params["username"];
    $groupname = $params["configoption1"];

    $freeradiussql = freeradius_DatabaseConnect($params);
    if (is_string($freeradiussql)) {
        return $freeradiussql; // Error condition
    }

    try {
        $count = $freeradiussql
            ->from('radusergroup')
            ->where('username', '=', $username)
            ->count();

        if (!$count) {
            return "User Not Found";
        }

        $freeradiussql
            ->from('radusergroup')
            ->where('username', '=', $username)
            ->update(array(
                'groupname' => $groupname,
            ));

    } catch (\Exception $e) {
        return "FreeRadius Database Query Error: " . mysql_error();
    }

    return "success";

}

/**
 * @param $params
 * @return \Illuminate\Database\Query\Builder|null|string
 */
function freeradius_DatabaseConnect($params)
{
    $pdo = null;
    try {
        $pdo = Capsule::getInstance()->getConnection('freeradius');
    } catch (\Exception $e) {
        // freeradius connect has not yet be created

        $sqlhost = $params["serverip"];

        if (empty($sqlhost)) {
            $sqlhost = $params["serverhostname"];
        }

        $config = array(
            'driver' => 'mysql',
            'host' => $sqlhost,
            // super hacky fix
            'database' => $params["serveraccesshash"],
            'username' => $params["serverusername"],
            'password' => $params["serverpassword"],
            'charset'  => 'utf8',
        );

        try {
            Capsule::getInstance()->addConnection(
                $config,
                'freeradius'
            );

            $pdo = Capsule::getInstance()->getConnection('freeradius');

        } catch (\Exception $e) {
            return "Unable to connect to FreeRadius Database.  "
            . "Please check FreeRadius server configuration.  "
            . $e->getMessage();
        }
    }

    if (is_object($pdo)) {
        if (method_exists($pdo, 'query')) {
            $ret = $pdo->query();
        } else {
            $processor = $pdo->getPostProcessor();
            $ret = new \Illuminate\Database\Query\Builder($pdo, $pdo->getQueryGrammar(), $processor);
        }
    } else {
        $ret = $pdo;
    }

    return $ret;
}
