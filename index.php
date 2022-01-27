<?php

include('UserInfo.php');

$directory = "/home/Mwah.ro/cdn.mwah.ro/i";
$files = scandir($directory);
$filess = count($files)-2;
$disk = '/dev/vda1';
$date1 = new DateTime("now");
$date2 = new DateTime("2022-01-03");
$interval = $date1->diff($date2);

class ServerInfo
{
	private static function uptime()
	{
		$uptime = strtok(exec('cat /proc/uptime'), '.');


		return array(
			'days' => sprintf("%2d", ($uptime / (3600*24))),
			'hours' => sprintf("%2d", (($uptime % (3600*24)) / 3600)),
			'minutes' => sprintf("%2d", ($uptime % (3600*24) % 3600) / 60),
			'seconds' => sprintf("%2d", ($uptime % (3600*24) % 3600) % 60)
		);
	}


	private static function cpupercent()
	{
		$cpu = exec('top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk \'{print 100 - $1}\'');


		return array(
			'percent' => $cpu
		);
	}


	private static function network()
	{
		$interfaces = array();
		exec('netstat -i | tail -n +3', $netstat);
		$netstat = implode("\n", $netstat);
		if ($netstat)
		{
			$lines = preg_split("/\n/", $netstat, -1, PREG_SPLIT_NO_EMPTY);
			foreach($lines as $line)
			{
				$network = preg_split("/\s+/", $line);
				$interfaces[] = $network['0'];
			}

			foreach($interfaces as $key => $interface)
			{
				$rx = exec('cat /sys/class/net/' . $interface . '/statistics/rx_bytes');
				$tx = exec('cat /sys/class/net/' . $interface . '/statistics/tx_bytes');
				$interfaces[$key] = array('name' => $interface, 'rxb' => $rx, 'txb' => $tx, 'rxp' => self::convertSize($rx, true), 'txp' => self::convertSize($tx, true));
			}
		}
		return $interfaces;
	}


