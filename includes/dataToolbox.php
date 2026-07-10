<?php

/**
 *  Classe à tout faire des données de log
 */
class DataToolbox {
    /**
     * @var array données de log avant filtrage et tri
     */
    public array $data;
    /**
     * @var array données de log après filtrage et tri
     */
    public array $filteredData;

    /**
     * @var Filtre filtre actif sur les données
     */
    public Filtre $filtreActif;

    /**
     * @var string colonne sur laquelle est basée le tri actif
     */
    public string $triColonne;

    /**
     * @var string sens dans lequel est le tri actif (ASC ou DESC)
     */
    public string $sensTri;

    /**
     * @param array $data données de log à traiter
     */
    function __construct(array $data = []) {
        $this->data = $data;
        $this->filteredData = $data;
        $this->filtreActif = new Filtre("","","","");
    }

    /**
     * Fonction de changement de données de log à traiter
     * @param array $data données de log à traiter
     * @return void
     */
    function importData(array $data): void {
        $this->data = $data;
        $this->filteredData = $data;
    }


    /**
     * Fonction d'application d'un filtre sur les données de la classe.
     * @param string $regex expression régulière qu'un des champs doit valider pour être affichée
     * @param string $recherche valeur qu'un champ doit contenir pour être affichée
     * @param string $dateDebut date de début de la sélection
     * @param string $dateFin date de fin de la sélection
     * @return array données filtrées
     */
    function filtrer(string $regex, string $recherche, string $dateDebut, string $dateFin) : array
    {
        $this->filtreActif->regex = $regex;
        $this->filtreActif->recherche = $recherche;
        $this->filtreActif->dateDebut = $dateDebut;
        $this->filtreActif->dateFin = $dateFin;

        if (empty($this->data)) {
            return [];
        }

        $resultat = [];

        // Trouver la colonne date
        $colonneDate = null;
        if (!empty($data)) {
            foreach (array_keys($this->data[0]) as $colonne) {
                if (stripos($colonne, "date") !== false) {
                    $colonneDate = $colonne;
                    break;
                }
            }
        }

        // Parcours des données (hors première ligne)
        foreach (array_slice($this->data, 1) as $ligne) {

            $valideRegex = ($regex === "");
            $valideRecherche = ($recherche === "");

            foreach ($ligne as $champ) {

                $champ = (string)$champ;

                // Au moins un champ respecte le regex
                if ($regex !== "" && preg_match($regex, $champ)) {
                    $valideRegex = true;
                }

                // Au moins un champ contient la recherche
                if ($recherche !== "" && stripos($champ, $recherche) !== false) {
                    $valideRecherche = true;
                }
            }
            $valide = $valideRegex && $valideRecherche;
            if ($valide && $colonneDate !== null) {
                $date = strtotime($ligne[$colonneDate]);
                if ($dateDebut !== null && $dateDebut !== "" && $date < strtotime($dateDebut)) {
                    $valide = false;
                }
                if ($dateFin !== null && $dateFin !== "" && $date > strtotime($dateFin . " 23:59:59")) {
                    $valide = false;
                }
            }
            if ($valide) {
                $resultat[] = $ligne;
            }
        }
        $this->filteredData = $resultat;
        return $resultat;
    }

    /**
     * fonction d'application d'un tri sur les données
     * @param string $colonne champ sur lequel se baser pour le tri
     * @param string $ordre ASC : ascending, DESC : descending
     * @return array données triées
     */
    function trier(string $colonne, string $ordre = "ASC") : array
    {
        $this -> triColonne = $colonne;
        $this -> sensTri = $ordre;
        usort($this->filteredData, function ($a, $b) use ($colonne, $ordre) {
            $valA = $a[$colonne] ?? "";
            $valB = $b[$colonne] ?? "";
            // Dates
            if (strtotime($valA) !== false && strtotime($valB) !== false) {
                $valA = strtotime($valA);
                $valB = strtotime($valB);
            }
            $resultat = $valA <=> $valB;
            return $ordre === "DESC" ? -$resultat : $resultat;
        });
        return $this->filteredData;
    }
}

class Filtre {
    public string $regex;
    public string $recherche;
    public string $dateDebut;
    public string $dateFin;
    function __construct($regex, string $recherche, string $dateDebut, string $dateFin) {
        $this->regex = $regex;
        $this->recherche = $recherche;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
    }
}