const formatPrice = (e) => {
    let value = parseInt(e.target.value);
    let number = document.querySelector("#number-format");
    number.value = "0";
    if (value) {
        let result = (value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        number.value = result;
    }
};

const saveInflow = (e) => {
    e.preventDefault();
    let porcents = document.querySelectorAll(".porcents"),
        sum = 0,
        numsNegative = false;
    [...porcents].forEach((elem) => {
        if (parseInt(elem.value) >= 0) {
            sum += parseInt(elem.value);
        } else {
            numsNegative = true;
        }
    });
    if (numsNegative) {
        Swal.fire("Numeros negativos!", "Los porcentajes deben ser mayor o igual 0", "error");
        return;
    }
    if (sum == 100) {
        if (preventSendForm()) {
            Swal.fire("Hey!", "Faltan datos por agregar", "info");
        } else {
            e.target.submit();
        }
        return;
    } else {
        Swal.fire("Error!", "La suma de porcentajes no es igual a 100", "error");
    }

};

const savePorcent = async (e) => {
    e.preventDefault();
    let message = document.querySelector("#show-message"),
        template = document.querySelector("#template-porcent"),
        parent = document.querySelector("#elements"),
        empty = document.querySelector("#empty"),
        clon, result;
    if (e.target.name.value.length > 0) {
        result = await fetch(`${URL_PROJECT}porcent/store`, options({ name: e.target.name.value }));
        result = await result.json();
        if (result.status == 200) {
            message.innerHTML = renderMessage("success", "Porcentaje agregado correctamente");
            e.target.reset();
            if (template == null) {
                setTimeout(() => {
                    location.reload()
                }, 1000)
            }
            try {
                let { id_porcent, name } = result.data;
                clon = template.content.cloneNode(true);
                clon.querySelector(".name").textContent = name;
                clon.querySelector(".value").setAttribute("name", `porcents[${id_porcent}]`)
                parent.appendChild(clon);
                if (empty) {
                    empty.innerHTML = "";
                }
            } catch (err) {
                console.log(err)
            }
        } else {
            if (result.type == "exists") {
                message.innerHTML = renderMessage("error", "Ya tienes un porcentaje con este nombre");
            } else {
                message.innerHTML = renderMessage("error", "No se pudo añadir porcentaje");
            }
        }
    } else {
        message.innerHTML = renderMessage("error", "Ingresa un valor");
    }
};

window.addEventListener("DOMContentLoaded", () => { })