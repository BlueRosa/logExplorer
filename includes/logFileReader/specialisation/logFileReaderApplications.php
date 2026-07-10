<?php

// lecteurs pour log d'applications

function lireLaravel(string $fichier): array
{
    $logs=[];

    $regex='/^\[(?<date>[^\]]+)\] (?<env>\S+)\.(?<level>\w+): (?<message>.*)$/';


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

function lirePM2(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $logs[]=[
            "message"=>trim($ligne),
            "raw"=>trim($ligne)
        ];
    }

    return $logs;
}

function lireCI(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $logs[]=[
            "message"=>trim($ligne),
            "raw"=>trim($ligne)
        ];
    }

    return $logs;
}