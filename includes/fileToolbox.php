<?php
include("includes/const.php");
include("includes/logFileReader/logFileReader.php");
/**
 *  Classe à tout faire des fichiers locaux. Elle gère les traitements CR des fichiers de log sur le serveur.
 */
class FileToolbox
{
    public string $chemin;
    public array $fichiers;
    public string $fichier;

    function __construct(string $chemin = LOG_FILE_LOCATION)
    {
        $this->chemin = $chemin;
        $this->fichiers = $this->listerFichiers($chemin);
        $this->fichier = "";
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
            $dateImport = null;
            $cheminInfo = $cheminFichier . ".info";
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

            $destination = $chemin . "/" . $nomStockage;

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
                    "type" => "csv",
                    "dateImport" => date("Y-m-d H:i:s"),
                    "separateur" => $separateur
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
     * @param array $file fichier à enregistrer
     * @param string $type type de fichier à enregistrer
     * @param string $chemin chemin du dossier des logs eregistrés localement
     * @return string nom de stockage du fichier récemment importé
     */
    function import(array $file, string $type, string $chemin = LOG_FILE_LOCATION): string
    {
        if ($file['error'] === UPLOAD_ERR_OK) {

            $nomStockage = uniqid("log_", true) . ".log";

            $destination = $chemin . "/" . $nomStockage;

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
    function getFichier(string $nom): array
    {
        foreach ($this->fichiers as $fichier) {
            if ($fichier["nomActuel"] === $nom) {
                $this->fichier = $fichier;
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
    function extractDataFromFile(array $file, string $chemin = LOG_FILE_LOCATION): array
    {
        return analyserFichierLog($chemin . "/" . $file["nomActuel"]);
    }

    function sauvegarder(array $data, string $nomfichier = "sauvegardeDefault.csv", string $chemin = LOG_FILE_LOCATION): int
    {
        if (empty($data)) {
            return 404;
        }
        $cheminFichier = $chemin . "/" . $nomfichier . ".csv";
        if (!is_dir($chemin)) {
            die("chemin du dossier de sauvegarde érroné");
        }
        $handle = fopen($cheminFichier, "w");
        if ($handle === false) {
            die("Impossible d'ouvrir le fichier de sauvegarde");
        }
        // Écriture des en-têtes
        fputcsv($handle, array_keys($data[0]));
        // Écriture des données
        foreach ($data as $ligne) {
            fputcsv($handle, $ligne);
        }
        fclose($handle);
        return 0;
    }

    public function enregistrerFiltre(string $type, string $nom, array $filtres, ?string $idFichier = null): string
    {
        $dossierBase = FILTER_LOCATION;
        // Détermination du dossier de sauvegarde
        if ($type === "csv") {
            if ($idFichier === null) {
                die ("l'id du fichier est obligatoire");
            }
            $dossier = $dossierBase . "csv/" . $idFichier . "/";
        } else {
            $dossier = $dossierBase ."/". $type . "/";
        }

        // Création du dossier si nécessaire
        if (!is_dir($dossier)) {
            mkdir($dossier, 0777, true);
        }

        // Génération de l'identifiant
        $id = "filtre_" . date("Ymd_His") . "_" . uniqid();

        // Conversion des filtres en tableau JSON
        $listeFiltres = [];

        foreach ($filtres as $filtre) {
            $listeFiltres[] = [
                "colonne" => $filtre->colonne,
                "condition" => $filtre->condition,
                "valeur" => $filtre->valeur
            ];
        }
        $data = [
            "id" => $id,
            "nom" => $nom,
            "type" => $type,
            "creation" => date("Y-m-d H:i:s"),
            "filtres" => $listeFiltres
        ];

        file_put_contents(
            $dossier . $id . ".json",
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
         if ($type === "csv") {
             $this->ajouterFiltreInfoCsv(LOG_FILE_LOCATION."/".$idFichier.".info", $dossier . $id . ".json");
         }
        return $id;
    }

    public function chargerFiltre(string $chemin): array
    {
        if (!file_exists($chemin)) {
            return [];
        }
        $contenu = json_decode(
            file_get_contents($chemin),
            true
        );
        $filtres = [];
        foreach ($contenu["filtres"] ?? [] as $filtre) {
            $filtres[] = new Filtre(
                $filtre["colonne"],
                $filtre["condition"],
                $filtre["valeur"]
            );
        }
        return $filtres;
    }

    public function ajouterFiltreInfoCsv(string $fichierInfo, string $idFiltre): bool {
        if (!file_exists($fichierInfo)) {
            return false;
        }
        $info = json_decode(file_get_contents($fichierInfo), true);
        if ($info === null) {
            return false;
        }
        // Création du tableau si absent
        if (!isset($info["filtres"])) {
            $info["filtres"] = [];
        }
        // Évite les doublons
        if (!in_array($idFiltre, $info["filtres"])) {
            $info["filtres"][] = $idFiltre;
        }
        return file_put_contents($fichierInfo, json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            !== false;
    }
}