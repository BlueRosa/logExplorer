<?php
include("includes/fileToolbox.php");
include("includes/dataToolbox.php");
include("includes/ollama.php");
session_start();
$fileToolbox = new FileToolbox();
$dataToolbox = new DataToolbox();
$filtres = [];
$compteurDepart = 0;

if (isset($_POST['submit'])) {
    if ($_POST['submit'] == 'importer') {
        if (isset($_FILES['file'])) {

            $fichier = $fileToolbox->import(
                    $_FILES['file'],
                    $_POST['typeLog']
            );

            $fileToolbox->fichier = $fichier;

            $dataToolbox->importData(
                    $fileToolbox->extractDataFromFile(
                            $fileToolbox->getFichier($fichier)
                    )
            );

        }
    } else if ($_POST['submit'] == 'csv') {
        if (isset($_FILES['file'])) {
            $fichier = $fileToolbox->importCSV($_FILES['file'], $_POST['separateur']);


            $fileToolbox->fichier = $fichier;

            $dataToolbox->importData($fileToolbox->extractDataFromFile(
                    $fileToolbox->getFichier($fichier)));

        }
    } else if ($_POST['submit'] == 'selectionner') {
        if (isset($_POST['fileChoose'])) {

            $dataToolbox->importData($fileToolbox->extractDataFromFile($fileToolbox->getFichier($_POST['fileChoose'])));

        }
    } else if ($_POST['submit'] == 'Filtrer') {

        $dataToolbox->importData(json_decode($_POST['data'], true));

        $compteur = 0;
        while (isset($_POST['filtre'.$compteur."colonne"]) && isset($_POST['filtre'.$compteur."condition"]) &&
                isset($_POST['filtre'.$compteur."valeur"]) ) {
            $dataToolbox->ajouterFiltre($_POST['filtre'.$compteur."colonne"], $_POST['filtre'.$compteur."condition"],
                    $_POST['filtre'.$compteur."valeur"]);
            $compteur++;
        }
        $dataToolbox->filtrer($dataToolbox->filtreActif);
        $fileToolbox->sauvegarder($dataToolbox->filteredData, "Save_".session_id());
        $fileToolbox->fichier = $_POST['file'];

    } else if ($_POST['submit'] == 'trier') {

        $dataToolbox->importData(json_decode($_POST['data'], true));

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
        $fileToolbox->sauvegarder($dataToolbox->filteredData, "Save_".session_id());
        $fileToolbox->fichier = $_POST['file'];

        $dataToolbox->filteredData = $dataToolbox->trier($colonneTri, $ordreTri);

    } else if ($_POST['submit'] == 'ia') {

        $dataToolbox->importData(json_decode($_POST['data'], true));
        /* partie pas à jour
        $filtreIA = demanderIaFiltres($_POST['demandeIA'], $dataToolbox->data[0] ?? []);

        $compteur = 0;
        while (isset($_POST['filtre'.$compteur."colonne"]) && isset($_POST['filtre'.$compteur."condition"]) &&
                isset($_POST['filtre'.$compteur."valeur"]) ) {
            $dataToolbox->ajouterFiltre($_POST['filtre'.$compteur."colonne"], $_POST['filtre'.$compteur."condition"],
                    $_POST['filtre'.$compteur."valeur"]);
            $compteur++;
        }
        */
        $fileToolbox->sauvegarder($dataToolbox->filteredData, "Save_".session_id());
        $fileToolbox->fichier = $_POST['file'];
        $dataToolbox->filtrer($dataToolbox->filtreActif);
    } else if ($_POST['submit'] == 'saveFiltre') {
        echo "";


    } else if ($_POST['submit'] == 'loadFiltre') {

        echo "";

    }
}

$colonnes = [];
if (!empty($dataToolbox->data)) {
    $colonnes = array_keys($dataToolbox->data[0]);
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
        const filtresInitiaux = <?= json_encode($filtres) ?>;
        let compteurFiltre = <?= $compteurDepart ?>;
        const colonnesLog = <?= json_encode($colonnes) ?>;
        const donneesLog = <?= json_encode($dataToolbox->data) ?>;
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
                <input type="hidden" name="file" value="<?= $fileToolbox->fichier ?? "" ?>">
                <input type="hidden" name="data" value="<?= htmlspecialchars(json_encode($dataToolbox->data)) ?>">
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
                        <div id="listeFiltres"></div>
                        <button type="button"
                                class="btn btn-success"
                                onclick="ajouterFiltre()">
                            ➕ Ajouter un filtre
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Fermer
                        </button>
                        <button type="submit"
                                name="submit"
                                value="Filtrer"
                                class="btn btn-primary">
                            Appliquer
                        </button>
                        <button type="submit"
                                name="submit"
                                value="saveFiltre"
                                class="btn btn-info">
                            Sauvegarder
                        </button>
                    </div>
                    <input type="hidden" name="data" value="<?= htmlspecialchars(json_encode($dataToolbox->data)) ?>">
                    <input type="hidden" name="file" value="<?= $fileToolbox->fichier ?? "" ?>">
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
    <a href="<?= LOG_FILE_LOCATION."/TempFile.csv" ?>" download>
        <button type="button">Sauvegarder le fichier en CSV</button>
    </a>
</div>
<form id="triForm" method="post">
    <input type="hidden" name="data" value="<?= htmlspecialchars(json_encode($dataToolbox->data)) ?>">
    <input type="hidden" name="file" value="<?= $fileToolbox->fichier ?? "" ?>">
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
