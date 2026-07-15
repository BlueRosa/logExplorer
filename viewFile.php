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
            $dataToolbox->importData($fileToolbox->extractDataFromFile($fileToolbox->getFichier(
                    $fileToolbox->import($_FILES['file'], $_POST['typeLog']))));
        }
    } else if ($_POST['submit'] == 'csv') {
        if (isset($_FILES['file'])) {
            $dataToolbox->importData($fileToolbox->extractDataFromFile(
                    $fileToolbox->getFichier($fileToolbox->importCSV($_FILES['file'], $_POST['separateur']))));
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
            $fileToolbox->sauvegarder($dataToolbox->filteredData, "Save_".session_id());
            $compteur++;
        }

        $dataToolbox->filteredData = $dataToolbox->filtrer($_POST['regex'] ?? null, $_POST['recherche'] ?? null,
                $_POST['dateDebut'] ?? null, $_POST['dateFin'] ?? null);
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
        $dataToolbox->filteredData = $dataToolbox->filtrer($_POST['regex'] ?? null, $_POST['recherche'] ?? null,
                $_POST['dateDebut'] ?? null, $_POST['dateFin'] ?? null);
        $dataToolbox->filteredData = $dataToolbox->trier($colonneTri, $ordreTri);
    } else if ($_POST['submit'] == 'ia') {
        $dataToolbox->importData(json_decode($_POST['data'], true));
        $filtreIA = demanderIaFiltres($_POST['demandeIA'], $dataToolbox->data[0] ?? []);
        $dataToolbox->filteredData = $dataToolbox->filtrer($filtreIA['regex'] ?? null, $filtreIA['recherche'] ?? null,
                $filtreIA['dateDebut'] ?? null, $filtreIA['dateFin'] ?? null);
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
                <input type="hidden" name="data" value="<?= htmlspecialchars(json_encode($dataToolbox->data)) ?>">
                <button type="submit" name="submit" value="ia" class="btn btn-primary">
                    Analyser
                </button>
            </div>
        </form>
    </div>
    <form action="" class="d-flex gap-2" method="post">
        <div class="filter">
            <label for="regex">Expression régulière</label>
            <input type="text" id="regex" name="regex"
                   placeholder="Ex : ^ERROR.*" value="<?= htmlspecialchars($dataToolbox->filtreActif->regex ?? "") ?>">
        </div>
        <div class="filter">
            <label for="recherche">Recherche</label>
            <input type="text" id="recherche" name="recherche"
                   placeholder="Texte à rechercher" value="<?= htmlspecialchars( $dataToolbox->filtreActif->recherche ?? "" )?>">
        </div>
        <div class="filter">
            <label for="dateDebut">Date de début</label>
            <input type="date" id="dateDebut" name="dateDebut" value="<?=htmlspecialchars( $dataToolbox->filtreActif->dateDebut ?? "" ) ?>">
        </div>
        <div class="filter">
            <label for="dateFin">Date de fin</label>
            <input type="date" id="dateFin" name="dateFin" value="<?= htmlspecialchars( $dataToolbox->filtreActif->dateFin ?? "" )?>">
        </div>
        <div class="filter">
            <label for="filtrer"> </label>
            <input type="submit" name="submit" id="filtrer" class="btn btn-dark pe-3 ps-3" value="Filtrer">
        </div>
        <input type="hidden" hidden value="<?=  htmlspecialchars(json_encode($dataToolbox->data)) ?>" name="data">
    </form>
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
                                value="filtres"
                                class="btn btn-primary">
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
    <a href="<?= LOG_FILE_LOCATION."/TempFile.csv" ?>" download>
        <button type="button">Sauvegarder le fichier en CSV</button>
    </a>
</div>
<form id="triForm" method="post">
    <input type="hidden" name="data" value="<?= htmlspecialchars(json_encode($dataToolbox->data)) ?>">
    <input type="hidden" name="colonneTri" value="<?= htmlspecialchars($dataToolbox->triColonne ?? '') ?>">
    <input type="hidden" name="ordreTri" value="<?= htmlspecialchars($dataToolbox->sensTri ?? 'ASC') ?>">
    <input type="hidden" name="regex" value="<?= htmlspecialchars($dataToolbox->filtreActif->regex ?? '') ?>">
    <input type="hidden" name="recherche" value="<?= htmlspecialchars($dataToolbox->filtreActif->recherche ?? '') ?>">
    <input type="hidden" name="dateDebut" value="<?= htmlspecialchars($dataToolbox->filtreActif->dateDebut ?? '') ?>">
    <input type="hidden" name="dateFin" value="<?= htmlspecialchars($dataToolbox->filtreActif->dateFin ?? '') ?>">
    <input type="hidden" name="submit" value="trier">
</form>
</body>
</html>
