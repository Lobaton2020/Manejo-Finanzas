window.URL_PROJECT = "/";
window.options = function (object) {
    return {
        "method": "POST",
        "body": convertFormdata(object)
    };
};


const convertFormdata = (object) => {
    let formData = new FormData();
    for (let property in object) {
        formData.append(property, object[property]);
    };
    return formData;
};

const renderMessage = (type, value) => {
    if (type == "error") type = "danger"
    let string = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">`;
    string += value;
    string += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
    string += '<span aria-hidden="true">&times;</span>';
    string += '</button>';
    string += '</div>';
    return string;
};
const renderErrorMessage = (msg)=> renderMessage('error',msg)
const renderSuccessMessage = (msg)=> renderMessage('success',msg)

const questionRedirection = () => {
    let res = document.querySelectorAll(".question"),
        href;

    [...res].forEach((elem) => {
        elem.addEventListener("click", (e) => {
            e.preventDefault();
            href = e.currentTarget.getAttribute("href");
            Swal.fire({
                title: '¿Estas seguro?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Si, lo estoy!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            })
        })
    });
};

const questionSend = (e) => {
    e.preventDefault();
    Swal.fire({
        title: '¿Estas seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si, lo estoy!'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    })
}

const preventSendForm = () => {
    let elem = document.querySelector("#not-send-form");
    console.log(elem)
    console.log(typeof elem)
    if (elem != null) {
        return true;
    }
    return false;
};



const saveTypeMove = async (e) => {
    e.preventDefault();
    let message = document.querySelector("#show-message");
    if (e.target.name.value.length > 0 && e.target.id_type_move.value.length > 0) {
        let data = {
            id_move_type: e.target.id_type_move.value,
            name: e.target.name.value
        }
        result = await fetch(`${URL_PROJECT}moveType/store`, options(data));
        result = await result.json();
        if (result.status == 200) {
            message.innerHTML = renderMessage("success", " Tipo de movimiento agregado correctamente");
            e.target.reset();
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            if (result.type == "exists") {
                message.innerHTML = renderMessage("error", "Ya tienes un tipo de movimiento con este nombre");
            } else {
                message.innerHTML = renderMessage("error", "No se pudo añadir porcentaje");
            }
        }
    } else {
        message.innerHTML = renderMessage("error", "Debes llenar todos los campos   ");
    }
};

const fixedSidebar = () => {
    document.querySelector(".button-menu-mobile").addEventListener("click", () => {
        if (localStorage.getItem("fixed-sidebar")) {
            localStorage.removeItem("fixed-sidebar")
        } else {
            localStorage.setItem("fixed-sidebar", "true")
        }
    })
    if (window.innerWidth > 500) {

        if (localStorage.getItem("fixed-sidebar")) {
            document.body.classList.add("enlarged")

        } else {
            if (document.body.classList.contains("enlarged")) {
                document.body.classList.remove("enlarged")
            }
        }
    }

};
const showMessageFirstVisit = () => {
    if (parseInt(document.cookie.indexOf("show-cookie")) > 0) {
        $("#first-visit").modal("show");
    }
};

const handleEditBudget = ()=>{
    $("#edit-budget").modal("show");
}

function formatCurrency(e) {
    const value = parseFloat($("#edit-budget").find("input").val() || 0);
    const formattedValue = value.toLocaleString('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2
    });
    document.getElementById('formattedMoney').innerHTML = formattedValue;
}
const updateBudget = async(e)=>{
    let message = document.querySelector("#show-message");
    message.innerHTML = ''
    e.preventDefault();
    if (!e.target.total.value.length > 0) {
        return message.innerHTML = renderErrorMessage( "Debes llenar todos los campos   ");
    }

    result = await fetch(`${URL_PROJECT}main/set_budget`, options({ budget: e.target.total.value }));
    result = await result.json();
    if (result.status == 201) {
        setTimeout(() => { location.reload() }, 2000);
       return message.innerHTML = renderSuccessMessage("Presupuesto actualizado. Recargando en 2s");
    }
    return message.innerHTML = renderErrorMessage( "Opps! Algo fue mal");

}
// Call of functions
window.addEventListener("DOMContentLoaded", () => {
    questionRedirection();
    fixedSidebar();
    showMessageFirstVisit();
    formatCurrency()
});