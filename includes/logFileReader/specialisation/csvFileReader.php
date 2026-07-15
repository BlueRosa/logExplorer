<?php

function lireCSV(string $fichier, string $separateur = ","): array
{
    $logs = [];
    if (!file_exists($fichier)) {
        return $logs;
    }
    $handle = fopen($fichier, "r");
    if ($handle === false) {
        return $logs;
    }
    // Lecture de l'en-tête
    $entetes = fgetcsv($handle, 0, $separateur);
    while (($ligne = fgetcsv($handle, 0, $separateur)) !== false) {
        $logs[] = array_combine($entetes, $ligne);
    }
    fclose($handle);
    return $logs;
}