	private static function memory()
	{
		$results['ram'] = array('total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0);


		$buffed = explode("\n", shell_exec('cat /proc/meminfo'));


		foreach($buffed as $buffer)
		{
			if(preg_match('/^MemTotal:\s+(.*)\s*kB/i', $buffer, $bufferMatched)) {
				$results['ram']['total'] = $bufferMatched['1'];
			}elseif(preg_match('/^MemFree:\s+(.*)\s*kB/i', $buffer, $bufferMatched)) {
				$results['ram']['free'] = $bufferMatched['1'];
			}
		}


		$results['ram']['used'] = $results['ram']['total'] - $results['ram']['free'];
		$results['ram']['percent'] = round(($results['ram']['used'] * 100) / $results['ram']['total']);


		return($results);
	}


	private static function users()
	{
		$users = preg_split('/=/', shell_exec('who -q'));


		return((int) $users['1']);
	}

	private static function load()
	{
		$load = preg_split("/\s/", shell_exec('cat /proc/loadavg'), 4);
		unset($load['3']);


		return( implode(' ', $load) );
	}


	private static function convertSize($size, $bytes = false)
	{
		if($bytes == false)
			$size = $size * 1024;

		$filesizename = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');


		return $size ? number_format(round($size / pow(1024, ($i = floor(log($size, 1024)))), 2), 2, ',', '') . ' ' . $filesizename[$i] : '0 Bytes';
	}


	public static function info()
	{
		$info['servername'] = $_SERVER['SERVER_NAME'];
		$info['serverport'] = $_SERVER['SERVER_PORT'];
		$info['date'] = date('d/m-Y H:i');
		$info['uptime'] = self::uptime();
		$info['users'] = self::users();
		$info['load'] = self::load();
		$memory = self::memory();
		$info['ram']['percent'] = $memory['ram']['percent'];
		$info['ram']['free'] = self::convertSize($memory['ram']['free']);
		$info['ram']['used'] = self::convertSize($memory['ram']['used']);
		$info['ram']['total'] = self::convertSize($memory['ram']['total']);
		$info['cpu'] = self::cpupercent();
		$info['network'] = self::network();
		$info['ip'] = (getenv('HTTP_X_FORWARDED_FOR') ? getenv('HTTP_X_FORWARDED_FOR') : getenv('REMOTE_ADDR'));
		$info['ua'] = $_SERVER['HTTP_USER_AGENT'];
		return $info;
	}
}
$info = ServerInfo::info();



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>CDN.MWAH.RO - STATUS</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Aldrich&amp;display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Atomic+Age&amp;display=swap">
    <link rel="stylesheet" href="assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="assets/fonts/font-awesome.min.css">
    <link rel="stylesheet" href="assets/fonts/ionicons.min.css">
    <link rel="stylesheet" href="assets/fonts/fontawesome5-overrides.min.css">
    <link rel="stylesheet" href="assets/css/Navigation-Clean.css">
    <link rel="stylesheet" href="assets/css/Social-Icons.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
</head>

<body style="font-size: 20px;font-weight: bold;text-align: center;">
    <nav class="navbar navbar-dark sticky-top bg-dark navigation-clean" style="background: rgb(0,0,1);">
        <div class="container-fluid"><a class="navbar-brand" href="https://cdn.mwah.ro/" style="font-weight: bold;font-family: 'Atomic Age', serif;font-size: 25px;color: var(--bs-purple);text-shadow: 0px 0px 20px var(--bs-purple);"><i class="fa fa-cloud-upload"></i>&nbsp;CDN.MWAH.RO</a><button data-bs-toggle="collapse" class="navbar-toggler" data-bs-target="#navcol-1"><span class="visually-hidden">Toggle navigation</span><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse text-center" id="navcol-1" style="font-family: 'Atomic Age', serif;font-weight: bold;">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="#">File Manager</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="card"></div>
    <div class="container">
        <div class="row">
            <div class="col-md-12" style="text-align: center;height: 120px;margin-top: 20px;"><strong style="font-size: 50px;font-family: 'Atomic Age', serif;"><i class="fa fa-user"></i>&nbsp;Client Info</strong></div>
        </div>
        <div class="row">
            <div class="col-md-3" style="text-align: center;font-family: Aldrich, sans-serif;font-size: 20px;font-weight: bold;"><strong style="font-size: 20px;font-family: system-ui;">IP<br><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" class="bi bi-hdd-network">
                        <path d="M4.5 5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zM3 4.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"></path>
                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H8.5v3a1.5 1.5 0 0 1 1.5 1.5h5.5a.5.5 0 0 1 0 1H10A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5H.5a.5.5 0 0 1 0-1H6A1.5 1.5 0 0 1 7.5 10V7H2a2 2 0 0 1-2-2V4zm1 0v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1zm6 7.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5z"></path>
                    </svg>&nbsp;<?php echo UserInfo::get_ip(); ?><br><br></strong></div>
            <div class="col-md-3" style="text-align: center;font-size: 20px;font-weight: bold;"><strong>Browser<br><i class="fa fa-safari"></i>&nbsp;<?php echo UserInfo::get_browser(); ?><br><br></strong></div>
            <div class="col-md-3" style="text-align: center;font-size: 20px;font-weight: bold;"><strong>OS<br>&nbsp;<i class="fa fa-windows"></i>&nbsp;<?php echo UserInfo::get_os(); ?><br><br></strong></div>
            <div class="col-md-3" style="text-align: center;font-size: 20px;font-weight: bold;"><strong>Device<br><i class="fa fa-laptop"></i>&nbsp;<?php echo UserInfo::get_device(); ?><br><br></strong></div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-12" style="text-align: center;font-weight: bold;font-size: 50px;font-family: 'Atomic Age', serif;height: 120px;margin-top: 20px;"><strong><i class="fa fa-cloud-upload"></i>&nbsp;Cloud Info</strong></div>
        </div>
        <div class="row">
            <div class="col-md-3" style="text-align: center;font-weight: bold;font-size: 20px;"><strong>Files<br><i class="fa fa-file-image-o">&nbsp;</i><?php echo $filess; ?><br></strong></div>
            <div class="col-md-3" style="text-align: center;font-weight: bold;font-size: 20px;"><strong>Total space<br><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" class="bi bi-hdd">
                        <path d="M4.5 11a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zM3 10.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"></path>
                        <path d="M16 11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9.51c0-.418.105-.83.305-1.197l2.472-4.531A1.5 1.5 0 0 1 4.094 3h7.812a1.5 1.5 0 0 1 1.317.782l2.472 4.53c.2.368.305.78.305 1.198V11zM3.655 4.26 1.592 8.043C1.724 8.014 1.86 8 2 8h12c.14 0 .276.014.408.042L12.345 4.26a.5.5 0 0 0-.439-.26H4.094a.5.5 0 0 0-.44.26zM1 10v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-1a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1z"></path>
                    </svg>&nbsp;<?php echo shell_exec("df | grep '^/dev/vda1' | awk '{s+=$2} END {print s/1048576}'");?>GB<br></strong></div>
            <div class="col-md-3" style="text-align: center;font-weight: bold;font-size: 20px;"><strong>Space used<br>&nbsp;<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" class="bi bi-hdd-fill">
                        <path d="M0 10a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-1zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zm2 0a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1zM.91 7.204A2.993 2.993 0 0 1 2 7h12c.384 0 .752.072 1.09.204l-1.867-3.422A1.5 1.5 0 0 0 11.906 3H4.094a1.5 1.5 0 0 0-1.317.782L.91 7.204z"></path>
                    </svg>&nbsp;<?php echo shell_exec("du -sh /home/Mwah.ro/cdn.mwah.ro/i | awk '{print $1}' | tr -d M") ?>MB<br></strong></div>
            <div class="col-md-3" style="text-align: center;font-weight: bold;font-size: 20px;"><strong>Online since<br><svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" class="bi bi-cloud-check">
                        <path fill-rule="evenodd" d="M10.354 6.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708 0z"></path>
                        <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"></path>
                    </svg>&nbsp;<?php echo "" . $interval->days . " days "; ?><br></strong></div>
        </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-12" style="font-size: 50px;font-family: 'Atomic Age', serif;font-weight: bold;height: 120px;margin-top: 20px;"><strong><i class="fas fa-server"></i>&nbsp;Server Info</strong></div>
        </div>
        <div class="row">
            <div class="col-md-3" style="font-weight: bold;font-size: 20px;"><strong>RAM usage<br>&nbsp;<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none">
                        <path d="M5 4C5 4.55228 4.55228 5 4 5C3.44772 5 3 4.55228 3 4C3 3.44772 3.44772 3 4 3C4.55228 3 5 3.44772 5 4Z" fill="currentColor"></path>
                        <path d="M9 4C9 4.55228 8.55228 5 8 5C7.44772 5 7 4.55228 7 4C7 3.44772 7.44772 3 8 3C8.55228 3 9 3.44772 9 4Z" fill="currentColor"></path>
                        <path d="M12 5C12.5523 5 13 4.55228 13 4C13 3.44772 12.5523 3 12 3C11.4477 3 11 3.44772 11 4C11 4.55228 11.4477 5 12 5Z" fill="currentColor"></path>
                        <path d="M17 4C17 4.55228 16.5523 5 16 5C15.4477 5 15 4.55228 15 4C15 3.44772 15.4477 3 16 3C16.5523 3 17 3.44772 17 4Z" fill="currentColor"></path>
                        <path d="M20 5C20.5523 5 21 4.55228 21 4C21 3.44772 20.5523 3 20 3C19.4477 3 19 3.44772 19 4C19 4.55228 19.4477 5 20 5Z" fill="currentColor"></path>
                        <path d="M5 20C5 20.5523 4.55228 21 4 21C3.44772 21 3 20.5523 3 20C3 19.4477 3.44772 19 4 19C4.55228 19 5 19.4477 5 20Z" fill="currentColor"></path>
                        <path d="M9 20C9 20.5523 8.55228 21 8 21C7.44772 21 7 20.5523 7 20C7 19.4477 7.44772 19 8 19C8.55228 19 9 19.4477 9 20Z" fill="currentColor"></path>
                        <path d="M12 21C12.5523 21 13 20.5523 13 20C13 19.4477 12.5523 19 12 19C11.4477 19 11 19.4477 11 20C11 20.5523 11.4477 21 12 21Z" fill="currentColor"></path>
                        <path d="M17 20C17 20.5523 16.5523 21 16 21C15.4477 21 15 20.5523 15 20C15 19.4477 15.4477 19 16 19C16.5523 19 17 19.4477 17 20Z" fill="currentColor"></path>
                        <path d="M20 21C20.5523 21 21 20.5523 21 20C21 19.4477 20.5523 19 20 19C19.4477 19 19 19.4477 19 20C19 20.5523 19.4477 21 20 21Z" fill="currentColor"></path>
                        <path d="M5 12C5.55228 12 6 11.5523 6 11C6 10.4477 5.55228 10 5 10C4.44772 10 4 10.4477 4 11C4 11.5523 4.44772 12 5 12Z" fill="currentColor"></path>
                        <path d="M20 13C20 13.5523 19.5523 14 19 14C18.4477 14 18 13.5523 18 13C18 12.4477 18.4477 12 19 12C19.5523 12 20 12.4477 20 13Z" fill="currentColor"></path>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0 9C0 7.34315 1.34315 6 3 6H21C22.6569 6 24 7.34315 24 9V15C24 16.6569 22.6569 18 21 18H3C1.34315 18 0 16.6569 0 15V9ZM3 8H21C21.5523 8 22 8.44772 22 9V15C22 15.5523 21.5523 16 21 16H3C2.44772 16 2 15.5523 2 15V9C2 8.44772 2.44772 8 3 8Z" fill="currentColor"></path>
                    </svg>&nbsp;<?php echo $info['ram']['percent'] ?>%<br></strong></div>
            <div class="col-md-3" style="font-weight: bold;font-size: 20px;"><strong>Uptime<br>&nbsp;<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" class="bi bi-clock-history">
                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.615.789a6.996 6.996 0 0 0-.418-.302zm1.834 1.79a6.99 6.99 0 0 0-.653-.796l.724-.69c.27.285.52.59.747.91l-.818.576zm.744 1.352a7.08 7.08 0 0 0-.214-.468l.893-.45a7.976 7.976 0 0 1 .45 1.088l-.95.313a7.023 7.023 0 0 0-.179-.483zm.53 2.507a6.991 6.991 0 0 0-.1-1.025l.985-.17c.067.386.106.778.116 1.17l-1 .025zm-.131 1.538c.033-.17.06-.339.081-.51l.993.123a7.957 7.957 0 0 1-.23 1.155l-.964-.267c.046-.165.086-.332.12-.501zm-.952 2.379c.184-.29.346-.594.486-.908l.914.405c-.16.36-.345.706-.555 1.038l-.845-.535zm-.964 1.205c.122-.122.239-.248.35-.378l.758.653a8.073 8.073 0 0 1-.401.432l-.707-.707z"></path>
                        <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0v1z"></path>
                        <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"></path>
                    </svg>&nbsp;<?php echo shell_exec("echo $(awk '{print $1}' /proc/uptime) / 3600 | bc") ?> hours<br><br></strong></div>
            <div class="col-md-3" style="font-weight: bold;font-size: 20px;"><strong>Logged users<br><i class="fa fa-user"></i>&nbsp;<?php echo shell_exec("who | sort | awk '{print $1}'"); ?><br><br><br><br></strong></div>
            <div class="col-md-3" style="font-weight: bold;font-size: 20px;"><strong>CPU usage<br>&nbsp;<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16" class="bi bi-cpu">
                        <path d="M5 0a.5.5 0 0 1 .5.5V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2A2.5 2.5 0 0 1 14 4.5h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14a2.5 2.5 0 0 1-2.5 2.5v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14A2.5 2.5 0 0 1 2 11.5H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2v-1H.5a.5.5 0 0 1 0-1H2A2.5 2.5 0 0 1 4.5 2V.5A.5.5 0 0 1 5 0zm-.5 3A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13h7a1.5 1.5 0 0 0 1.5-1.5v-7A1.5 1.5 0 0 0 11.5 3h-7zM5 6.5A1.5 1.5 0 0 1 6.5 5h3A1.5 1.5 0 0 1 11 6.5v3A1.5 1.5 0 0 1 9.5 11h-3A1.5 1.5 0 0 1 5 9.5v-3zM6.5 6a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"></path>
                    </svg>&nbsp;<?php echo $info['cpu']['percent']; ?>%<br></strong></div>
        </div>
    </div>
    <div class="social-icons"><a href="https://instagram.com/cosmincw" target="_blank"><i class="fab fa-instagram"></i></a><a href="https://facebook.com/CreedoW" target="_blank"><i class="icon ion-social-facebook"></i></a><a href="https://discord.com/users/664741770591993858" target="_blank"><i class="fab fa-discord"></i></a><a href="https://steamcommunity.com/id/creedow" target="_blank"><i class="fab fa-steam"></i></a></div>
    <script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>