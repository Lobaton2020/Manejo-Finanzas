const handlerSaveQuerySql = async (e) => {
    e.preventDefault();
    let message = document.querySelector("#show-message")
    const data = {
        description: e.target.descriptionQuerySql.value,
        query: e.target.querySql.value
    }
    if (data.query.length > 0) {
        let result = await fetch(`${URL_PROJECT}query/store`, options(data));
        result = await result.json();
        if (result.status == 200) {
            message.innerHTML = renderMessage("success", "Consulta SQL alamacenada correctamente");
            e.target.reset();
        } else {
            message.innerHTML = renderMessage("error", "Error al guardar consulta SQL");
        }
    } else {
        message.innerHTML = renderMessage("error", "Ingresa una consulta SQL");
    }

};
const handlerShowQuerForm = (e) => {
    e.preventDefault()
    $("#show-queries").modal("hide")
    const query = e.target.parentNode.previousElementSibling.textContent
    const description = e.target.parentNode.previousElementSibling.previousElementSibling.textContent

    document.querySelector("#field-sql").value = query
    document.querySelector("#description-query").textContent = description
};
const formatDateTime = (dateString) => {
    const fecha = new Date(dateString);
    const opciones = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        second: 'numeric',
        timeZoneName: 'short'
    }
    return new Intl.DateTimeFormat('es-ES', opciones).format(fecha);
}
const handlerEditQuerForm = (id, description, query) => {
    try {
        document.querySelector('[data-target="#add-querie-sql"]').click()
        document.querySelector('#show-queries > div > div > div.modal-header > button > span').click()
        document.querySelector('#myModalLabel').textContent = "Actualizar Consulta SQL"
        document.querySelector('[name="descriptionQuerySql"]').value = description
        document.querySelector('[name="querySql"]').value = query
        const form = document.querySelector('#form-item')
        form.removeAttribute("onsubmit")
        form.setAttribute("action", URL_PROJECT + "query/update/" + id)
        form.setAttribute("method", "POST")

    } catch (err) {
        console.error("ERROR_EDIT", err);
    }
}
const handlerShowQueries = async () => {
    $("#show-queries").modal("show")
    let container = document.querySelector("#show-data-result"),
        message = document.querySelector("#show-message"),
        template = document.querySelector("#card-show-queries"),
        fragment = document.createDocumentFragment(),
        clon, result;
    result = await fetch(`${URL_PROJECT}query/queries`)
    result = await result.json()
    const { data, status } = result
    if (status == 200) {

        data.forEach(({ id_query, description, query, create_at }) => {
            clon = template.content.cloneNode(true)
            clon.querySelector(".description").textContent = description
            clon.querySelector(".query").textContent = query
            clon.querySelector(".date").textContent = formatDateTime(create_at)
            clon.querySelector(".delete-query-form").href = `${URL_PROJECT}query/delete/${id_query}`
            clon.querySelector(".add-query-form").addEventListener("click", handlerShowQuerForm)
            clon.querySelector(".edit-query-form").addEventListener("click", (e) => handlerEditQuerForm(id_query, description, query))
            fragment.appendChild(clon)
        });
        container.innerHTML = ""
        container.appendChild(fragment)
    } else {
        message.innerHTML = renderMessage("error", "No se pudo cargar los datos");
    }
};

const loader = (type = false) => {
    const loader = document.getElementById("loader")
    type == true ?
        loader.style.display = "block" :
        loader.style.display = "none";
}

