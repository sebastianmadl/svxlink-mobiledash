<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
set_time_limit(0); ignore_user_abort(false);
@ini_set('zlib.output_compression','0');
@ini_set('output_buffering','0');
@ini_set('implicit_flush','1');
while(ob_get_level()>0){@ob_end_flush();}
@ob_implicit_flush(1);

require_once dirname(__DIR__).'/settings.php';
$logFile=$svxLogFile;
$cfg=file_exists($svxConfPath)?parse_ini_file($svxConfPath,true,INI_SCANNER_RAW):[];
$defTG=$cfg['ReflectorLogic']['DEFAULT_TG']??'23211';

// ── DMRIds Parser ─────────────────────────────────────────────────────────────
// Lädt dl3el/DMRIds.dat und baut ein Array [CALLSIGN => Vorname]
// Format pro Zeile: "DMRID CALLSIGN Vorname [Nachname ...]"
function parseDmrIds($file) {
    if (!file_exists($file)) return [];
    $result = [];
    $fh = fopen($file, 'r');
    if (!$fh) return [];
    while (($line = fgets($fh)) !== false) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $parts = preg_split('/\s+/', $line, 4);
        // Spalten: 0=DMRID, 1=CALLSIGN, 2=Vorname, [3=Nachname]
        if (count($parts) >= 3) {
            $cs = strtoupper(trim($parts[1]));
            $result[$cs] = trim($parts[2]);
        }
    }
    fclose($fh);
    return $result;
}

// Callsign normalisieren: OE1SXM-HS → OE1SXM
function normCs($cs) {
    return strtoupper(preg_replace('/-.*$/', '', trim($cs)));
}

$dmrFile = dirname(__DIR__, 2) . '/dl3el/DMRIds.dat';
$hasDmr  = file_exists($dmrFile);

// ─────────────────────────────────────────────────────────────────────────────

function sse($e,$d){echo "event: $e\ndata: ".json_encode($d)."\n\n";if(ob_get_level())ob_flush();flush();}
function pl($line,$defTG){
    $line=trim($line);if(!$line)return null;
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
        return['e'=>'mode','date'=>date('d.m.Y'),'time'=>$m[1],'state'=>(stripos($m[2],'De')===0||stripos($m[2],'Stop')===0)?'OFF':'ON','name'=>$m[3]];
    if(preg_match('/(\d{2}:\d{2}:\d{2}):.*\bModule([A-Za-z0-9_]+)\b.*\b(activated|deactivated|started|stopped)\b/i',$line,$m))
        return['e'=>'mode','date'=>date('d.m.Y'),'time'=>$m[1],'state'=>(stripos($m[3],'de')===0||stripos($m[3],'stop')===0)?'OFF':'ON','name'=>$m[2]];
    if(preg_match('/(\d{2}\.\d{2}\.\d{4})\s+(\d{2}:\d{2}:\d{2}):.*Incoming EchoLink connection from\s+(\S+)\s+\(([^)]*)\)/',$line,$m))
        return['e'=>'el','date'=>$m[1],'time'=>$m[2],'cs'=>strtoupper($m[3]),'name'=>$m[4]];
    return null;
}

// ── Schritt 1: Log sofort parsen und senden (ohne Namen) ─────────────────────
$hist=[];
if($logFile&&file_exists($logFile)){
    $raw=shell_exec("tail -20000 ".escapeshellarg($logFile)." | grep -aE 'Talker start on|Talker stop on|Turning the transmitter|squelch is|Selecting TG|Authentication OK|Connection established|Heartbeat timeout|Connection refused|EchoLink connection from|Activating module|Deactivating module|Starting module|Stopping module|Module.*(activated|deactivated|started|stopped)' | tail -500");
    foreach(array_filter(explode("\n",$raw))as $l){$p=pl($l,$defTG);if($p)$hist[]=$p;}
}else{
    sse('err',['msg'=>'Log not found — check SVXLOGPATH in configs/config.php']);
}
// Sofort senden — Uhrzeit, Callsigns, TGs sind bereits sichtbar
sse('history',['lines'=>$hist,'defTG'=>$defTG,'hasDmr'=>$hasDmr]);
if(!$logFile||!file_exists($logFile))exit;

// ── Schritt 2: DMRIds im Hintergrund laden und nachsenden ────────────────────
$dmrNames = [];
if($hasDmr){
    $dmrNames = parseDmrIds($dmrFile);
    // Nur die Callsigns die auch in der History vorkommen mitschicken
    // (spart Übertragungsgröße — nicht alle 250k Einträge)
    $usedCs = [];
    foreach($hist as $ev){
        if(isset($ev['cs'])){
            $norm = strtoupper(preg_replace('/-.*$/','',$ev['cs']));
            if(isset($dmrNames[$norm])) $usedCs[$norm] = $dmrNames[$norm];
        }
    }
    sse('dmrnames',['names'=>$usedCs]);
}

