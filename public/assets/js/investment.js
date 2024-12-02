function calculatePercentageAnualEfective() {
    const monto = parseFloat(prompt("Ingrese el monto invertido:"));
    if (isNaN(monto)) return
    const ganancia = parseFloat(prompt("Ingrese el valor de ganancia:"));
    if (isNaN(ganancia)) return
    const meses = parseInt(prompt("Ingrese el numero de meses:"));
    if (isNaN(meses)) return

    const pea = Math.pow((monto + ganancia) / monto, 12 / meses) - 1;

    alert("El porcentaje efectivo anual es: " + (pea * 100).toFixed(2) + "%");
}


document.addEventListener("DOMContentLoaded", () => {
    formatPrice({
        target: document.querySelector("#retirement_amount")
    }, "#number-format");

    formatPrice({
        target: document.querySelector("#real_retribution")
    }, "#number-format-2");
});
