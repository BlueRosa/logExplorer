<?php
include("includes/const.php");
include("includes/logFileReader/logFileReader.php");
/**
 *  Classe à tout faire des fichiers locaux. Elle gère les traitements CR des fichiers de log sur le serveur.
 */
class FileToolbox
{
    /**
     * @var string chemin vers le fichier des logs
     */
    public string $chemin;
    /**
     * @var array liste des fichiers de logs présents sur le serveur
     */
    public array $fichiers;
    function __construct(string $chemin = LOG_FILE_LOCATION)
    {
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
     * Fonction d'import d'un fichier CSV du navigateur vers le serveur
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
     * Fonction d'import d'un fichier depuis le navigateur vers le serveur
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
     * Fonction de récupération de fichier à partir de son nom
     * @param String $nom nom actuel du fichier souhaité
     * @return array fichier qui porte le nom donné en paramètre
     */
    function getFichier(string $nom): array
    {
        foreach ($this->fichiers as $fichier) {
            if ($fichier["nomActuel"] === $nom) {
                return $fichier;
            }
        }
        return [];
    }

    /**
     * Fonction appelant la fonction d'extraction des données
     * @param array $file fichier dont il faut extraire les données
     * @param string $chemin chemin du dossier où sont les fichiers de log enregistrés localement
     * @return array données contenues dans le fichier
     */
    function extractDataFromFile(array $file, string $chemin = LOG_FILE_LOCATION): array
    {
        return analyserFichierLog($chemin . "/" . $file["nomActuel"]);
    }

    /**
     * Fonction de sauvegarde d'un filtre
     * @param string $type type de données pour lesquelles le filtre est fait
     * @param string $nom nom du filtre au choix
     * @param array $filtres liste de Filtre composant ce filtre
     * @param string|null $idFichier nom du fichier actuel ( utile que pour les csv )
     * @return string nom du filtre fraichement enregistré
     */
    public function enregistrerFiltre(string $type, string $nom, array $filtres, ?string $idFichier = null): string
    {
        $dossierBase = FILTER_LOCATION;
        // Détermination du dossier de sauvegarde

        if ($type === "csv") {
            if ($idFichier === null) {
                die("l'id du fichier est obligatoire");
            }
            $dossier = $dossierBase . "/csv/" . $idFichier . "/";
        } else {
            $dossier = $dossierBase . "/" . $type . "/";
        }

        // Création du dossier si nécessaire
        if (!is_dir($dossier)) {
            if (!mkdir($dossier, 0777, true)) {
                die("Impossible de créer le dossier : ".$dossier);
            }
        }
        if (!is_writable($dossier)) {
            die("Dossier non accessible en écriture : ".$dossier);
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
            "data" => $listeFiltres
        ];

        $fichier = $dossier . $id . ".json";

        if (file_put_contents($fichier, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false)
        {
            die("Impossible de sauvegarder le filtre : ".$fichier);
        }

        if ($type === "csv") {
             $this->ajouterFiltreInfoCsv(LOG_FILE_LOCATION."/".$idFichier.".info", $dossier . $id . ".json");
        }
        return $id;
    }

    /**
     * Fonction de lecture d'un fichier de filtre avec son chemin
     * @param string $chemin chemin du fichier filtre à charger
     * @return array liste des données tirées du filtre
     */
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
        if (!is_array($contenu)) {
            return [];
        }
        foreach ($contenu["data"] ?? [] as $filtre) {
        $filtres[] = new Filtre(
            $filtre["colonne"],
            $filtre["condition"],
            $filtre["valeur"]
        );
    }
        return $filtres;
    }

    /**
     * Fonction d'ajout du nom d'un fichier dans le fichier info d'un csv.
     * @param string $fichierInfo lien du fichier d'informations (.info) du fichier ouvert
     * @param string $idFiltre nom du filtre concerné par cet ajout
     * @return bool si l'ajout du filtre dans le fichier info a réussi ou non
     */
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
            $info["filtres"][] = basename($idFiltre);
        }
        return file_put_contents($fichierInfo, json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            !== false;
    }

