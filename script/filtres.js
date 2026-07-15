document.addEventListener("DOMContentLoaded", () => {
    if (filtresInitiaux.length > 0) {
        filtresInitiaux.forEach(filtre => {
            ajouterFiltre(filtre);
        });
    } else {
        ajouterFiltre();
    }
});

function ajouterFiltre(filtre = {}) {
    let id = compteurFiltre++;
    let bloc = document.createElement("div");
    bloc.className = "card mb-3 filtre-bloc";

    let optionsColonnes = "";
    colonnesLog.forEach(colonne => {
        optionsColonnes += `
        <option value="${colonne}">
            ${colonne}
        </option>
    `;

    });
    bloc.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h6>
                    🔎 Filtre ${id}
                </h6>
                <button type="button"
                        class="btn btn-danger btn-sm"
                        onclick="supprimerFiltre(this)">
                    🗑
                </button>
            </div>
            <div class="row mt-3">
                <div class="col">
                    <label>
                        Colonne
                    </label>
                    <select class="form-select" name="filtre${id}colonne">
                        ${optionsColonnes}
                    </select>
                </div>
                <div class="col">
                    <label>
                        Condition
                    </label>
                    <select class="form-select"
                            name="filtre${id}condition">
                        <option value="contient">
                            Contient
                        </option>
                        <option value="=">
                            Égal
                        </option>
                        <option value="regex">
                            Regex
                        </option>
                    </select>
                </div>
                <div class="col">
                    <label>
                        Valeur
                    </label>
                    <input class="form-control" name="filtre${id}valeur" placeholder="Valeur">
                </div>
            </div>
        </div>
    `;
    document.getElementById("listeFiltres").appendChild(bloc);
}

function supprimerFiltre(bouton) {
    bouton.closest(".filtre-bloc").remove();
    renumeroterFiltres();
}

function renumeroterFiltres() {
    const filtres = document.querySelectorAll(".filtre-bloc");
    filtres.forEach((bloc, index) => {
        // Change le titre
        bloc.querySelector("h6").innerHTML = "🔎 Filtre " + index;
        // Change les noms des champs envoyés en POST
        bloc.querySelectorAll("select, input").forEach(element => {
            if (element.name) {
                element.name = element.name.replace(
                    /filtre\d+/,
                    "filtre" + index
                );
            }
            if (element.id) {
                element.id = element.id.replace(
                    /filtre\d+/,
                    "filtre" + index
                );
            }
        });
    });
    // Le prochain filtre ajouté doit prendre le bon numéro
    compteurFiltre = filtres.length;
}