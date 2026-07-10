<?php

// lecteur pour système de mailing

//linux mail

function lirePostfix(string $fichier): array
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

function lireDovecot(string $fichier): array
{
    return lireLinuxSyslog($fichier);
}

function lireExim(string $fichier): array
{
    $logs=[];


    foreach(file($fichier) as $ligne){

        $ligne=trim($ligne);


        if(preg_match(
            '/^(?<date>\d{4}-\d{2}-\d{2} \d\d:\d\d:\d\d) (?<id>\S+) (?<action><=|=>|\*\*|==) (?<data>.*)$/',
            $ligne,
            $m
        )){


            $logs[]=[
                "date"=>$m["date"],
                "id"=>$m["id"],
                "action"=>$m["action"],
                "message"=>$m["data"],
                "raw"=>$ligne
            ];
        }
    }

    return $logs;
}

function lireSendmail(string $fichier): array
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

// securite mail

function lireAmavis(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $ligne=trim($ligne);

        $level="info";

        if(str_contains(strtoupper($ligne),"INFECTED")){
            $level="danger";
        }

        $logs[]=[
            "level"=>$level,
            "message"=>$ligne,
            "raw"=>$ligne
        ];
    }

    return $logs;
}

function lireSpamAssassin(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $ligne=trim($ligne);

        $logs[]=[
            "message"=>$ligne,
            "raw"=>$ligne
        ];
    }

    return $logs;
}

function lireClamAV(string $fichier): array
{
    $logs=[];


    foreach(file($fichier) as $ligne){

        $ligne=trim($ligne);

        $status="clean";

        if(str_contains($ligne,"FOUND")){
            $status="infected";
        }

        $logs[]=[
            "status"=>$status,
            "message"=>$ligne,
            "raw"=>$ligne
        ];
    }

    return $logs;
}

// Zimbra

function lireZimbra(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        $ligne=trim($ligne);

        $logs[]=[
            "message"=>$ligne,
            "raw"=>$ligne
        ];
    }

    return $logs;
}

function lireZimbraAuth(string $fichier): array
{
    $logs=[];

    foreach(file($fichier) as $ligne){

        if(
            stripos($ligne,"authentication") !== false
        ){

            $logs[]=[
                "message"=>trim($ligne),
                "raw"=>trim($ligne)
            ];
        }
    }

    return $logs;
}

// Microsoft

function lireExchangeTransport(string $fichier): array
{
    $logs=[];

    if (($handle=fopen($fichier,"r"))!==false){

        $header=fgetcsv($handle);

        while(($ligne=fgetcsv($handle))!==false){

            $logs[]=[
                "date"=>$ligne[0] ?? null,
                "connector"=>$ligne[1] ?? null,
                "event"=>$ligne[2] ?? null,
                "sender"=>$ligne[3] ?? null,
                "recipient"=>$ligne[4] ?? null,
                "raw"=>$ligne
            ];
        }

        fclose($handle);
    }

    return $logs;
}

function lireExchangeSMTP(string $fichier): array
{
    $logs=[];

    if(($handle=fopen($fichier,"r"))!==false){

        $headers=fgetcsv($handle);

        while(($ligne=fgetcsv($handle))!==false){

            $data=array_combine($headers,$ligne);

            $logs[]=[
                "data"=>$data,
                "raw"=>$ligne
            ];
        }

        fclose($handle);
    }

    return $logs;
}

function lireM365MessageTrace(string $fichier): array
{
    $logs=[];

    if(($handle=fopen($fichier,"r"))!==false){

        $headers=fgetcsv($handle);

        while(($ligne=fgetcsv($handle))!==false){

            $data=array_combine($headers,$ligne);

            $logs[]=[
                "sender"=>$data["SenderAddress"] ?? null,
                "recipient"=>$data["RecipientAddress"] ?? null,
                "status"=>$data["Status"] ?? null,
                "data"=>$data,
                "raw"=>$ligne
            ];
        }

        fclose($handle);
    }

    return $logs;
}

function lireM365Audit(string $fichier): array
{
    $logs=[];

    $contenu=file_get_contents($fichier);

    $json=json_decode($contenu,true);

    if(!$json){
        return $logs;
    }

    foreach($json as $event){

        $logs[]=[
            "user"=>$event["UserId"] ?? null,
            "operation"=>$event["Operation"] ?? null,
            "ip"=>$event["ClientIP"] ?? null,
            "data"=>$event,
            "raw"=>$event
        ];
    }

    return $logs;
}

function lireM365Defender(string $fichier): array
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

