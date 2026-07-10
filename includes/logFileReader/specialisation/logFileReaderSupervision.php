<?php

// lecteurs pour système de supervision

function lireSNMPTrap(string $fichier): array
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

function lireSupervision(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match(
            '/^(?<type>\w+ ALERT): (?<host>[^;]+);(?<state>[^;]+);(?<message>.*)/',
            trim($ligne),
            $m
        )){

            $logs[]=[
                "alert"=>$m["type"],
                "host"=>$m["host"],
                "state"=>$m["state"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}