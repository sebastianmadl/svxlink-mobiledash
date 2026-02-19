<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');
set_time_limit(0);
ignore_user_abort(false);

@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
@ini_set('implicit_flush', '1');
while (ob_get_level() > 0) { @ob_end_flush(); }
@ob_implicit_flush(1);

$logFile = '/var/log/svxlink';
if (is_dir($logFile)) {
  foreach (['/var/log/svxlink/svxlink.log','/var/log/svxlink/svxlink'] as $cand) {
    if (is_file($cand)) { $logFile = $cand; break; }
  }
}

$cfg = file_exists('/etc/svxlink/svxlink.conf') ? parse_ini_file('/etc/svxlink/svxlink.conf',true,INI_SCANNER_RAW) : [];
$defTG = $cfg['ReflectorLogic']['DEFAULT_TG'] ?? '23211';

function sse($e,$d){echo "event: $e\ndata: ".json_encode($d)."\n\n";if(ob_get_level())ob_flush();flush();}

function pl($line,$defTG){
    $line=trim($line); if(!$line)return null;
    if(preg_match('/^(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2}:\d{2}):.*Talker start on TG\s+#(\d+):\s+(\S+)/',$line,$m))
        return['e'=>'start','date'=>$m[1],'time'=>$m[2],'tg'=>$m[3],'cs'=>strtoupper($m[4]),'primary'=>($m[3]===$defTG)];
    if(preg_match('/^(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2}:\d{2}):.*Talker stop on TG\s+#(\d+):\s+(\S+)/',$line,$m))
        return['e'=>'stop','time'=>$m[2],'tg'=>$m[3],'cs'=>strtoupper($m[4])];
    if(preg_match('/(\d{2}:\d{2}:\d{2}):.*Turning the transmitter (ON|OFF)/',$line,$m))
        return['e'=>'tx','time'=>$m[1],'state'=>$m[2]];
    if(preg_match('/(\d{2}:\d{2}:\d{2}):.*squelch is (OPEN|CLOSED)/',$line,$m))
        return['e'=>'sq','time'=>$m[1],'state'=>$m[2]];
    if(preg_match('/(\d{2}:\d{2}:\d{2}):.*ReflectorLogic.*Selecting TG\s+#(\d+)/',$line,$m))
        return['e'=>'tgsel','time'=>$m[1],'tg'=>$m[2]];
    if(preg_match('/(\d{2}:\d{2}:\d{2}):.*ReflectorLogic.*(Authentication OK|Connection established|Heartbeat timeout|Connection refused|Locally ordered disconnect)/',$line,$m))
        return['e'=>'conn','time'=>$m[1],'ok'=>(strpos($m[2],'OK')!==false||strpos($m[2],'established')!==false),'msg'=>$m[2]];
    if(preg_match('/(\d{2}:\d{2}:\d{2}):.*\b(Activating|Deactivating|Starting|Stopping)\b.*\bmodule\b\s+([A-Za-z0-9_]+)/i',$line,$m))
        return['e'=>'mode','time'=>$m[1],'state'=> (stripos($m[2],'De')===0 || stripos($m[2],'Stop')===0) ? 'OFF' : 'ON','name'=>$m[3]];
    if(preg_match('/(\d{2}:\d{2}:\d{2}):.*\bModule([A-Za-z0-9_]+)\b.*\b(activated|deactivated|started|stopped)\b/i',$line,$m))
        return['e'=>'mode','time'=>$m[1],'state'=> (stripos($m[3],'de')===0 || stripos($m[3],'stop')===0) ? 'OFF' : 'ON','name'=>$m[2]];
    if(preg_match('/(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2}:\d{2}):.*Incoming EchoLink connection from\s+(\S+)\s+\(([^)]*)\)/',$line,$m))
        return['e'=>'el','date'=>$m[1],'time'=>$m[2],'cs'=>strtoupper($m[3]),'name'=>$m[4]];
    return null;
}

$hist=[];
if(file_exists($logFile)){
    $raw=shell_exec("tail -20000 ".escapeshellarg($logFile)." | grep -aE 'Talker start on|Talker stop on|Turning the transmitter|squelch is|Selecting TG|Authentication OK|Connection established|Heartbeat timeout|Connection refused|EchoLink connection from|Activating module|Deactivating module|Starting module|Stopping module|Module.*(activated|deactivated|started|stopped)' | tail -500");
    foreach(array_filter(explode("\n",$raw)) as $l){$p=pl($l,$defTG);if($p)$hist[]=$p;}
}
sse('history',['lines'=>$hist,'defTG'=>$defTG]);

$proc=proc_open("tail -F -n 0 ".escapeshellarg($logFile),[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
if(!is_resource($proc)){sse('err',['msg'=>'Cannot tail log']);exit;}
stream_set_blocking($pipes[1],false);
$lastHb=time();

while(!connection_aborted()){
    $line=fgets($pipes[1]);
    if($line!==false&&$line!==''){
        $p=pl($line,$defTG);if($p)sse('log',$p);
    } else {
        if(time()-$lastHb>=15){
            $t=@file_get_contents('/sys/class/thermal/thermal_zone0/temp');
            $la=sys_getloadavg();$nc=max(1,(int)shell_exec('nproc 2>/dev/null'));
            $mem=@file_get_contents('/proc/meminfo');$mp='—';
            if($mem&&preg_match('/MemTotal:\s+(\d+)/',$mem,$mt2)&&preg_match('/MemAvailable:\s+(\d+)/',$mem,$ma2))
                $mp=round((1-$ma2[1]/$mt2[1])*100).'%';
            $up=@file_get_contents('/proc/uptime');$us='—';
            if($up){$s=floatval(explode(' ',$up)[0]);$us=floor($s/86400).'d '.floor(($s%86400)/3600).'h '.floor(($s%3600)/60).'m';}
            sse('hb',['ts'=>date('H:i:s'),'cpu'=>round($la[0]*100/$nc,1).'%','tmp'=>$t?round($t/1000).'°C':'—','mem'=>$mp,'up'=>$us]);
            $lastHb=time();
        }
        usleep(200000);
    }
}
proc_terminate($proc);
@fclose($pipes[0]);@fclose($pipes[1]);@fclose($pipes[2]);proc_close($proc);
