<?php

// lecteur pour système de sécurité

function lireModSecurity(string $fichier): array
{
    $logs=[];

    $handle=fopen($fichier,"r");

    if(!$handle)return $logs;

    while(($ligne=fgets($handle))!==false){

        if(str_contains($ligne,"ModSecurity")){

            $logs[]=[
                "level"=>"warning",
                "message"=>trim($ligne),
                "raw"=>trim($ligne)
            ];
        }
    }

    fclose($handle);

    return $logs;
}

function lireFail2ban(string $fichier): array
{
    $logs=[];

    $regex='/^(?<date>[^ ]+ [^,]+),\d+ (?<service>\S+) \[(?<pid>\d+)\]: (?<level>\w+) \[(?<jail>[^\]]+)\] (?<message>.*)$/';

    foreach(file($fichier) as $ligne){

        if(preg_match($regex,trim($ligne),$m)){

            $logs[]=[
                "date"=>$m["date"],
                "level"=>$m["level"],
                "service"=>$m["service"],
                "jail"=>$m["jail"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}