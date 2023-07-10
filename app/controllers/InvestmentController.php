<?php
require_once URL_APP . 'models/Investment.php';
class InvestmentController extends Controller
{

    private InvestmentView $investmentView;
    private $investment;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->investmentView = $this->model("investmentView");
        $this->investment = $this->model("investment");
    }
    public function index()
    {

        $data = $this->investmentView->select("*", ["id_user[=]" => $this->id, "state[!=]" => Investment::$InvestmentState["HIDDED"], "AND"], " state ASC")->array();
        $completed = $this->investmentView->getResumeByState(Investment::$InvestmentState["COMPLETED"])->object();
        $actived = $this->investmentView->getResumeByState(Investment::$InvestmentState["ACTIVED"])->object();
        $lost = $this->investmentView->getResumeByState(Investment::$InvestmentState["LOST"])->object();
        $options = [
            "data" => $data,
            "completed" => $completed,
            "actived" => $actived,
            "lost" => $lost,
        ];
        return view("investments.list", $options, true);
    }
    public function edit($id)
    {
        $data = $this->investmentView->get("*", ["id_investment[=]" => $id, "id_user[=]" => $this->id, "AND"])->array();
        $options = [
            "data" => $data,
            "stateList" => array_values(Investment::$InvestmentState),
            "riskList" => array_values(Investment::$levelRisk)
        ];
        array_shift($options["stateList"]);
        return view("investments.edit", $options);
    }
    public function update($id)
    {
        execute_post(function ($request) use ($id) {
            if (
                arrayEmpty([
                    "init_date",
                    "end_date",
                    "state",
                    "sdsd",
                    "risk_level",
                    "real_retribution",
                    "percent_annual_effective"
                ], $request)
            ) {
                return redirect("investment/create")->with("error", "Debes llenar todos los campos requeridos.");
            }
            $where = ["id_investment[=]" => $id, "id_user[=]" => $this->id, "AND"];
            try {
                $belongsToMe = $this->investmentView->get("*", $where)->object();
                if (!$belongsToMe) {
                    throw new ErrorException("No puedes editar este registro");
                }
                $data = [
                    "init_date" => $request->init_date,
                    "end_date" => $request->end_date,
                    "state" => $request->state,
                    "risk_level" => $request->risk_level,
                    "real_retribution" => $request->real_retribution,
                    "percent_annual_effective" => $request->percent_annual_effective
                ];
                $this->investment->update($data, ["id_investment[=]" => $id]);
                return redirect("investment")->with("success", "Inversion actualizada exitosamente.");
            } catch (Exception $e) {
                return redirect("investment/edit/{$id}")->with("error", $e->getMessage());
            }
        });
    }
    public function hide($id)
    {
        $where = ["id_investment[=]" => $id, "id_user[=]" => $this->id, "AND"];
        try {
            $belongsToMe = $this->investmentView->get("*", $where)->object();
            if (!$belongsToMe) {
                throw new ErrorException("No puedes quitar este registro");
            }
            $data = ["state" => Investment::$InvestmentState["HIDDED"]];
            $this->investment->update($data, ["id_investment[=]" => $id]);
            return redirect("investment")->with("info", "Registro quitado exitosamente");
        } catch (Exception $e) {
            return redirect("investment")->with("error", $e->getMessage());
        }
    }
}