    /**
     * Fonction pour lire la liste des filtres disponibles pour un fichier
     * @param string $type type de fichier concerné
     * @param string|null $nomfichier nom du fichier concerné (utile que pour les csv)
     * @param string|null $cheminFichier chemin du fichier concerné (utile que pour les csv)
     * @param string $chemin chemin menant au dossier des filtres
     * @return array liste des filtres existants et compatible avec ce fichier
     */
    public function listerFiltres(string $type, ?string $nomfichier = null, ?string $cheminFichier = LOG_FILE_LOCATION , string $chemin = FILTER_LOCATION) : array
    {
        $filtres = [];

        if (!is_dir($chemin)) {
            return $filtres;
        }

        if ($type === "csv") {
            if ($nomfichier === null) {
                return [];
            }
            return $this->listerFiltresCsv($nomfichier, $cheminFichier);
        }

        $chemin .= "/" . $type;

        if (!is_dir($chemin)) {
            return [];
        }

        foreach (glob($chemin . "/*.json") as $fichier) {
            $contenu = json_decode(file_get_contents($fichier), true);
            $filtres[] = [
                "id" => $contenu["id"],
                "nom" => $contenu["nom"],
                "type" => $contenu["type"],
                "creation" => $contenu["creation"],
                "chemin" => $fichier,
                "data" => $contenu["data"]
            ];
        }
        return $filtres;
    }

    /**
     * Fonction qui trouve les filtres compatibles avec un fichier
     * @param string $nomFichier nom du fichier concerné
     * @param string $cheminFichier chemin vers le fichier des logs
     * @return array liste des filtres trouvés pour ce fichier
     */
    public function listerFiltresCsv(string $nomFichier, string $cheminFichier = LOG_FILE_LOCATION): array
    {
        $resultat = [];

        $infoPath = $cheminFichier . "/" . $nomFichier . ".info";

        if (!file_exists($infoPath)) {
            return [];
        }

        $info = json_decode(file_get_contents($infoPath), true);

        foreach ($info["filtres"] ?? [] as $fichierFiltre) {

            $chemin = FILTER_LOCATION."/csv/".$nomFichier."/".$fichierFiltre;

            if (!file_exists($chemin)) {
                continue;
            }

            $filtre = json_decode(file_get_contents($chemin), true);

            $resultat[] = [
                "id" => $filtre["id"],
                "nom" => $filtre["nom"],
                "chemin" => $chemin,
                "data" => $filtre["data"]
            ];
        }
        return $resultat;
    }

    /**
     * Fonction de mise à jour / de création du fichier téléchargeable des données traitées
     * @param array $data données traitées
     * @param string $nomFichier nom du fichier à télécharger
     * @param string $chemin chemin vers le fichier à télécharger
     * @return bool si la mise a jour/ la créaction s'est bien passée ou non
     */
    public function mettreAJourTempCSV(array $data, string $nomFichier = "SaveData" ,string $chemin = LOG_FILE_LOCATION): bool
    {
        if (empty($data)) {
            return false;
        }
        // Nettoyage des anciennes sessions
        $this->nettoyerTempCSV($chemin);
        $session = session_id();
        if (empty($session)) {
            return false;
        }
        $dossierTemp = $chemin . "/temp/" . $session;
        if (!is_dir($dossierTemp)) {
            mkdir($dossierTemp, 0777, true);
        }
        $fichier = $dossierTemp . "/".$nomFichier.".csv";
        $handle = fopen($fichier, "w");
        if ($handle === false) {
            return false;
        }
        fputcsv($handle, array_keys($data[0]));
        foreach ($data as $ligne) {
            fputcsv($handle, $ligne);
        }
        fclose($handle);
        touch($dossierTemp);
        return true;
    }

    /**
     * Fonction de suppression des fichiers temporaires expirés
     * @param string $chemin chemin vers les fichiers (sans /temp)
     * @return void
     */
    private function nettoyerTempCSV(string $chemin): void
    {
        $temp = $chemin . "/temp";
        if (!is_dir($temp)) {
            return;
        }
        foreach (glob($temp."/*") as $dossier) {
            if (!is_dir($dossier)) {
                continue;
            }
            // Suppression après 24h d'inactivité
            if (time() - filemtime($dossier) > 86400) {
                foreach (glob($dossier."/*") as $fichier) {
                    if (is_file($fichier)) {
                        unlink($fichier);
                    }
                }
                rmdir($dossier);
            }
        }
    }
}