<?php

// lecteurs pour les pare-feu

function lireFortigate(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        preg_match_all('/(\w+)=("[^"]*"|\S+)/', trim($ligne), $m);

        $data=[];

        foreach($m[1] as $i=>$key){

            $data[$key]=trim($m[2][$i],'"');
        }

        $logs[]=[
            "data"=>$data,
            "raw"=>trim($ligne)
        ];
    }

    return $logs;
}

function lirePaloAlto(string $fichier): array
{
    return lireFortigate($fichier);
}