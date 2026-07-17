let compteurFiltre = 0;
document.addEventListener("DOMContentLoaded", () => {
    if (filtresInitiaux.length > 0) {
        filtresInitiaux.forEach(filtre => ajouterFiltre(filtre));
    }
});

function ajouterFiltre(filtre = {}) {
    const id = compteurFiltre++;
    const colonne = filtre.colonne ?? "";
    const condition = filtre.condition ?? "contient";
    const valeur = filtre.valeur ?? "";
    const bloc = document.createElement("div");
    bloc.className = "card mb-3 filtre-bloc";
    let optionsColonnes = "";
    colonnesLog.forEach(col => {
        optionsColonnes += `
            <option value="${col}" ${col === colonne ? "selected" : ""}>
                ${col}
            </option>
        `;
    });

    bloc.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h6>🔎 Filtre ${id}</h6>
                <button type="button" class="btn btn-danger btn-sm" onclick="supprimerFiltre(this)">
                    🗑
                </button>
            </div>
            <div class="row mt-3">
                <div class="col">
                    <label>Colonne</label>
                    <select class="form-select" name="filtre${id}colonne" onchange="adapterTypeChamp(this)">
                        ${optionsColonnes}
                    </select>
                </div>
                <div class="col">
                    <label>Condition</label>
                    <select class="form-select" name="filtre${id}condition">
                        <option value="contient" ${condition==="contient"?"selected":""}>
                            Contient
                        </option>
                        <option value="=" ${condition==="="?"selected":""}>
                            Égal
                        </option>
                        <option value="regex" ${condition==="regex"?"selected":""}>
                            Regex
                        </option>
                        <option value="<" ${condition==="<"?"selected":""}>
                            Inférieur / Avant
                        </option>
                        <option value=">" ${condition===">"?"selected":""}>
                            Supérieur / Après
                        </option>
                    </select>
                </div>
                <div class="col">
                    <label>Valeur</label>
                    <input class="form-control" name="filtre${id}valeur" value="${valeur}" placeholder="Valeur">
                </div>
            </div>
        </div>
    `;

    document.getElementById("listeFiltres").appendChild(bloc);

    // Détection automatique du type
    adapterTypeChamp(bloc.querySelector(`select[name="filtre${id}colonne"]`));
}

function supprimerFiltre(bouton) {
    bouton.closest(".filtre-bloc").remove();
    renumeroterFiltres();
}

function renumeroterFiltres() {
    const filtres = document.querySelectorAll(".filtre-bloc");
    filtres.forEach((bloc, index) => {
        bloc.querySelector("h6").textContent = "🔎 Filtre " + index;
        bloc.querySelectorAll("select, input").forEach(element => {
            if (element.name) {
                element.name = element.name.replace(
                    /filtre\d+/,
                    "filtre" + index
                );
            }
        });
    });
    compteurFiltre = filtres.length;
}

function adapterTypeChamp(selectColonne) {

    const bloc = selectColonne.closest(".filtre-bloc");
    const input = bloc.querySelector('input[name$="valeur"]');
    const selectCondition = bloc.querySelector('select[name$="condition"]');

    const colonne = selectColonne.value;

    let exemple = "";

    for (const ligne of donneesLog) {
        if (ligne[colonne] !== undefined &&
            ligne[colonne] !== null &&
            ligne[colonne] !== "") {

            exemple = String(ligne[colonne]).trim();
            break;
        }
    }

    let typeDate = false;

    input.type = "text";

    // Date YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(exemple)) {
        input.type = "date";
        typeDate = true;
    }
    // Heure HH:mm ou HH:mm:ss
    else if (/^\d{2}:\d{2}(:\d{2})?$/.test(exemple)) {
        input.type = "time";
        typeDate = true;
    }
    // Date ISO avec heure + Z ou timezone
    else if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?(Z|[+-]\d{2}:\d{2})?$/.test(exemple)) {
        input.type = "datetime-local";
        typeDate = true;
    }


    if (typeDate) {
        selectCondition.innerHTML = `
            <option value="=">
                Égal
            </option>
            <option value="<">
                Avant
            </option>
            <option value=">">
                Après
            </option>
        `;
    } else {
        selectCondition.innerHTML = `
            <option value="contient">
                Contient
            </option>
            <option value="=">
                Égal
            </option>
            <option value="regex">
                Regex
            </option>
            <option value="<">
                Plus petit
            </option>
            <option value=">">
                Plus grand
            </option>
        `;
    }
}