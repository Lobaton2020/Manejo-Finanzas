const renderCategories = async (e) => {
    let template = document.querySelector("#template"),
        parent = document.querySelector("#elements"),
        spaceEmpty = document.querySelector("#empty"),
        clon, result;

    result = await fetch(`${URL_PROJECT}/category/select/${e.target.value}`);
    result = await result.json();
    console.log(result);
    if (result.status == 200) {
        if (result.data.length > 0) {
            spaceEmpty.innerHTML = "";
            parent.innerHTML = "";
        } else {
            parent.innerHTML = "";
            spaceEmpty.innerHTML = "<h5 class='text-center'>Ohh!<h5>";
            spaceEmpty.innerHTML += "<span class='text-muted catchme'>No se han encontrado categorias. Agrega una</span>";
            spaceEmpty.style.marginTop = "20px"
        }
        result.data.forEach((data) => {
            let { id_category, name } = data;
            clon = template.content.cloneNode(true);
            clon.querySelector(".name").textContent = name;
            clon.querySelector(".name").setAttribute('for', id_category)
            clon.querySelector(".value").setAttribute("value", id_category)
            clon.querySelector(".value").setAttribute("id", id_category)
            parent.appendChild(clon);
        })
    } else {

        message.innerHTML = renderMessage("error", "Error al cargar los datos");

    }
};



const saveOutflow = (e) => {
    e.preventDefault();
    if (preventSendForm()) {
        Swal.fire("Hey!", "Faltan datos por agregar", "info");
    } else {
        if (document.querySelector(".catchme")) {
            Swal.fire("Hey!", "Faltan datos por agregar", "info");
        } else {
            e.target.submit();
        }
    }
};

const saveCategory = async (e) => {
    e.preventDefault();
    let typeOutflow = document.querySelector("#inflow_type"),
        message = document.querySelector("#show-message"),
        template = document.querySelector("#template"),
        parent = document.querySelector("#elements"),
        spaceEmpty = document.querySelector("#empty"),
        clon, result;
    if (!typeOutflow) {
        message.innerHTML = renderMessage("error", "Selecciona un tipo de movimiento de egreso");
        return;
    }

    if (typeOutflow.value.length > 0) {
        if (e.target.name.value.length > 0) {
            let data = {
                id_outflow_type: typeOutflow.value,
                name: e.target.name.value
            };
            result = await fetch(`${URL_PROJECT}category/store`, options(data));
            result = await result.json();
            if (result.status == 200) {
                message.innerHTML = renderMessage("success", "Porcentaje agregado correctamente");
                e.target.reset();

                try {

                    let { id_category, name } = result.data;
                    clon = template.content.cloneNode(true);
                    clon.querySelector(".name").textContent = name;
                    clon.querySelector(".value").setAttribute("value", id_category)
                    parent.appendChild(clon);
                    spaceEmpty.innerHTML = "";

                } catch (err) {
                    console.log(err)
                }

            } else {
                if (result.type == "exists") {
                    message.innerHTML = renderMessage("error", "Ya tienes una categoria con este nombre");
                } else {
                    message.innerHTML = renderMessage("error", "No se pudo añadir la categoria");
                }

            }
        } else {
            message.innerHTML = renderMessage("error", "Ingresa un valor");
        }
    } else {
        message.innerHTML = renderMessage("error", "Primero selecciona tipo de movimiento de egreso");

    }

};

const saveCategoryEgress = async (e) => {
    e.preventDefault();
    let typeOutflow = document.querySelector("#inflow_type"),
        message = document.querySelector("#show-message"),
        template = document.querySelector("#template"),
        parent = document.querySelector("#elements"),
        clon, result;
    if (!typeOutflow) {
        message.innerHTML = renderMessage("error", "Selecciona un tipo de movimiento de egreso");
        return;
    }
    try {

        if (typeOutflow.value.length > 0) {
            if (e.target.name.value.length > 0) {
                let data = {
                    id_outflow_type: typeOutflow.value,
                    name: e.target.name.value
                };
                result = await fetch(`${URL_PROJECT}category/store`, options(data));
                result = await result.json();
                if (result.status == 200) {
                    message.innerHTML = renderMessage("success", "Categoria de egreso agregada correctamente");
                    e.target.reset();
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    if (result.type == "exists") {
                        message.innerHTML = renderMessage("error", "Ya tienes una categoria con este nombre");
                    } else {
                        message.innerHTML = renderMessage("error", "No se pudo añadir la categoria");
                    }
                }
            } else {
                message.innerHTML = renderMessage("error", "Ingresa un valor");
            }
        } else {
            message.innerHTML = renderMessage("error", "Primero selecciona tipo de movimiento de egreso");
        }
    } catch (err) {
        console.log(err)
    }
};

