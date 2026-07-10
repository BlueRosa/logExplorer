<?php

// lecteurs pour le web
function lireApacheFullAccess(string $fichier): array
{
    $logs = [];

    if (!file_exists($fichier)) {
        return $logs;
    }

    $handle = fopen($fichier, "r");

    if (!$handle) {
        return $logs;
    }

    /*
     * Format :
     * IP - USER [date] "METHOD URL HTTP" STATUS SIZE "REFERER" "USER_AGENT" reste...
     */
    $regex = '/^(?<ip>\S+) \S+ \S+ \[(?<date>[^\]]+)\] "(?<method>\S+) (?<url>\S+) (?<protocol>[^"]+)" (?<status>\d+) (?<size>\S+) "(?<referer>[^"]*)" "(?<user_agent>[^"]*)"(?: (?<extra>.*))?$/';

    while (($ligne = fgets($handle)) !== false) {

        $ligne = trim($ligne);

        if (preg_match($regex, $ligne, $match)) {

            $log = [
                "ip" => $match["ip"],

                "date" => $match["date"],

                "request" => [
                    "method" => $match["method"],
                    "url" => $match["url"],
                    "protocol" => $match["protocol"]
                ],

                "status" => (int)$match["status"],

                "size" => $match["size"] === "-"
                    ? null
                    : (int)$match["size"],

                "referer" => $match["referer"],

                "user_agent" => $match["user_agent"],

                "extra" => []
            ];


            // Champs supplémentaires après le User-Agent
            if (!empty($match["extra"])) {

                $extras = explode(" ", $match["extra"]);

                foreach ($extras as $extra) {
                    $log["extra"][] = $extra;
                }
            }


            $logs[] = $log;

        } else {

            // Ligne non reconnue
            $logs[] = [
                "type" => "unknown",
                "raw" => $ligne
            ];
        }
    }

    fclose($handle);

    return $logs;
}

function lireApacheError(string $fichier): array
{
    $logs = [];

    $handle = fopen($fichier, "r");

    if (!$handle) return $logs;

    $regex = '/^\[(?<date>[^\]]+)\] \[(?<module>[^:]+):(?<level>[^\]]+)\](?: \[pid (?<pid>\d+):tid (?<tid>\d+)\])?(?: \[client (?<ip>[^:\]]+)(?::(?<port>\d+))?\])? (?<message>.*)$/';

    while (($ligne=fgets($handle))!==false){

        if(preg_match($regex,trim($ligne),$m)){

            $logs[]=[
                "date"=>$m["date"],
                "level"=>$m["level"],
                "ip"=>$m["ip"] ?? null,
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    fclose($handle);

    return $logs;
}
function lireApacheAccess(string $fichier): array
{
    $logs = [];

    $handle = fopen($fichier, "r");

    if (!$handle) return $logs;

    $regex = '/^(?<ip>\S+) \S+ \S+ \[(?<date>[^\]]+)\] "(?<method>\S+) (?<url>\S+) (?<protocol>[^"]+)" (?<status>\d+) (?<size>\S+) "(?<referer>[^"]*)" "(?<agent>[^"]*)"(?: (?<extra>.*))?$/';

    while (($ligne = fgets($handle)) !== false) {

        if (preg_match($regex, trim($ligne), $m)) {

            $logs[] = [
                "date" => $m["date"],
                "level" => "info",
                "ip" => $m["ip"],
                "method" => $m["method"],
                "url" => $m["url"],
                "status" => (int)$m["status"],
                "size" => $m["size"],
                "agent" => $m["agent"],
                "message" => $m["method"]." ".$m["url"],
                "raw" => trim($ligne)
            ];
        }
    }

    fclose($handle);

    return $logs;
}

function lireNginxAccess(string $fichier): array
{
    return lireApacheAccess($fichier);
}

function lireNginxError(string $fichier): array
{
    $logs=[];

    $handle=fopen($fichier,"r");

    if(!$handle)return $logs;

    $regex='/^(?<date>\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) \[(?<level>\w+)\] (?<message>.*)$/';

    while(($ligne=fgets($handle))!==false){

        if(preg_match($regex,trim($ligne),$m)){

            $logs[]=[
                "date"=>$m["date"],
                "level"=>$m["level"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    fclose($handle);

    return $logs;
}

function lirePhpError(string $fichier): array
{
    $logs=[];

    $handle=fopen($fichier,"r");

    if(!$handle) return $logs;

    $regex='/^\[(?<date>[^\]]+)\] PHP (?<level>[^:]+): (?<message>.*)$/';

    while(($ligne=fgets($handle))!==false){

        if(preg_match($regex,trim($ligne),$m)){

            $logs[]=[
                "date"=>$m["date"],
                "level"=>$m["level"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    fclose($handle);

    return $logs;
}
