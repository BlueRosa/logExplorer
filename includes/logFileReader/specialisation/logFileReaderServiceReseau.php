<?php

// lecteurs pour les services réseau

function lireDHCP(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match(
            '/DHCP(?<event>\w+) for (?<ip>\S+) from (?<mac>\S+)/',
            trim($ligne),
            $m
        )){

            $logs[]=[
                "event"=>$m["event"],
                "ip"=>$m["ip"],
                "mac"=>$m["mac"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireDNSBind(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match(
            '/client (?<ip>[\d.]+)#\d+: query: (?<domain>\S+) IN (?<type>\S+)/',
            trim($ligne),
            $m
        )){

            $logs[]=[
                "client"=>$m["ip"],
                "domain"=>$m["domain"],
                "record"=>$m["type"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}