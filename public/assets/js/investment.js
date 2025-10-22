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
const fNumber = (number) => {
    return number.toLocaleString('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0
    });
}
async function initinitChartistPie() {
    try {
        const portfolio = fetch(`${URL_PROJECT}query/sql/SELECT-iv.name,-sum(iv.amount)-as-amount-FROM-investments_view-AS-iv-WHERE-state-=-'Activo'-GROUP-BY-iv.name`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(res => res.json());
        const risk = fetch(`${URL_PROJECT}query/sql/SELECT-iv.risk_level-as-name,-sum(iv.amount)-as-amount-FROM-investments_view-AS-iv-WHERE-state-=-'Activo'-GROUP-BY-iv.risk_level`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(res => res.json());
        const [{ data: portfolioData }, { data: riskData }] = await Promise.all([portfolio, risk]);
        
        const totalPortfolio = portfolioData.reduce((total, item) => total + parseFloat(item.amount), 0);
        const mapPortfolio = portfolioData.map(item => {
            return {
                name: item.name,
                amount: parseFloat(item.amount),
                percentage: Math.round((parseFloat(item.amount) * 100) / totalPortfolio)
            }
        })
        const chartPortfolio = new Chartist.Pie('#simple-pie-porfolio', {
            labels: mapPortfolio.map(item => item.name),
            series: mapPortfolio.map(item => item.percentage),
        },{
            donut: true,
            showLabel: true,
            labelInterpolationFnc: function(value) {
                return ''
              },
            plugins: [
                Chartist.plugins.tooltip(
                    {
                        tooltipFnc: function(_, percentage) {
                            const porfolio = mapPortfolio.find(item => item.percentage == percentage);
                            return porfolio.name + ' | ' + fNumber(porfolio.amount) + ' | ' + porfolio.percentage + '%';
                          }
                    }
                )
            ]
        });
        
        const totalRisk = riskData.reduce((total, item) => total + parseFloat(item.amount), 0);
        const mapRisk = riskData.map(item => {
            return {
                name: item.name,
                amount: parseFloat(item.amount),
                percentage: Math.round((parseFloat(item.amount) * 100) / totalRisk)
            }
        })
        const chartRisk = new Chartist.Pie('#simple-pie-risk', {
            labels: mapRisk.map(item => item.name),
            series: mapRisk.map(item => item.percentage)
        },{
            plugins: [
                Chartist.plugins.tooltip({
                    tooltipFnc: function(_, percentage) {
                        const risk = mapRisk.find(item => item.percentage == percentage);
                        return risk.name + ' | ' + fNumber(risk.amount) + ' | ' + risk.percentage + '%';
                    }
                }),
            ],
            labelInterpolationFnc: function(value) {
                return '';
              }
        });
    } catch (error) {
        console.log("initinitChartistPie", error);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    initinitChartistPie();
    formatPrice({
        target: document.querySelector("#retirement_amount")
    }, "#number-format");

    formatPrice({
        target: document.querySelector("#real_retribution")
    }, "#number-format-2");


});
