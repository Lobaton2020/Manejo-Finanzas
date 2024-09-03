<?php

class CookTrackingController extends Controller
{
    private $cookTracking;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->cookTracking = $this->model("cookTracking");
    }


    public function index()
    {
        return view("cookTracking.index");
    }

    public function getMarkedDaysPerAnioMonth($anio, $month)
    {
        if (!isset($anio, $month)) {
            return httpResponse(["error" => "Los valores 'anio' o 'mes' son requeridos"])->json();
        }
        $data = $this->cookTracking->getMarkedDaysPerAnioMonth($anio, $month, $this->id);
        return httpResponse($data->array())->json();
    }


    public function getTotalTodo()
    {
        $data = $this->cookTracking->getTotalTodo($this->id);
        return httpResponse($data->array())->json();
    }

    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["date", 'title'], $request)) {
                return redirect("moneyLoan")->with("error", "Lo sentimos, llena todos los campos");
            }
            $data = [
                "user_id" => $this->id,
                "title" => $request->title,
                "descripcion" => $request->descripcion,
                "date" => $request->date,
            ];
            if ($this->cookTracking->insert($data)->array()) {
                return httpResponse()->json();
            } else {
                return httpResponse(500, "ok", "Error al crear nuevo registro")->json();
            }
        });
    }

    public function update($id)
    {
        return execute_post(function ($request) use ($id) {
            if (arrayEmpty(["descipcion"], $request)) {
                return redirect("moneyLoan")->with("error", "La descripcion es obligatoria");
            }
            $data = [
                "descripcion" => $request->descripcion
            ];
            if (isset($request->date)) {
                $data["date"] = $request->date;
            }
            if ($this->cookTracking->update($data, ["id[=]" => $id])->array()) {
                return httpResponse()->json();
            } else {
                return httpResponse(500, "ok", "Error al crear nuevo registro")->json();
            }
        });
    }
}
