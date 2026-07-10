<?php

// lecteur pour système de base de donnée

function lireMysqlError(string $fichier): array
{
    $logs=[];

    $handle=fopen($fichier,"r");

    if(!$handle)return $logs;

    while(($ligne=fgets($handle))!==false){

        $logs[]=[
            "level"=>"error",
            "message"=>trim($ligne),
            "raw"=>trim($ligne)
        ];
    }

    fclose($handle);

    return $logs;
}

function lirePostgresql(string $fichier): array
{
    $logs=[];

    $regex='/^(?<date>\S+ \S+ \S+) \[(?<pid>\d+)\] (?<level>\w+): (?<message>.*)$/';


    foreach(file($fichier) as $ligne){

        if(preg_match($regex,trim($ligne),$m)){

            $logs[]=[
                "date"=>$m["date"],
                "level"=>$m["level"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireSqlServerError(string $fichier): array
{
    $logs = [];

    if (!file_exists($fichier)) {
        return $logs;
    }

    foreach (file($fichier) as $ligne) {

        $ligne = trim($ligne);

        /*
         * Format :
         * YYYY-MM-DD HH:mm:ss.mmm     Source      Message
         */
        $regex = '/^(?<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+)\s+(?<source>\S+)\s+(?<message>.*)$/';

        if (preg_match($regex, $ligne, $m)) {

            $level = "info";

            // Détection simple de gravité
            if (
                stripos($m["message"], "error") !== false ||
                stripos($m["message"], "failed") !== false
            ) {
                $level = "error";
            }

            if (
                stripos($m["message"], "warning") !== false
            ) {
                $level = "warning";
            }

            $logs[] = [
                "date" => $m["date"],
                "source" => $m["source"],
                "level" => $level,
                "message" => $m["message"],
                "raw" => $ligne
            ];

        } else {

            $logs[] = [
                "message" => $ligne,
                "raw" => $ligne
            ];
        }
    }

    return $logs;
}

function lireSqlServerAgent(string $fichier): array
{
    $logs = [];

    if (!file_exists($fichier)) {
        return $logs;
    }

    foreach (file($fichier) as $ligne) {

        $ligne = trim($ligne);

        if ($ligne === "") {
            continue;
        }

        $level = "info";

        if (
            stripos($ligne, "failed") !== false ||
            stripos($ligne, "error") !== false
        ) {
            $level = "error";
        }

        if (
            stripos($ligne, "warning") !== false
        ) {
            $level = "warning";
        }

        $logs[] = [
            "level" => $level,
            "message" => $ligne,
            "raw" => $ligne
        ];
    }

    return $logs;
}


