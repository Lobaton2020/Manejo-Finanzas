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
    const route = getCurrentRoute();
    console.log('Current route:', route);

    if (route === 'investment') {
        initinitChartistPie();
        formatPrice({
            target: document.querySelector("#retirement_amount")
        }, "#number-format");

        formatPrice({
            target: document.querySelector("#real_retribution")
        }, "#number-format-2");
    }

    initGroupInlineEdit();
});

function initGroupInlineEdit() {
    console.log('[GroupEdit] Init loaded');
}

// Funciones globales para inline edit
function groupEditClick(btn) {
    console.log('[GroupEdit] Edit clicked, ID:', btn.dataset.id);
    const row = btn.closest('tr');
    row.querySelectorAll('.group-display-value').forEach(el => el.classList.add('d-none'));
    row.querySelectorAll('.group-edit-input').forEach(el => el.classList.remove('d-none'));
    row.querySelectorAll('.group-edit-btn, .group-delete-btn').forEach(el => el.classList.add('d-none'));
    row.querySelectorAll('.group-save-btn, .group-cancel-btn').forEach(el => el.classList.remove('d-none'));
}

function groupCancelClick(btn) {
    console.log('[GroupEdit] Cancel clicked');
    const row = btn.closest('tr');
    row.querySelectorAll('.group-display-value').forEach(el => el.classList.remove('d-none'));
    row.querySelectorAll('.group-edit-input').forEach(el => el.classList.add('d-none'));
    row.querySelectorAll('.group-edit-btn, .group-delete-btn').forEach(el => el.classList.remove('d-none'));
    row.querySelectorAll('.group-save-btn, .group-cancel-btn').forEach(el => el.classList.add('d-none'));
}

function groupSaveClick(btn) {
    console.log('[GroupEdit] Save clicked, ID:', btn.dataset.id);
    const row = btn.closest('tr');
    const id = btn.dataset.id;
    const name = row.querySelector('.group-name-input').value;
    const description = row.querySelector('.group-desc-input').value;
    console.log('[GroupEdit] Saving, name:', name, 'description:', description);

    const formData = new FormData();
    formData.append('name', name);
    formData.append('description', description);

    fetch(URL_PROJECT + 'investment/groupsUpdateInline/' + id, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log('[GroupEdit] Saved:', data);
        location.reload();
    })
    .catch(err => {
        console.error('[GroupEdit] Error:', err);
        alert('Error al guardar');
    });
}
