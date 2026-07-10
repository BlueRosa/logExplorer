<?php

// lecteurs pour système de conteneurs / de cloud

function lireKubernetesLog(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $parts = explode(" ", trim($ligne), 4);

        $logs[]=[
                "date"=>$parts[0] ?? null,
                "stream"=>$parts[1] ?? null,
                "message"=>$parts[3] ?? "",
                "raw"=>trim($ligne)
        ];
    }

    return $logs;
}

function lireDockerLog(string $fichier): array
{
    $logs = [];

    $handle = fopen($fichier, "r");

    if (!$handle) return $logs;


    $regex = '/^(?<date>\S+) (?<level>\w+) (?<message>.*)$/';


    while (($ligne = fgets($handle)) !== false) {

        if (preg_match($regex, trim($ligne), $m)) {

            $logs[] = [
                    "date" => $m["date"],
                    "level" => $m["level"],
                    "message" => $m["message"],
                    "raw" => trim($ligne)
            ];
        }
    }

    fclose($handle);

    return $logs;
}