const drawTable = (elem, data) => {

    const makeThead = (info) => {
        let thead = document.createElement("thead")
        let tr = document.createElement("tr")
        info.forEach(item => {
            let th = document.createElement("th")
            th.innerHTML = item
            tr.appendChild(th)
        });
        thead.appendChild(tr)
        return thead
    };
    const evalToNumberFormat = (number) => {
        let num = String(number)
        if (Number(num) === Number(num)) {
            if (!isNaN(parseFloat(number))) {
                num = parseFloat(num)
                return new Intl.NumberFormat("de-DE").format(num)
            }
        }
        return number
    }
    const makeTbody = (data) => {
        let tbody = document.createElement("tbody")
        data.forEach(item => {
            let tr = document.createElement("tr")
            let values = Object.values(item)
            values.forEach(subitem => {
                let td = document.createElement("td")
                td.textContent = evalToNumberFormat(subitem)
                tr.appendChild(td)
            })
            tbody.appendChild(tr)
        });

        return tbody
    };
    const makeTable = (data) => {
        let keys = Object.keys(data[0])
        let table = document.createElement("table")
        let container = document.createElement("div")
        container.className = "table-responsive"
        table.id = "datatable"
        table.className = "table table-striped table-bordered dt-responsive nowrap"
        table.style = "border-collapse: collapse; border-spacing: 0; width: 100%;"
        table.appendChild(makeThead(keys))
        table.appendChild(makeTbody(data))
        container.appendChild(table)
        return container
    }

    let title = elem.firstElementChild
    elem.innerHTML = ""
    elem.style.display = "block"
    elem.appendChild(title)
    elem.appendChild(makeTable(data))
    //Funcion creada en /assets/template/pages/datatables-init.js
    InitDataTables()
}
const drawGraphic = (elem, data) => {
    let graphicBars = []
    elem.innerHTML = ""
    let is_show = true
    data.forEach(element => {
        if (Object.keys(element).length != 2) {
            is_show = false
            return
        }
        const [name, total] = Object.values(element)
        graphicBars.push({
            y: name,
            a: total
        });

    });
    if (is_show) {
        $.MorrisCharts.createBarChart(elem, graphicBars, 'y', ['a'], ['Total'], ["#30419b"]);
    } else {
        elem.innerHTML = `
        <h3 class='text-center text-info'>Ohh</h3>
        <p class='text-muted text-center'>No hay datos por mostrar </p>
        <p class='text-muted text-center'>
        Recuerda lo siguiente
        <ul class='text-muted text-center'>
                <li>En la consulta se esperan 2 resultado</li>
                <li>Un nombre y un total, para mostrar correctamente el grafico</li>
            </ul>
        </p>
        `;
    }
}

const makeQueryServer = (query, elemSuccess, elemError, elemGraphic) => {
    let querySql = new String(query);
    if (querySql.length > 0) {
        loader(true);
        querySql = querySql.replace(/\s/g, "-");
        (async () => {
            const res = await fetch(`${URL_PROJECT}query/sql/${querySql}`)
            let data = await res.text()
            if (data.trim().startsWith("SQLSTATE")) {
                elemError.innerHTML = renderMessage("error", data)
                elemSuccess.style.display = "none"
                loader(false)
            } else {
                data = JSON.parse(data)
                if (data.status == 400) {
                    loader(false)
                    elemSuccess.style.display = "none"
                    return elemError.innerHTML = renderMessage("error", data.message)
                }
                if (data.data.length == 0) {
                    loader(false)
                    elemSuccess.style.display = "none"
                    return elemError.innerHTML = renderMessage("info", "No se encontró Informacion, La tabla, vista o entidad esta vacia  ")
                }
                elemError.innerHTML = ""
                drawTable(elemSuccess, data.data)
                drawGraphic(elemGraphic, data.data)
                loader(false)
            }
        })()
    } else {
        Swal.fire({
            title: "Campo vacio",
            icon: "error",
            text: "Pon un string SQL para hacer la consulta."
        })
    }
}


const queryStringSQL = () => {
    const buttonPlay = document.getElementById("button-play")
    const fieldSql = document.getElementById("field-sql")
    const elemSuccess = document.getElementById("result-data")
    const elemError = document.getElementById("result-error")
    const buttonShowQueries = document.getElementById("button-show-queries")
    const elemGraphic = document.getElementById("div-show-graphics")
    buttonShowQueries.addEventListener("click", handlerShowQueries)
    fieldSql.addEventListener("keyup", (e) => {
        if (e.ctrlKey && e.keyCode == 13) {
            makeQueryServer(fieldSql.value, elemSuccess, elemError, elemGraphic)
        }
    });
    buttonPlay.addEventListener("click", (e) => makeQueryServer(fieldSql.value, elemSuccess, elemError, elemGraphic));
};
const initQueries = () => {
    queryStringSQL();
};

document.addEventListener("DOMContentLoaded", initQueries)