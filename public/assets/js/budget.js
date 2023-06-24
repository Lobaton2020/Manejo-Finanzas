
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

document.addEventListener("DOMContentLoaded", editTemporalBudget)
setTimeout(editTemporalBudget, 2000)