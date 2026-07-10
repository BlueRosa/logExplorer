<?php

// lecteurs pour cache / services

function lireRedis(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $logs[]=[
            "type"=>"redis",
            "message"=>trim($ligne),
            "raw"=>trim($ligne)
        ];
    }

    return $logs;
}

function lireVarnish(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match(
            '/^(?<ip>\S+) .* \[(?<date>[^]]+)] "(?<method>\S+) (?<url>\S+).*" (?<status>\d+) (?<cache>\S+)/',
            trim($ligne),
            $m
        )){
            $logs[]=[
                "type"=>"varnish",
                "ip"=>$m["ip"],
                "date"=>$m["date"],
                "method"=>$m["method"],
                "url"=>$m["url"],
                "status"=>$m["status"],
                "cache"=>$m["cache"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireSquid(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(preg_match(
            '/^(?<timestamp>\S+) (?<duration>\S+) (?<client>\S+) (?<status>\S+) (?<method>\S+) (?<url>\S+)/',
            trim($ligne),
            $m
        )){

            $logs[]=[
                "timestamp"=>$m["timestamp"],
                "client"=>$m["client"],
                "status"=>$m["status"],
                "method"=>$m["method"],
                "url"=>$m["url"],
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

function lireMemcached(string $fichier): array
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

function lireUnbound(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne) {

        $ligne = trim($ligne);

        $cache = "unknown";

        if (str_contains($ligne, "cache hit")) {
            $cache = "hit";
        }

        $logs[] = [
            "cache" => $cache,
            "message" => $ligne,
            "raw" => $ligne
        ];
    }

    return $logs;
}

function lirePowerDNS(string $fichier): array
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