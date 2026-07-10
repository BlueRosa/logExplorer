<?php

// lecteurs pour matériel réseau constructeur

function lireCiscoIOS(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match('/%(?<facility>\w+)-(?<severity>\d+)-(?<code>\w+): (?<message>.*)/',
            trim($ligne),
            $m
        )){

            $logs[]=[
                "facility"=>$m["facility"],
                "severity"=>(int)$m["severity"],
                "code"=>$m["code"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireJuniper(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match('/(?<service>\w+)\[(?<pid>\d+)]: (?<message>.*)/',
            trim($ligne),
            $m)){

            $logs[]=[
                "service"=>$m["service"],
                "pid"=>$m["pid"],
                "message"=>$m["message"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}