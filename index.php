<?php
global $listeTypes;
include("includes/fileToolbox.php");
$fileToolbox = new FileToolbox();
$fileToolbox->listerFichiers();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Entrée de document</title>
    <link rel="stylesheet" href="style/bootstrap-5.3.8-dist/css/bootstrap.css">
    <link rel="stylesheet" href="style/index.css">
</head>
<body>
<h1 class="page-title">
    📄 Log Simplifier
    <small class="d-block fs-5 text-secondary">
        by @bouffeur2frittes38
    </small>
</h1>
<h2 class="text-center text-secondary mb-4">
    Choisissez une méthode
</h2>
<div class="container">
    <div class="row g-4 justify-content-center">
        <div class="col-lg-5">
            <div class="upload-card h-100">
                <form action="viewFile.php" enctype="multipart/form-data" method="post">
                    <h2>📤 Importer un fichier de logs</h2>
                    <div class="form-group">
                        <label for="file">Fichier</label>
                        <input type="file" id="file" accept=".log,.txt,.csv" name="file" required>
                        <div class="filter mt-2">
                            <label for="typeLog">Type de log</label>
                            <select class="form-select" name="typeLog" id="typeLog" required>
                                <option value="" selected disabled>
                                    Choisir un type de log...
                                </option>
                                <?php foreach ($listeTypes as $categorie => $types) : ?>
                                    <optgroup label="<?= htmlspecialchars($categorie) ?>">
                                        <?php foreach ($types as $type) : ?>
                                            <option value="<?= htmlspecialchars($type[0]) ?>">
                                                <?= htmlspecialchars($type[0]) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="submit" value="importer" class="btn btn-primary btn-upload">
                        Importer
                    </button>
                </form>
                <hr>
                <form action="viewFile.php" method="post">
                    <h2>📤 Importer un fichier CSV</h2>
                    <div class="form-group">
                        <label for="file">Fichier</label>
                        <input type="file" id="file" accept=".log,.txt,.csv" name="file" required>
                        <div class="filter mt-2">
                            <label for="separateur">Séparateur</label>
                            <input type="text" name="separateur" id="separateur" value="," placeholder="Séparateur...">
                        </div>
                    </div>
                    <button type="submit" name="submit" value="csv" class="btn btn-primary btn-upload">
                        Importer
                    </button>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="upload-card h-100">
                <form action="viewFile.php" method="post">
                    <h2>📂 Ouvrir un fichier importé</h2>
                    <div class="form-group">
                        <label for="fileChoose">Fichier importé</label>
                        <select class="form-select" name="fileChoose" required id="fileChoose">
                            <option selected disabled>Choisissez un fichier...</option>
                            <?php foreach ($fileToolbox->fichiers as $fichier) : ?>
                                <option value="<?=htmlspecialchars($fichier['nomActuel']) ?>">
                                    <?=htmlspecialchars($fichier['dateImport'])." - ".htmlspecialchars($fichier['nomOriginal'])." - ".htmlspecialchars($fichier['type'])?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="submit" value="selectionner" class="btn btn-primary btn-upload">
                        Ouvrir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>