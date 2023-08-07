<?php

class NoteController extends Controller
{
    private $model;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("note");
    }

    public function index()
    {
        $notes = $this->model->select("*", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        foreach ($notes as $note) {
            $note->preview = substr($note->description, 0, 30) . (strlen($note->description) > 30 ? "..." : "");
        }
        return view("notes.list", ["notes" => $notes]);
    }

    public function create()
    {
        return view("notes.create");
    }

    public function edit($id = null)
    {
        $note = $this->model->get("*", ["id_note[=]" => $id, "id_user[=]" => $this->id, "AND"])->array();
        return view("notes.edit", ["note" => $note]);
    }

    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["description", "total"], $request)) {
                return redirect("moneyLoan")->with("error", "Lo sentimos, llena todos los campos");
            }
            $data = [
                "id_user" => $this->id,
                "description" => $request->description,
                "total" => $request->total,
                "status" => 1,
                "create_at" => getCurrentDatetime()
            ];
            if ($this->model->insert($data)->array()) {
                return redirect("note")->with("success", "Hemos añadido tu nota correctamente.");
            } else {
                return redirect("note")->with("error", "Lo sentimos,hubo un error");
            }
        });
    }
    public function update()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["description", "total", "id"], $request)) {
                return redirect("moneyLoan")->with("error", "Lo sentimos, llena todos los campos");
            }
            $data = [
                "description" => $request->description,
                "total" => $request->total,
            ];
            if ($this->model->update($data, ["id_user[=]" => $this->id, "id_note[=]" => $request->id, "AND"])->array()) {
                return redirect("note")->with("success", "Hemos actualizado la nota correctamente.");
            } else {
                return redirect("note")->with("error", "Lo sentimos,hubo un error");
            }
        });
    }

    public function disable($id)
    {
        $cond = ["id_user[=]" => $this->id, "id_note[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->disable($cond)->array()) {
                return redirect("note")->with("info", "Hemos eliminado tu nota.");
            } else {
                return redirect("note")->with("error", "Parece que hubo un error");
            }
        } else {
            return redirect("note")->with("error", "Esta nota no es tuya.");
        }
    }
}
