const renderStatisticMoneyDisponiblebyDeposits = async() => {
    try {
        let element = document.getElementById("money-deposit-disponible");
        let result = await fetch(`${URL_PROJECT}report/moneyDisponiblebyDeposit`)
        result = await result.json();
        if (result.status == 200) {
            if (result.data.length == 0) {
                element.innerHTML = "<h3 class='text-center text-info'>Ohh</h3><p class='text-muted text-center'>No hay datos por mostrar</p>";
                return;
            }
            let barData = [];
            result.data.forEach(element => {
                barData.push({
                    y: element.name,
                    a: element.total
                });
            });
            $.MorrisCharts.createBarChart("money-deposit-disponible", barData, 'y', ['a'], ['Total'], ['#02c58d']);
        } else {
            element.innerHTML = "<h3 class='text-center text-danger'>Hubo un error al renderizar la grafica</h3>"
        }
    } catch (err) {
        console.error(err)
    }
};

const renderStatisticMoneySpendbyDeposits = async() => {
    try {
        let element = document.getElementById("money-deposit-spend");

        let result = await fetch(`${URL_PROJECT}report/moneySpendbyDeposit`)
        result = await result.json();
        if (result.status == 200) {
            let barData = [];
            if (result.data.length == 0) {
                element.innerHTML = "<h3 class='text-center text-info'>Ohh</h3><p class='text-muted text-center'>No hay datos por mostrar</p>";
                return;
            }
            result.data.forEach(element => {
                barData.push({
                    y: element.name,
                    a: element.total
                });
            });
            $.MorrisCharts.createBarChart("money-deposit-spend", barData, 'y', ['a'], ['Total'], ["#30419b"]);
        } else {
            element.innerHTML = "<h3 class='text-center text-danger'>Hubo un error al renderizar la grafica</h3>"

        }
    } catch (err) {
        console.error(err)
    }
};
const defaultActionAmounts = () => {
    try {
        let clicked = false;// default don't show nothing
        const eyeButton = document.querySelector("#eyeButton")
        eyeButton.addEventListener("click", () => {
            document.querySelectorAll(".show_hide__ammount").forEach(element => {
            if (clicked) {
                return element.innerHTML = `$ -'---.--- <small class='text-muted'>COP</small>`
            }
                return element.innerHTML = element.getAttribute("amount")
            });
            if (clicked) {
                clicked = false;
                eyeButton.innerHTML = `<i class='fa fa-eye'></i>`
                return;
            }
            clicked = true
            eyeButton.innerHTML = `<i class='fa fa-eye-slash'></i>`
        })
    } catch (error) {
        console.error("NO SE PUEDE MOSTRAR PLATA", { error })
    }
}
window.addEventListener("DOMContentLoaded", () => {
    renderStatisticMoneyDisponiblebyDeposits();
    renderStatisticMoneySpendbyDeposits();
    defaultActionAmounts();
});