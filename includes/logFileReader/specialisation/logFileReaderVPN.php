<?php

// lecteurs pour VPN

function lireOpenVPN(string $fichier): array
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

function lireWireguard(string $fichier): array
{
    $logs = [];

    foreach (file($fichier) as $ligne) {

        $logs[] = [
            "message" => trim($ligne),
            "raw" => trim($ligne)
        ];
    }

    return $logs;
}