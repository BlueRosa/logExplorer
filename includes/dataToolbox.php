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
     * @var array filtres actifs sur les données
     */
    public array $filtreActif;

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
        $this->filtreActif = [];
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

    function filtrer(array $filtres): array
    {
        if (empty($this->filteredData)) {
            return [];
        }
        $resultat = $this->filteredData;
        foreach ($filtres as $filtre) {
            $resultat = array_filter($resultat, function ($ligne) use ($filtre) {
                // La colonne n'existe pas
                if (!isset($ligne[$filtre->colonne])) {
                    return false;
                }
                $valeurColonne = (string)$ligne[$filtre->colonne];
                $valeurFiltre = (string)$filtre->valeur;
                switch ($filtre->condition) {
                    case "contient":
                        return stripos($valeurColonne, $valeurFiltre) !== false;
                    case "=":
                        return $valeurColonne === $valeurFiltre;
                    case "regex":
                        return preg_match($valeurFiltre, $valeurColonne) === 1;
                    case ">":
                        return $valeurColonne > $valeurFiltre;
                    case "<":
                        return $valeurColonne < $valeurFiltre;
                    default:
                        return false;
                }
            });
            // Réindexation après chaque filtre
            $resultat = array_values($resultat);
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

    public function ajouterFiltre(string $colonne, string $condition, string $valeur) : void
    {
        $this->filtreActif[] = new Filtre($colonne, $condition, $valeur);
    }
}

class Filtre {
    public string $colonne;
    public string $condition;
    public string $valeur;
    function __construct(string $colonne, string $condition, string $valeur) {
        $this->colonne = $colonne;
        $this->condition = $condition;
        $this->valeur = $valeur;
    }
}