<?php

class AdminController extends Controller
{
    private $user;
    private $rol_b;
    private $docuemnt_type;
    private $token;
    private $notification;
    private $loggin;
    private $count_visit;
    private $notification_type;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->only_access_admin();
        $this->user = $this->model("user");
        $this->rol_b = $this->model("rol");
        $this->token = $this->model("tokenRegister");
        $this->docuemnt_type = $this->model("documentType");
        $this->notification = $this->model("notification");
        $this->loggin = $this->model("loggin");
        $this->count_visit = $this->model("countVisit");
        $this->notification_type = $this->model("notificationType");
    }
    public function index()
    {
        return redirect("admin/users");
    }

    public function users()
    {
        $users = $this->user->select("*", ["id_user[<>]" => $this->id])->array();
        foreach ($users as $user) {
            $user->rol = $this->rol_b->get("*", ["id_rol[=]" => $user->id_rol])->array()->name;
            $user->document = $this->docuemnt_type->get("*", ["id_document_type[=]" => $user->id_document_type])->array()->abrev;
            unset($user->password);
        }
        return view("admin.usersList", ["users" => $users]);
    }

    public function tokens()
    {
        $tokens = $this->token->select("*", ["id_user[=]" => $this->id])->array();
        foreach ($tokens as $token) {
            $token->rol = $this->rol_b->get("*", ["id_rol[=]" => $token->id_rol])->array()->name;
            $url_token = URL_PROJECT . "auth/tokenRegister/" . $token->token;
            $name_token = substr($url_token, 0, 40);
            $token->token = "<a target='_blank' class='text-decoration-underline' href='{$url_token}'>{$name_token}</a>";
        }
        return view("admin.tokensList", ["tokens" => $tokens]);
    }
    public function notifications()
    {
        $notifications = $this->notification->select()->array();
        foreach ($notifications as $notification) {
            $type = $this->notification_type->get("*", ["key_notification_type[=]" => $notification->key_notification_type])->array()->name;
            $user = $this->user->get("*", ["id_user[=]" => $notification->id_user])->array()->complete_name;
            $notification->description = "<b>" . ucwords($user) . "</b>" . $type;
        }
        return view("admin.notificationsList", ["notifications" => $notifications]);
    }

    public function visits()
    {
        $visits = $this->count_visit->select()->array();
        foreach ($visits as $visit) {
            $visit->count = intval(intval($visit->count) / 3);
            $visit->user = $this->user->get("*", ["id_user[=]" => $visit->id_user])->array();
            if (isset($visit->user->complete_name)) {
                if ($visit->user->id_user == $this->id) {
                    $visit->user = "Tú";
                } else {
                    $visit->user = ucwords($visit->user->complete_name);
                }
            } else {
                $visit->user = "Anonimo";
            }
        }
        return view("admin.visitsList", ["visits" => $visits]);
    }
    public function loggins()
    {
        $loggins = $this->loggin->select()->array();
        foreach ($loggins as $loggin) {
            $user = $this->user->get("*", ["id_user[=]" => $loggin->id_user])->array()->complete_name;
            $loggin->user = ucwords($user);
            $loggin->browser = substr($loggin->browser, 0, strlen($loggin->browser) / 2) . "<br>" . substr($loggin->browser, strlen($loggin->browser) / 2, strlen($loggin->browser));
        }
        return view("admin.logginsList", ["loggins" => $loggins]);
    }
}
