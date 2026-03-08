# Budget Execute Feature - SPEC.md

## Current Flow

1. **Budget List** (`budgets/list.php`): Shows all budgets
2. **Budget Items** (`budgets/outflowList.php`): Shows items in a budget with:
   - Columns: ID, Budget Name, Outflow Type, Category, Deposit (%), Amount, Description, Status, In Budget
   - "Execute" button per item submits to `budget/execOne/:id/:id_budget`

3. **Execute Action** (`BudgetController::execOne`):
   - Validates the item exists and is active
   - Calls `OutflowService::perform_egress()` which:
     - Checks if sufficient balance exists in the deposit
     - Creates an actual outflow record
     - Optionally creates investment if outflow type contains "inversion"

## User Requirement

> "When I click on the execute button I want to edit the value previously seen on the table"

**Interpretation**: Before executing an item, user wants to **modify the amount** (and possibly other fields) from what's stored in the table.

## Questions

1. **Edit Scope**: Which fields should be editable before execute?
   - [ ] Only `amount`
   - [ ] `amount` + `description`
   - [ ] All fields (outflow type, category, deposit, amount, description)

2. **Edit Location**: 
   - [ ] Inline editing on the table row
   - [ ] Modal popup with the item data pre-filled
   - [ ] Redirect to a pre-filled edit form

3. **Validation**:
   - Should the amount be validated against available balance before saving?
   - Should percentage be editable (recalculates amount)?

4. **Single vs Batch**:
   - Is this only for single item execution (`execOne`) or also for executing all items at once (`execList`)?

## Proposed Solution (if only amount is editable via modal)

1. Add "Edit" button next to "Execute" button in outflowList.php
2. Create modal that loads current item data via AJAX
3. On save, update the temporal_budget_outflow record
4. Then proceed with execute (or let user click execute after edit)
