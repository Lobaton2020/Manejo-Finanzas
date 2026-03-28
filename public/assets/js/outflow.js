const renderCategories = async (e) => {
    let template = document.querySelector("#template"),
        parent = document.querySelector("#elements"),
        spaceEmpty = document.querySelector("#empty"),
        clon, result;

    result = await fetch(`${URL_PROJECT}category/select/${e.target.value}`);
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
                message.innerHTML = renderMessage("success", "Categoria agregada correctamente");
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

const OUTFLOW_URL = URL_PROJECT + 'outflow';

function initOutflowFilters() {
    if (!document.getElementById('filterModal')) return;

    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Seleccionar...',
            allowClear: true
        });
        
        $('#filterCategory').select2({
            width: '100%',
            placeholder: 'Buscar categoría...',
            allowClear: true,
            minimumInputLength: 0,
            dropdownParent: $('#filterModal')
        });
    }

    initQuickDateRange();
    initDateNavigation();
    initFilterForm();
    initApplyFilters();
    initClearFilters();
    initRemoveFilterChips();
}

function applyQuickDateRange(value) {
    const fromDate = document.getElementById('filterDateFrom');
    const toDate = document.getElementById('filterDateTo');
    
    if (!value || !fromDate || !toDate) return;
    
    const today = new Date();
    let from, to;
    
    switch(value) {
        case 'today':
            from = today;
            to = today;
            break;
        case 'yesterday':
            from = new Date(today);
            from.setDate(from.getDate() - 1);
            to = new Date(from);
            break;
        case 'last2days':
            from = new Date(today);
            from.setDate(from.getDate() - 2);
            to = today;
            break;
        case 'last7days':
            from = new Date(today);
            from.setDate(from.getDate() - 7);
            to = today;
            break;
        case 'last30days':
            from = new Date(today);
            from.setDate(from.getDate() - 30);
            to = today;
            break;
        case 'last90days':
            from = new Date(today);
            from.setDate(from.getDate() - 90);
            to = today;
            break;
        case 'last6months':
            from = new Date(today);
            from.setMonth(from.getMonth() - 6);
            to = today;
            break;
        case 'lastyear':
            from = new Date(today);
            from.setFullYear(from.getFullYear() - 1);
            to = today;
            break;
        case 'current_month':
            from = new Date(today.getFullYear(), today.getMonth(), 1);
            to = today;
            break;
        case 'last_year_full':
            from = new Date(today.getFullYear() - 1, 0, 1);
            to = new Date(today.getFullYear() - 1, 11, 31);
            break;
        case 'last2years':
            from = new Date(today);
            from.setFullYear(from.getFullYear() - 2);
            to = today;
            break;
        case 'last3years':
            from = new Date(today);
            from.setFullYear(from.getFullYear() - 3);
            to = today;
            break;
        case 'year_to_date':
            from = new Date(today.getFullYear(), 0, 1);
            to = today;
            break;
    }
    
    if (from && to) {
        fromDate.value = from.toISOString().split('T')[0];
        toDate.value = to.toISOString().split('T')[0];
    }
}

function initQuickDateRange() {
    const quickDateRange = document.getElementById('quickDateRange');
    if (!quickDateRange) return;

    quickDateRange.addEventListener('change', function() {
        applyQuickDateRange(this.value);
    });

    const fromDate = document.getElementById('filterDateFrom');
    const toDate = document.getElementById('filterDateTo');
    
    if (fromDate && toDate) {
        fromDate.addEventListener('change', updateQuickDateRange);
        toDate.addEventListener('change', updateQuickDateRange);
    }
}

function updateQuickDateRange() {
    const from = document.getElementById('filterDateFrom').value;
    const to = document.getElementById('filterDateTo').value;
    const quickSelect = document.getElementById('quickDateRange');
    
    if (from || to) {
        quickSelect.value = '';
    }
}

