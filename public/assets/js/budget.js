
const editTemporalBudget = (e) => {
    try {
        e.preventDefault()
        const form = document.querySelector("#form-item-budget")
        form.name.value = e.currentTarget.dataset.name
        form.setAttribute("action", form.action.replace("store", "update/" + e.currentTarget.dataset.id))
    } catch (err) {
        console.log("ERROR", err)
    }

}

const openEditBudgetItemModal = (e) => {
    try {
        e.preventDefault()
        const button = e.currentTarget
        const id = button.dataset.id
        const amount = button.dataset.amount
        const description = button.dataset.description
        const budgetId = button.dataset.budget

        document.getElementById('edit_id_temporal_budget_outflow').value = id
        document.getElementById('edit_id_temporal_budget').value = budgetId
        document.getElementById('edit_amount').value = amount
        document.getElementById('edit_description').value = description || ''

        $('#editBudgetItemModal').modal('show')
    } catch (err) {
        console.log("ERROR", err)
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const route = getCurrentRoute();

    if (route === 'budget') {
        editTemporalBudget();
        setTimeout(editTemporalBudget, 2000)
    }
});