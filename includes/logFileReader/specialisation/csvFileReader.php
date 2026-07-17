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
    foreach ($entetes as &$entete) {
        // Supprime le BOM UTF-8
        $entete = preg_replace('/^\xEF\xBB\xBF/', '', $entete);
        // Supprime les guillemets éventuels
        $entete = trim($entete, '"');
        // Supprime les espaces inutiles
        $entete = trim($entete);
    }
    unset($entete);
    while (($ligne = fgetcsv($handle, 0, $separateur)) !== false) {
        // Sécurité si une ligne a un nombre de colonnes différent
        if (count($entetes) !== count($ligne)) {
            continue;
        }
        $logs[] = array_combine($entetes, $ligne);
    }
    fclose($handle);
    return $logs;
}