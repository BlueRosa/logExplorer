<?php
include("includes/const.php");
include("includes/logFileReader/logFileReader.php");
/**
 *  Classe à tout faire des fichiers locaux. Elle gère les traitements CR des fichiers de log sur le serveur.
 */
class FileToolbox {
    public string $chemin;
    public array $fichiers;
    function __construct(string $chemin = LOG_FILE_LOCATION) {
        $this->chemin = $chemin;
        $this->fichiers = $this->listerFichiers($chemin);
    }

    /**
     * Fonction de lecture des fichiers présents dans le dossier des logs conservés.
     * @param string $chemin chemin du dossier des logs conservés
     * @return array liste des fichiers présents dans le dossier des logs conservés
     */
    function listerFichiers(string $chemin = LOG_FILE_LOCATION): array
    {
        $fichiers = [];
        if (!is_dir($chemin)) {
            return $fichiers;
        }
        foreach (scandir($chemin) as $fichier) {
            if ($fichier === "." || $fichier === "..") {
                continue;
            }
            // Ignore les fichiers .info
            if (str_ends_with($fichier, ".info")) {
                continue;
            }
            $cheminFichier = $chemin . "/" . $fichier;
            if (!is_file($cheminFichier)) {
                continue;
            }
            $nomOriginal = null;
            $type = null;
            $dateImport = null;        $cheminInfo = $cheminFichier . ".info";
            if (is_file($cheminInfo)) {
                $info = json_decode(
                    file_get_contents($cheminInfo),
                    true
                );
                if (is_array($info)) {
                    $nomOriginal = $info["nomOriginal"] ?? null;
                    $type = $info["type"] ?? null;
                    $dateImport = $info["dateImport"] ?? null;
                }
            }
            $fichiers[] = [
                "nomActuel" => $fichier,
                "nomOriginal" => $nomOriginal,
                "type" => $type,
                "dateImport" => $dateImport
            ];
        }
        return $fichiers;
    }

    /**
     * @param array $file fichier à enregistrer
     * @param string $separateur séparateur des valeurs du fichier csv
     * @param string $chemin chemin du dossier des logs eregistrés localement
     * @return string nom du fichier de log stocké sur le serveur
     */
    function importCSV(array $file, string $separateur, string $chemin = LOG_FILE_LOCATION): string
    {
        if ($file['error'] === UPLOAD_ERR_OK) {

            if (!is_dir($chemin)) {
                mkdir($chemin, 0777, true);
            }

            $nomStockage = uniqid("log_", true) . ".log";

            $destination = $chemin ."/". $nomStockage;

            if (!move_uploaded_file(
                $file['tmp_name'],
                $destination
            )) {
                die("Impossible de déplacer le fichier");
            }
            $info = file_put_contents(
                $destination . ".info",
                json_encode([
                    "nomOriginal" => basename($file['name']),
                    "type" => "CSV",
                    "dateImport" => date("Y-m-d H:i:s"),
                    "separateur" => $separateur
                ], JSON_PRETTY_PRINT)
            );
            if ($info === false) {
                die("Impossible de créer le fichier info");
            }
            return $nomStockage;
        }
        return "";
    }

    /**
     * @param array $file fichier à enregistrer
     * @param string $type type de fichier à enregistrer
     * @param string $chemin chemin du dossier des logs eregistrés localement
     * @return string nom de stockage du fichier récemment importé
     */
    function import(array $file, string $type, string $chemin = LOG_FILE_LOCATION): string
    {
        if ($file['error'] === UPLOAD_ERR_OK) {

            $nomStockage = uniqid("log_", true) . ".log";

            $destination = $chemin ."/". $nomStockage;

            if (!move_uploaded_file(
                $file['tmp_name'],
                $destination
            )) {
                die("Impossible de déplacer le fichier");
            }
            $info = file_put_contents(
                $destination . ".info",
                json_encode([
                    "nomOriginal" => basename($file['name']),
                    "type" => $type,
                    "dateImport" => date("Y-m-d H:i:s")
                ], JSON_PRETTY_PRINT)
            );
            if ($info === false) {
                die("Impossible de créer le fichier info");
            }
            $this->fichiers = $this->listerFichiers($chemin);
            return $nomStockage;
        }
        return "";
    }

    /**
     * fonction de récupération de fichier à partir de son nom
     * @param String $nom nom actuel du fichier souhaité
     * @return array fichier qui porte le nom donné en paramètre
     */
    function getFichier(String $nom) : array{
        foreach ($this->fichiers as $fichier) {
            if ($fichier["nomActuel"] === $nom) {
                return $fichier;
            }
        }
        return [];
    }

    /**
     * fonction appelant la fonction d'extraction des données
     * @param array $file fichier dont il faut extraire les données
     * @param string $chemin chemin du dossier où sont les fichiers de log enregistrés localement
     * @return array données contenues dans le fichier
     */
    function extractDataFromFile(array $file, string $chemin = LOG_FILE_LOCATION) : array{
        return analyserFichierLog($chemin."/".$file["nomActuel"]);
    }
}