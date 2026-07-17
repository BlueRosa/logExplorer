<?php
include("includes/dataToolbox.php");
include("includes/fileToolbox.php");
include("includes/ollama.php");
session_start();
$fileToolbox = new FileToolbox();
$dataToolbox = new DataToolbox($_SESSION['data'] ?? []);
$filtresSauvegardes = [];

// --- traitement des POST et des FILES ---

if (isset($_POST['submit'])) {
    switch ($_POST['submit']) {
        case 'importer' : // import des données du navigateur vers le serveur
            if (isset($_FILES['file'])) {
                $nomOriginal = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);

                $_SESSION['fichierActuel'] = [
                        "nom" => $fileToolbox->import($_FILES['file'], $_POST['typeLog']),
                        "type" => $_POST['typeLog'],
                        "nomInitial" => $nomOriginal
                ];
                $_SESSION['data'] = $fileToolbox->extractDataFromFile($fileToolbox->getFichier($_SESSION['fichierActuel']['nom']));
                $dataToolbox->importData($_SESSION['data']);
            }
            break;
        case 'csv' : // import d'un fichier csv du navigateur vers le serveur
            if (isset($_FILES['file'])) {
                $nomOriginal = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
                $_SESSION['fichierActuel'] = [
                        "nom" => $fileToolbox->importCSV($_FILES['file'], $_POST['separateur']),
                        "type" => 'csv',
                        "nomInitial" => $nomOriginal
                ];

                $dataToolbox->importData($fileToolbox->extractDataFromFile(
                        $fileToolbox->getFichier($_SESSION['fichierActuel']['nom'])
                ));
            }
            break;
        case 'selectionner' : // choix d'un fichier déjà présent sur le serveur
            if (isset($_POST['fileChoose'])) {
                $file = json_decode($_POST['fileChoose'], true);
                $_SESSION['data'] = $fileToolbox->extractDataFromFile($fileToolbox->getFichier($file['nomActuel']));
                $_SESSION['fichierActuel'] = [
                        "nom" => $file['nomActuel'],
                        "type" => $file['type'],
                    "nomInitial" => $file['nomOriginal']
                ];
                $dataToolbox->importData($_SESSION['data']);
            }
            break;
        case 'Filtrer' : // filtrage du fichier ouvert
            $compteur = 0;
            while (isset($_POST['filtre'.$compteur."colonne"]) && isset($_POST['filtre'.$compteur."condition"]) &&
                    isset($_POST['filtre'.$compteur."valeur"])) {
                $dataToolbox->ajouterFiltre(
                        $_POST['filtre'.$compteur."colonne"],
                        $_POST['filtre'.$compteur."condition"],
                        $_POST['filtre'.$compteur."valeur"]
                );
                $compteur++;
            }
            $dataToolbox->filtrer($dataToolbox->filtreActif);
            break;
        case 'trier' : // tri du fichier ouvert par une colonne (et re-application des filtres s'il y en a)
            $colonneTri = $_POST['tri'] ?? "";
            $ancienTri = $_POST['colonneTri'] ?? "";
            $ordreTri = $_POST['ordreTri'] ?? "ASC";

            if ($colonneTri === $ancienTri) {
                $ordreTri = ($ordreTri === "ASC") ? "DESC" : "ASC";
            } else {
                $ordreTri = "ASC";
            }

            $compteur = 0;
            while (isset($_POST['filtre'.$compteur."colonne"]) && isset($_POST['filtre'.$compteur."condition"]) &&
                    isset($_POST['filtre'.$compteur."valeur"]) ) {
                $dataToolbox->ajouterFiltre($_POST['filtre'.$compteur."colonne"], $_POST['filtre'.$compteur."condition"],
                        $_POST['filtre'.$compteur."valeur"]);
                $compteur++;
            }
            $dataToolbox->filtrer($dataToolbox->filtreActif);
            $dataToolbox->filteredData = $dataToolbox->trier($colonneTri, $ordreTri);
            break;

        case 'ia' : // demande de génération de filtres à l'IA
            /* partie pas à jour
            $filtreIA = demanderIaFiltres($_POST['demandeIA'], $_SESSION['data'][0] ?? []);

            $compteur = 0;
            while (isset($_POST['filtre'.$compteur."colonne"]) && isset($_POST['filtre'.$compteur."condition"]) &&
                    isset($_POST['filtre'.$compteur."valeur"]) ) {
                $dataToolbox->ajouterFiltre($_POST['filtre'.$compteur."colonne"], $_POST['filtre'.$compteur."condition"],
                        $_POST['filtre'.$compteur."valeur"]);
                $compteur++;
            }
            */
            $dataToolbox->filtrer($dataToolbox->filtreActif);
            break;
        case 'saveFiltre': // sauvegarde du filtre actif
            $nomFiltre = $_POST['nomSauvegarde'] ?? '';

            if (empty($nomFiltre)) {
                die("Nom du filtre obligatoire");
            }

            $compteur = 0;
            while (isset($_POST['filtre'.$compteur."colonne"]) && isset($_POST['filtre'.$compteur."condition"]) &&
                    isset($_POST['filtre'.$compteur."valeur"])) {
                $dataToolbox->ajouterFiltre($_POST['filtre'.$compteur."colonne"], $_POST['filtre'.$compteur."condition"],
                        $_POST['filtre'.$compteur."valeur"]
                );
                $compteur++;
            }
            $fileToolbox->enregistrerFiltre($_SESSION['fichierActuel']['type'], $nomFiltre, $dataToolbox->filtreActif,
                    $_SESSION['fichierActuel']['nom']);
            $dataToolbox->filtrer($dataToolbox->filtreActif);
            break;
        case 'loadFiltre' : // chargement d'un filtre précédemment enregistré
            $dataToolbox->filtreActif = $fileToolbox->chargerFiltre($_POST['filtreCharge']);
            $dataToolbox->filtrer($dataToolbox->filtreActif);
            break;
        default :
            die ('post submit inconnu');


    }
}
$nomFichierSauvegarde = "";
/* Synchronisation du fichier à télécharger */
if (!empty($dataToolbox->filteredData)) {
    $nomFichierSauvegarde = $_SESSION['fichierActuel']['nomInitial']."-".date("Y-m-d");
    $nomFichierSauvegarde .= $dataToolbox->filtered? "-filtered":"";
    $nomFichierSauvegarde .= $dataToolbox->sorted? "-sorted":"";
    $fileToolbox->mettreAJourTempCSV($dataToolbox->filteredData, $nomFichierSauvegarde);
}
// réglage pour le modal de filtrage
$filtresSauvegardes = $fileToolbox->listerFiltres($_SESSION['fichierActuel']['type'], $_SESSION['fichierActuel']['nom']);
$filtresActifs = $dataToolbox->filtreActif ?? [];
$colonnes = [];
if (!empty($_SESSION['data'])) {
    $colonnes = array_keys($_SESSION['data'][0]);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Log files simplifyer</title>
    <link rel="stylesheet" href="style/bootstrap-5.3.8-dist/css/bootstrap.css">
    <link rel="stylesheet" href="style/viewFile.css">
    <script src="script/bootstrap-5.3.8-dist/js/bootstrap.bundle.js"></script>
    <script>
        const filtresInitiaux = <?= json_encode($filtresActifs) ?>;
        const colonnesLog = <?= json_encode($colonnes) ?>;
        const donneesLog = <?= json_encode($_SESSION['data']) ?>;
    </script>
    <script src="script/filtres.js" defer></script>
</head>
<body>
<h1 class="page-title">
    📄 Log Simplifier
    <small class="d-block fs-5 text-secondary">
        by @bouffeur2frittes38
    </small>
</h1>
<div class="filters">
    <div class="ia-filter">
        <form method="post">
            <label for="demandeIA">🤖 Demande à l'IA</label>
            <div class="d-flex gap-2 m-2">
                <input type="text" id="demandeIA" name="demandeIA" class="form-control"
                        placeholder="Ex : trouve les erreurs de connexion d'hier">
                <button type="submit" name="submit" value="ia" class="btn btn-primary">
                    Analyser
                </button>
            </div>
        </form>
    </div>
    <!-- Bouton -->
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFiltres">
        🔎 Filtres
    </button>
    <!-- Modal -->
    <div class="modal fade" id="modalFiltres" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="viewFile.php">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Gestion des filtres
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label for="filtreCharge"> Sélectionner un filtre sauvegardé</label>
                            <select name="filtreCharge" id="filtreCharge">
                                <?php foreach ($filtresSauvegardes as $filtre): ?>
                                    <option value="<?= $filtre['chemin'] ?>">
                                        <?= htmlspecialchars($filtre['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="submit" value="loadFiltre" class="btn btn-secondary">
                                Charger
                            </button>
                            <button type="button"
                                    class="btn btn-success"
                                    onclick="ajouterFiltre()">
                                ➕ Ajouter un filtre
                            </button>
                        </div>
                        <div id="listeFiltres"></div>
                    </div>
                    <div class="modal-footer d-flex gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Fermer
                        </button>
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <label for="nomSauvegarde" class="mb-0 text-nowrap">
                                Nom du filtre
                            </label>
                            <input id="nomSauvegarde" class="form-control" name="nomSauvegarde"
                                   placeholder="Ex : Erreurs 500">
                        </div>
                        <button type="submit" name="submit" value="saveFiltre" class="btn btn-info">
                            Sauvegarder
                        </button>
                        <button type="submit" name="submit" value="Filtrer" class="btn btn-primary">
                            Appliquer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="results">
    <div class="results-header">
        <h2>Exploration des logs</h2>
    </div>
    <div class="table-responsive">
        <table class="table log-table align-middle mb-0">
            <thead>
                <tr>
                    <?php if (!empty($dataToolbox->filteredData)): ?>
                        <?php foreach (array_keys($dataToolbox->filteredData[0]) as $colonne): ?>
                            <th>
                                <button type="submit" form="triForm" name="tri" value="<?= htmlspecialchars($colonne) ?>"
                                        class="btn w-100 h-100 p-0">
                                    <?= htmlspecialchars(ucfirst($colonne)) ?>
                                    <?php if (($dataToolbox->triColonne ?? "") === $colonne): ?>
                                        <?= ($dataToolbox->sensTri ?? "ASC") === "ASC" ? "↑" : "↓" ?>
                                    <?php endif; ?>
                                </button>
                            </th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dataToolbox->filteredData ?? [] as $ligne): ?>
                <tr>
                    <?php foreach ($ligne as $valeur): ?>
                        <td>
                            <div class="cell-content">
                                <?= htmlspecialchars((string)$valeur) ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <a href="<?= "files/temp/".session_id()."/".$nomFichierSauvegarde.".csv" ?>" download class="btn btn-success">
        Télécharger les données CSV
    </a>
</div>
<form id="triForm" method="post">
    <input type="hidden" name="colonneTri" value="<?= htmlspecialchars($dataToolbox->triColonne ?? '') ?>">
    <input type="hidden" name="ordreTri" value="<?= htmlspecialchars($dataToolbox->sensTri ?? 'ASC') ?>">
    <?php
    $count = 0;
    foreach ($dataToolbox->filtreActif ?? [] as $filtre) {
        echo '<input type="hidden" name="filtre'.$count.'colonne" value="'.$filtre->colonne.'">';
        echo '<input type="hidden" name="filtre'.$count.'condition" value="'.$filtre->condition.'">';
        echo '<input type="hidden" name="filtre'.$count.'valeur" value="'.$filtre->valeur.'">';
        $count++;
    }
    ?>
    <input type="hidden" name="submit" value="trier">
</form>
</body>
</html>
