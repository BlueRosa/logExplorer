<?php

// lecteurs pour système de téléphonie

function lireSIP(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match('/^(INVITE|REGISTER|BYE|ACK|OPTIONS) (.*)/',
            trim($ligne),
            $m)){

            $logs[]=[
                "method"=>$m[1],
                "target"=>$m[2],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireCDR(string $fichier): array
{
    $logs=[];

    $handle=fopen($fichier,"r");

    while(($ligne=fgetcsv($handle))!==false){

        $logs[]=[
            "date"=>$ligne[0] ?? null,
            "caller"=>$ligne[1] ?? null,
            "callee"=>$ligne[2] ?? null,
            "duration"=>$ligne[3] ?? null
        ];
    }

    fclose($handle);

    return $logs;
}