function initDateNavigation() {
    const prevBtn = document.getElementById('datePrevBtn');
    const nextBtn = document.getElementById('dateNextBtn');
    const quickSelect = document.getElementById('quickDateRange');
    const fromDate = document.getElementById('filterDateFrom');
    const toDate = document.getElementById('filterDateTo');
    
    if (!prevBtn || !nextBtn || !quickSelect || !fromDate || !toDate) return;
    
    function navigateRange(direction) {
        const fromVal = fromDate.value;
        const toVal = toDate.value;
        
        if (!fromVal || !toVal) {
            quickSelect.value = 'last30days';
            applyQuickDateRange('last30days');
            return;
        }
        
        const fromD = new Date(fromVal);
        const toD = new Date(toVal);
        const diffDays = Math.round((toD - fromD) / (1000 * 60 * 60 * 24)) + 1;
        
        let newFrom, newTo;
        
        if (direction === 'prev') {
            newFrom = new Date(fromD);
            newFrom.setDate(newFrom.getDate() - diffDays);
            newTo = new Date(fromD);
            newTo.setDate(newTo.getDate() - 1);
        } else {
            newFrom = new Date(toD);
            newFrom.setDate(newFrom.getDate() + 1);
            newTo = new Date(toD);
            newTo.setDate(newTo.getDate() + diffDays);
        }
        
        fromDate.value = newFrom.toISOString().split('T')[0];
        toDate.value = newTo.toISOString().split('T')[0];
        quickSelect.value = '';
    }
    
    prevBtn.addEventListener('click', () => navigateRange('prev'));
    nextBtn.addEventListener('click', () => navigateRange('next'));
}

function initFilterForm() {
    const filterModal = document.getElementById('filterModal');
    if (!filterModal) return;

    $('#filterModal').on('show.bs.modal', function() {
        const saved = sessionStorage.getItem('outflowFilters');
        if (saved) {
            const filters = JSON.parse(saved);
            Object.keys(filters).forEach(key => {
                const el = document.querySelector(`[name="${key}"]`);
                if (el) {
                    if (el.type === 'radio') {
                        const radio = document.querySelector(`[name="${key}"][value="${filters[key]}"]`);
                        if (radio) radio.checked = true;
                    } else if (el.multiple) {
                        const values = Array.isArray(filters[key]) ? filters[key] : [filters[key]];
                        Array.from(el.options).forEach(opt => {
                            opt.selected = values.includes(opt.value);
                        });
                        $(el).trigger('change');
                    } else {
                        el.value = filters[key];
                    }
                }
            });
        }
    });
}

function saveFiltersToSession() {
    const form = document.getElementById('filterForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const filters = {};
    for (let [key, value] of formData.entries()) {
        if (filters[key]) {
            if (!Array.isArray(filters[key])) {
                filters[key] = [filters[key]];
            }
            filters[key].push(value);
        } else {
            filters[key] = value;
        }
    }
    sessionStorage.setItem('outflowFilters', JSON.stringify(filters));
}

function initApplyFilters() {
    const applyBtn = document.getElementById('applyFilters');
    if (!applyBtn) return;

    applyBtn.addEventListener('click', function() {
        saveFiltersToSession();
        
        const params = new URLSearchParams();
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);

        for (let [key, value] of formData.entries()) {
            if (value !== '') {
                if (params.has(key)) {
                    params.append(key, value);
                } else {
                    params.set(key, value);
                }
            }
        }

        const radios = ['is_in_budget'];
        radios.forEach(name => {
            const checked = document.querySelector(`input[name="${name}"]:checked`);
            if (checked && checked.value !== '') {
                params.set(name, checked.value);
            }
        });

        params.set('page', '1');
        
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('length')) {
            params.set('length', urlParams.get('length'));
        }

        const newUrl = OUTFLOW_URL + (params.toString() ? '?' + params.toString() : '');
        window.location.href = newUrl;
    });
}

function initClearFilters() {
    const clearBtn = document.getElementById('clearFilters');
    const clearHeaderBtn = document.querySelector('.clear-all-filters');
    
    const clearAll = function() {
        sessionStorage.removeItem('outflowFilters');
        window.location.href = OUTFLOW_URL;
    };

    if (clearBtn) {
        clearBtn.addEventListener('click', clearAll);
    }
    
    if (clearHeaderBtn) {
        clearHeaderBtn.addEventListener('click', clearAll);
    }
}

function initRemoveFilterChips() {
    const chips = document.querySelectorAll('.remove-filter');
    chips.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const key = this.dataset.key;
            const params = new URLSearchParams(window.location.search);
            
            params.delete(key);
            params.delete(key + '[]');
            
            params.set('page', '1');
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('length')) {
                params.set('length', urlParams.get('length'));
            }
            
            const filters = {};
            for (let [k, v] of params.entries()) {
                if (filters[k]) {
                    if (!Array.isArray(filters[k])) {
                        filters[k] = [filters[k]];
                    }
                    filters[k].push(v);
                } else {
                    filters[k] = v;
                }
            }
            sessionStorage.setItem('outflowFilters', JSON.stringify(filters));
            
            window.location.href = OUTFLOW_URL + (params.toString() ? '?' + params.toString() : '');
        });
    });
}

window.addEventListener("DOMContentLoaded", () => {
    const route = getCurrentRoute();
    
    if (route === 'outflow') {
        initOutflowFilters();
    }
});