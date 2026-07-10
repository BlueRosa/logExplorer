<?php

// lecteurs pour le syslog

function lireSyslogRFC3164(string $fichier): array
{
    $logs = [];

    foreach (file($fichier) as $ligne) {

        $regex = '/^(?<date>\w+\s+\d+\s+\d+:\d+:\d+) (?<host>\S+) (?<service>[^:]+): (?<message>.*)$/';

        if (preg_match($regex, trim($ligne), $m)) {

            $logs[] = [
                "date" => $m["date"],
                "host" => $m["host"],
                "service" => $m["service"],
                "message" => $m["message"],
                "raw" => trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireSyslogRFC5424(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $regex='/^<(?<priority>\d+)>(?<version>\d+) (?<date>\S+) (?<host>\S+) (?<app>\S+) (?<pid>\S+) (?<msgid>\S+) (?<message>.*)$/';

        if(preg_match($regex,trim($ligne),$m)){

            $logs[]=[
                "priority"=>$m["priority"],
                "date"=>$m["date"],
                "host"=>$m["host"],
                "application"=>$m["app"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireLinuxSyslog(string $fichier): array
{
    $logs=[];

    $handle=fopen($fichier,"r");

    if(!$handle)return $logs;

    $regex='/^(?<date>\w{3} \d+ \d+:\d+:\d+) (?<host>\S+) (?<service>[^:]+): (?<message>.*)$/';

    while(($ligne=fgets($handle))!==false){

        if(preg_match($regex,trim($ligne),$m)){

            $logs[]=[
                "date"=>$m["date"],
                "level"=>"info",
                "service"=>$m["service"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    fclose($handle);

    return $logs;
}
