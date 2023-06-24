<?php
require_once URL_APP . 'services/OutflowService.php';

class BudgetController extends Controller
{
    private $path = "budget";
    private $pathSub = "budget/list_outflows/";
    private $model;
    private $notification;
    private $temporalBudget;
    private $temporalBudgetView;
    private $temporalBudgetOutflow;

    private $porcent;
    private $outflow_type;
    private $category;
    private $outflowService;
    private $modelOutflow;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("porcent");
        $this->notification = $this->model("notification");
        $this->temporalBudget = $this->model("temporalBudget");
        $this->temporalBudgetView = $this->model("temporalBudgetView");
        $this->temporalBudgetOutflow = $this->model("temporalBudgetOutflow");
        $this->porcent = $this->model("porcent");
        $this->outflow_type = $this->model("outflowType");
        $this->category = $this->model("category");
        $this->modelOutflow = $this->model("outflow");

        $this->outflowService = new OutflowServie(
            $this->modelOutflow,
            $this->notification
        );
    }

    public function index()
    {
        $data = $this->temporalBudgetView->select("*", ["id_user[=]" => $this->id])->array();
        return view("budgets.list", ["data" => $data]);
    }
    public function list_outflows($id)
    {
        $data = $this->temporalBudgetOutflow->select("*", ["id_user[=]" => $this->id, "id_temporal_budget[=]" => $id, "AND"])->array();
        foreach ($data as $outflow) {
            $outflow->outfow_type = $this->outflow_type->get("*", ["id_outflow_type[=]" => $outflow->id_outflow_type, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->porcent = $this->porcent->get("*", ["id_porcent[=]" => $outflow->id_porcent, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->category = $this->category->get("*", ["id_category[=]" => $outflow->id_category, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->description = empty($outflow->description) ? "No aplica" : $outflow->description;
            $outflow->is_in_budget = boolval($outflow->is_in_budget) ? "Si" : "No";
            $outflow->temporal_budget_name = $this->temporalBudget->get("*", ["id_temporal_budget[=]" => $outflow->id_temporal_budget, "id_user[=]" => $this->id, "AND"])->array()->name;
        }
        return view("budgets.outflowList", ["data" => $data, "id_temporal_budget" => $id]);
    }
    public function store_element($id)
    {
        execute_post(function ($request) use ($id) {
            if (!$id) {
                exit("El id del presupuesto temporal es requerido");
            }
            if (arrayEmpty(["id_outflow_type", "id_category", "id_porcent", "amount", "is_in_budget"], $request)) {
                return redirect($this->pathSub . $id)->with("error", "Debes llenar todos los campos requeridos.");
            }
            $data = [
                "id_user" => $this->id,
                "id_outflow_type" => $request->id_outflow_type,
                "id_temporal_budget" => $id,
                "id_category" => $request->id_category,
                "id_porcent" => $request->id_porcent,
                "description" => is_correct($request->description),
                "amount" => $request->amount,
                "status" => 1,
                "is_in_budget" => intval($request->is_in_budget),
                "update_at" => getCurrentDatetime(),
                "create_at" => getCurrentDatetime()
            ];
            if ($this->temporalBudgetOutflow->insert($data)->array()) {
                $this->notification->insert([$this->id, "budget-item"]);
                return redirect($this->pathSub . $id)->with("success", "Elemento del presupuesto agregado correctamente.");
            } else {
                return redirect($this->pathSub . $id)->with("error", "No se pudo añadir la salida de dinero");
            }
        });
    }

    public function store()
    {
        execute_post(function ($request) {
            if (arrayEmpty(["name"], $request)) {
                return redirect($this->path)->with("error", "Debes llenar todos los campos requeridos.");
            }
            $data = [
                "id_user" => $this->id,
                "name" => is_correct($request->name),
                "created_at" => getCurrentDatetime()
            ];
            if ($this->temporalBudget->insert($data)->array()) {
                $this->notification->insert([$this->id, "budget"]);
                return redirect($this->path)->with("success", "Presupuesto agregado correctamente.");
            } else {
                return redirect($this->path)->with("error", "No se pudo añadir presupuesto");
            }
        });
    }
    public function update($id)
    {
        execute_post(function ($request) use ($id) {
            if (!$id) {
                exit("El id del presupuesto temporal es requerido");
            }
            if (arrayEmpty(["name"], $request)) {
                return redirect($this->path)->with("error", "Debes llenar todos los campos requeridos.");
            }
            $data = [
                "name" => is_correct($request->name),
            ];
            if ($this->temporalBudget->update($data, ["id_user[=]" => $this->id, "id_temporal_budget[=]" => $id, "AND"])->array()) {
                return redirect($this->path)->with("success", "Presupuesto actualizado correctamente.");
            } else {
                return redirect($this->path)->with("error", "No se pudo actualizar el presupuesto");
            }
        });
    }
    public function execList($id)
    {
        try {
            $egressList = $this->temporalBudgetOutflow->select("*", ["id_user[=]" => $this->id, "id_temporal_budget[=]" => $id, "status[=]" => 1, "AND"])->array();
            foreach ($egressList as $egress) {
                $this->outflowService->perform_egress($this->id, $egress);
            }
            return redirect($this->path)->with("success", "Ejecucion de presupuesto realizada, revisa tus egresos.");
        } catch (Exception $e) {
            return redirect($this->path)->with("error", $e->getMessage());
        }

    }
    public function execOne($id, $id_budget)
    {
        if (!isset($id) || !isset($id_budget)) {
            throw new ErrorException("Bad request, params invalids");
        }
        try {
            $query = ["id_user[=]" => $this->id, "id_temporal_budget_outflow[=]" => $id, "id_temporal_budget[=]" => $id_budget, "status[=]" => 1, "AND"];
            $egress = $this->temporalBudgetOutflow->get("*", $query)->array();
            if (!$egress) {
                throw new ErrorException("Debes tener el estado activo, No data found");
            }
            $this->outflowService->perform_egress($this->id, $egress);
            return redirect($this->pathSub . $id_budget)->with("success", "Egreso hecho de forma correcta");
        } catch (Exception $e) {
            return redirect($this->pathSub . $id_budget)->with("error", $e->getMessage());
        }

    }

    public function disable($id, $id_budget)
    {
        $cond = ["id_user[=]" => $this->id, "id_temporal_budget_outflow[=]" => $id, "AND"];
        if ($this->temporalBudgetOutflow->has($cond)->array()) {
            if ($this->temporalBudgetOutflow->disable($cond)->array()) {
                return redirect($this->pathSub . $id_budget)->with("success", "Estado actualizado exitosamente");
            } else {
                return redirect($this->pathSub . $id_budget)->with("error", "No se pudo inactivar");
            }
        } else {
            return redirect($this->pathSub . $id_budget)->with("error", "El estado no pertenece a los tuyos.");
        }
    }
    public function enable($id, $id_budget)
    {
        $cond = ["id_user[=]" => $this->id, "id_temporal_budget_outflow[=]" => $id, "AND"];
        if ($this->temporalBudgetOutflow->has($cond)->array()) {
            if ($this->temporalBudgetOutflow->enable($cond)->array()) {
                return redirect($this->pathSub . $id_budget)->with("success", "Estado actualizado exitosamente");
            } else {
                return redirect($this->pathSub . $id_budget)->with("error", "No se pudo activar");
            }
        } else {
            return redirect($this->pathSub . $id_budget)->with("error", "El estado no pertenece a los tuyos.");
        }
    }
    public function delete_element($id, $id_budget)
    {
        $cond = ["id_user[=]" => $this->id, "id_temporal_budget_outflow[=]" => $id, "AND"];
        if ($this->temporalBudgetOutflow->has($cond)->array()) {
            if ($this->temporalBudgetOutflow->delete($cond)->array()) {
                return redirect($this->pathSub . $id_budget)->with("success", "Estado eliminado correctamente");
            } else {
                return redirect($this->pathSub . $id_budget)->with("error", "No se pudo eliminar");
            }
        } else {
            return redirect($this->pathSub . $id_budget)->with("error", "El estado no pertenece a los tuyos.");
        }
    }
    public function delete($id)
    {
        $cond = ["id_user[=]" => $this->id, "id_temporal_budget[=]" => $id, "AND"];
        if ($this->temporalBudgetOutflow->count($cond)->array() > 0) {
            return redirect($this->path)->with("error", "Elimina los elementos del presupuesto primero");
        }
        if ($this->temporalBudget->delete($cond)->array()) {
            return redirect($this->path)->with("success", "El presupuesto se ha eliminado con exito");
        } else {
            return redirect($this->path)->with("error", "No se pudo eliminar");
        }
    }
}