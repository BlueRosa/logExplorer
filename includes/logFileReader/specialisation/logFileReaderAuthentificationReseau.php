<?php

// lecteurs pour les services d'auhentification réseau

function lireRadius(string $fichier): array
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