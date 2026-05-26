<?php
class OutflowServie
{
    private $model;
    private $notification;
    private $outflowType;
    private $investment;
    function __construct(
        $model,
        $notification,
        $outflowType,
        $investment
    ) {
        $this->model = $model;
        $this->notification = $notification;
        $this->outflowType = $outflowType;
        $this->investment = $investment;
    }
    public function get_amounts_disponible()
    {
        require_once URL_APP . "controllers/ReportController.php";
        $report = new ReportController();
        return toArray($report->moneyDisponiblebyDeposit())->data;
    }
    public function is_amount_disponible($id_porcent, $amount)
    {
        $disponibles = $this->get_amounts_disponible();
        $is_amount_disponible = false;
        foreach ($disponibles as $disponible) {
            if ($id_porcent == $disponible->id_porcent) {
                if ($amount <= $disponible->total) {
                    $is_amount_disponible = true;
                }
            }
        }
        ;
        return $is_amount_disponible;
    }

    function perform_egress($id_user, $request)
    {


        if (!$this->is_amount_disponible($request->id_porcent, $request->amount)) {
            throw new ErrorException(" El saldo del deposito seleccionado, NO es suficiente para el monto que quieres retirar.");
        }
        $data = [
            "id_user" => $id_user,
            "id_outflow_type" => $request->id_outflow_type,
            "id_category" => $request->id_category,
            "id_porcent" => $request->id_porcent,
            "description" => is_correct($request->description),
            "amount" => $request->amount,
            "set_date" => isset($request->set_date) ? $request->set_date : getCurrentDatetime(),
            "status" => 1,
            "is_in_budget" => intval($request->is_in_budget),
            "update_at" => getCurrentDatetime(),
            "create_at" => getCurrentDatetime()
        ];
        if ($this->model->insert($data)->array()) {
            $this->notification->insert([$id_user, "egress"]);
            $outflow_type_name = $this->outflowType->get(["name"], ["id_outflow_type[=]" => $request->id_outflow_type]);
            if ($outflow_type_name->object() && strpos(strtolower($outflow_type_name->object()->name), 'inversion') !== false) {
                $groupId = isset($request->id_group_investment) && !empty($request->id_group_investment) ? $request->id_group_investment : null;
                $this->investment->create($this->model->id(), $groupId);
            }
            return "OK";
        } else {
            throw new ErrorException("No se pudo añadir la salida de dinero");
        }
    }
}