$proc=proc_open("tail -F -n 0 ".escapeshellarg($logFile),[['pipe','r'],['pipe','w'],['pipe','w']],$pipes);
if(!is_resource($proc)){sse('err',['msg'=>'Cannot tail: '.$logFile]);exit;}
stream_set_blocking($pipes[1],false);
$lastHb=time();
while(!connection_aborted()){
    $line=fgets($pipes[1]);
    if($line!==false&&$line!==''){
        $p=pl($line,$defTG);
        if($p){
            // Namen für live eingehende Events nachschlagen
            if($p['e']==='start' && isset($p['cs'])){
                $norm=strtoupper(preg_replace('/-.*$/','',$p['cs']));
                $p['name']=$dmrNames[$norm]??'';
            }
            sse('log',$p);
            // ── Reconnect erkannt → Config + History neu einlesen und pushen ──
            if($p['e']==='conn' && !empty($p['ok'])){
                // Komplette Config neu einlesen
                $newCfg    = file_exists($svxConfPath) ? parse_ini_file($svxConfPath,true,INI_SCANNER_RAW) : [];
                $newDefTG  = $newCfg['ReflectorLogic']['DEFAULT_TG']  ?? $defTG;
                $newCs     = $newCfg['ReflectorLogic']['CALLSIGN']     ?? '';
                $newHost   = $newCfg['ReflectorLogic']['HOSTS']        ?? ($newCfg['ReflectorLogic']['DNS_DOMAIN'] ?? '');
                if (strpos($newHost,',')!==false) $newHost = trim(explode(',',$newHost)[0]);
                $newMonTGs = array_values(array_filter(array_map('trim',
                    explode(',', $newCfg['ReflectorLogic']['MONITOR_TGS'] ?? $newDefTG)
                )));
                // $defTG für laufende Session aktualisieren
                $defTG = $newDefTG;

                $newHist=[];
                $raw2=shell_exec("tail -20000 ".escapeshellarg($logFile)." | grep -aE 'Talker start on|Talker stop on|Turning the transmitter|squelch is|Selecting TG|Authentication OK|Connection established|Heartbeat timeout|Connection refused|EchoLink connection from|Activating module|Deactivating module|Starting module|Stopping module|Module.*(activated|deactivated|started|stopped)' | tail -500");
                foreach(array_filter(explode("\n",$raw2))as $l){$ev=pl($l,$defTG);if($ev)$newHist[]=$ev;}
                if($dmrNames){
                    foreach($newHist as &$ev){
                        if($ev['e']==='start' && isset($ev['cs'])){
                            $norm=strtoupper(preg_replace('/-.*$/','',$ev['cs']));
                            $ev['name']=$dmrNames[$norm]??'';
                        }
                    }
                    unset($ev);
                }
                sse('refresh',[
                    'lines'   => $newHist,
                    'defTG'   => $newDefTG,
                    'monTGs'  => $newMonTGs,
                    'callsign'=> $newCs,
                    'host'    => $newHost,
                ]);
            }
        }
    }
    else{
        if(time()-$lastHb>=15){
            $t=@file_get_contents($cpuTempFile);
            $la=sys_getloadavg();$nc=max(1,(int)shell_exec('nproc 2>/dev/null'));
            $mem=@file_get_contents($memInfoFile);$mp='—';
            if($mem&&preg_match('/MemTotal:\s+(\d+)/',$mem,$mt2)&&preg_match('/MemAvailable:\s+(\d+)/',$mem,$ma2))
                $mp=round((1-$ma2[1]/$mt2[1])*100).'%';
            $up=@file_get_contents($uptimeFile);$us='—';
            if($up){$s=floatval(explode(' ',$up)[0]);$us=floor($s/86400).'d '.floor(($s%86400)/3600).'h '.floor(($s%3600)/60).'m';}
            sse('hb',['ts'=>date('H:i:s'),'cpu'=>round($la[0]*100/$nc,1).'%','tmp'=>$t?round($t/1000).'°C':'—','mem'=>$mp,'up'=>$us]);
            $lastHb=time();
        }
        usleep(200000);
    }
}
proc_terminate($proc);
@fclose($pipes[0]);@fclose($pipes[1]);@fclose($pipes[2]);proc_close($proc);
