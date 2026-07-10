<?php

function demanderIaFiltres(string $message, array $log): array
{
    $exempleLog = json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $url = "http://localhost:11434/api/generate";

    $prompt = '
Tu es un traducteur de demandes utilisateur vers des filtres PHP.

Ta réponse doit être UNIQUEMENT un objet JSON valide.
N écris aucun texte avant ou après.
N utilise pas de bloc Markdown.

Le JSON doit contenir exactement ces 4 clés :

{
  "regex": "",
  "recherche": "",
  "dateDebut": "",
  "dateFin": ""
}

Signification des champs :

- regex :
    Expression régulière compatible avec preg_match().
    Elle est testée sur CHAQUE champ de la ligne.
    Si au moins un champ correspond, le critère est validé.
    Si le filtre n est pas nécessaire, mettre "".

- recherche :
    Chaîne de caractères recherchée avec stripos().
Si au moins un champ contient cette chaîne, le critère est validé.
Si inutile, mettre "".

- dateDebut :
    Date minimale au format YYYY-MM-DD.
Si inutile, mettre "".

- dateFin :
    Date maximale au format YYYY-MM-DD.
Si inutile, mettre "".

Règles :

- Utiliser le moins de critères possible.
- Ne jamais inventer des dates.
- Si l utilisateur ne demande pas de date, laisser les dates vides.
- Si une simple recherche suffit, ne pas utiliser de regex.
- Utiliser une regex uniquement lorsqu elle apporte réellement quelque chose.
- Les regex doivent être directement utilisables avec preg_match(), donc entourées de délimiteurs (/.../).
- Les points doivent être échappés (exemple : index\.php).

Voici un exemple de ligne de log :

'.json_encode($exempleLog).'

Exemples :

Utilisateur :
les requêtes POST

Réponse :
{
    "regex": "",
  "recherche": "POST",
  "dateDebut": "",
  "dateFin": ""
}

Utilisateur :
les accès à index.php

Réponse :
{
    "regex": "/index\\.php/",
  "recherche": "",
  "dateDebut": "",
  "dateFin": ""
}

Utilisateur :
les erreurs du 8 juillet 2026

Réponse :
{
    "regex": "",
  "recherche": "ERROR",
  "dateDebut": "2026-07-08",
  "dateFin": "2026-07-08"
}

Demande utilisateur :

'.$message;


    $data = [
        "model" => "qwen2.5:7b",
        "prompt" => $prompt,
        "stream" => false,
        "format" => "json"
    ];

    $options = [
        "http" => [
            "header" => "Content-Type: application/json",
            "method" => "POST",
            "timeout" => 60,
            "content" => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);

    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        return [];
    }

    $ollamaResponse = json_decode($response, true);

    if (!isset($ollamaResponse["response"])) {
        return [];
    }

    return json_decode($ollamaResponse["response"], true) ?? [];
}