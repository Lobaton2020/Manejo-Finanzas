const MESES = [
    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];
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

const renderStatisticNetWorthByMonth = async () => {
    try {
        const id = "money-net-worth"
        let element = document.getElementById(id);
        let result = await fetch(`${URL_PROJECT}report/getNetWorth`)
        result = await result.json();
        if (result.status == 200) {
            if (result.data.length == 0) {
                element.innerHTML = "<h3 class='text-center text-info'>Ohh</h3><p class='text-muted text-center'>No hay datos por mostrar</p>";
                return;
            }
            let data = result.data.map(({ year, month, net_worth }) => ({
                x: `${year}-${month}`,
                a: parseInt(net_worth),
            }));
            const [
                keys,
                labels,
                colors
            ] = [
                    ['a'],
                    ['Patrimonio Neto'],
                    ['#1DB4F1']
                ]
            $.MorrisCharts.createAreaChart(id, 0, 0, data, 'x', keys, labels, colors);
        } else {
            element.innerHTML = "<h3 class='text-center text-danger'>Hubo un error al renderizar la grafica</h3>"
        }
    } catch (err) {
        console.error(err)
    }
};
const renderStatisticNetWorthWithMoneyLoans = async () => {
    try {
        const id = "money-net-worth-moneyloan"
        let element = document.getElementById(id);
        let result = await fetch(`${URL_PROJECT}report/getNetWorthWithRestMoneyLoans`)
        result = await result.json();
        if (result.status == 200) {
            if (result.data.length == 0) {
                element.innerHTML = "<h3 class='text-center text-info'>Ohh</h3><p class='text-muted text-center'>No hay datos por mostrar</p>";
                return;
            }
            let data = result.data.map(({ year, month, net_worth }) => ({
                x: `${year}-${month}`,
                a: parseInt(net_worth),
            }));
            const [
                keys,
                labels,
                colors
            ] = [
                    ['a'],
                    ['Patrimonio con prestamos a otros'],
                    ['#FCBE2D']
                ]
            $.MorrisCharts.createAreaChart(id, 0, 0, data, 'x', keys, labels, colors);
        } else {
            element.innerHTML = "<h3 class='text-center text-danger'>Hubo un error al renderizar la grafica</h3>"
        }
    } catch (err) {
        console.error(err)
    }
};

const renderStatisticNetWortDetail = async () => {
    try {
        const id = "money-net-worth-detail"
        let element = document.getElementById(id);
        let result = await fetch(`${URL_PROJECT}report/getNetWorth`)
        result = await result.json();
        if (result.status == 200) {
            if (result.data.length == 0) {
                element.innerHTML = "<h3 class='text-center text-info'>Ohh</h3><p class='text-muted text-center'>No hay datos por mostrar</p>";
                return;
            }
            let data = result.data.map(({ year, month, net_worth, total_revenue, inflow, outflow }) => ({
                x: `${year}-${month}`,
                a: parseInt(net_worth),
                b: parseInt(total_revenue),
                c: parseInt(inflow),
                d: parseInt(outflow)
            }));
            const [
                keys,
                labels,
                colors
            ] = [
                    ['a', 'b', 'c', 'd'],
                    ['Patrimonio neto (blue)', 'Ganancias(purple)', 'Ingresos(green)', 'Egresos (red)'],
                    ['#59c6fb', '#30419b', '#02c58d', '#fc5454']
                ]
            $.MorrisCharts.createLineChart(id, data, 'x', keys, labels, colors);

        } else {
            element.innerHTML = "<h3 class='text-center text-danger'>Hubo un error al renderizar la grafica</h3>"
        }
    } catch (err) {
        console.error(err)
    }
};

window.addEventListener("DOMContentLoaded", () => {
    renderStatisticNetWorthWithMoneyLoans();
    renderStatisticMoneyDisponiblebyDeposits();
    renderStatisticMoneySpendbyDeposits();
    renderStatisticNetWorthByMonth();
    renderStatisticNetWortDetail();
    defaultActionAmounts();
});