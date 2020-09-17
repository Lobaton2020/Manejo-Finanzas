<?php

class AuthController extends Controller
{
    private $notification;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication("SECONDARY");
        $this->notification = $this->model("notification");
    }

    public function index()
    {
        return view("auth.login", [], false);
    }

    public function login()
    {
        execute_post(function ($request) {
            if (arrayEmpty(["email", "password"], $request)) {
                return redirect("auth")->with("error", "Debes llenar todo los campos.");
            }
            $user = $this->model("user");
            $rol = $this->model("rol");
            $document = $this->model("documentType");
            $user = $user->login($request);
            if ($user) {
                $name_rol = $rol->get(["name"], ["id_rol[=]" => $user->id_rol])->array()->name;
                $name_document =  $document->get(["name"], ["id_document_type[=]" => $user->id_document_type])->array()->name;
                $credentials = [
                    "id" => $user->id_user,
                    "rol" => ["id" => $user->id_rol, "name" => $name_rol],
                    "name" => $user->complete_name,
                    "email" => $user->email,
                    "document" => ["id" => $user->id_document_type, "name" => $name_document, "number" => $user->number_document],
                    "image" => $user->image
                ];
                $this->setSession($credentials);
                $model_login = $this->model("loggin");
                $data = [
                    "id_user" => $user->id_user,
                    "browser" => $_SERVER["HTTP_USER_AGENT"],
                    "server" => $_SERVER["REMOTE_ADDR"],
                    "create_at" => getCurrentDatetime()
                ];
                $model_login->insert($data);
                return redirect("main")->with("info", "Bienvenid@ {$credentials["name"]}.");;
            } else {
                return redirect("auth")->with("error", "Error de autenticacion.");
            }
        });
    }

    public function tokenRegister($tokenParam = null)
    {
        $token = $this->model("tokenRegister");
        $document_type = $this->model("documentType");
        if ($token->has(["token[=]" => $tokenParam])->array()) {
            if ($token->has(["status[=]" => 1, "token[=]" => $tokenParam, "AND"])->array()) {
                $type_documents = $document_type->select()->array();
                return view("auth.register", ["token" => $tokenParam, "type_documents" => $type_documents], false);
            } else {
                addMessage("error", "Lo sentimos. El token se encuentra inactivo. Pide al un administrador que lo active.");
                return view("layouts.error", ["only_message" => true], false);
            }
        } else {
            addMessage("error", "El token se ha vencido o ya lo tomaste.");
            return view("layouts.error", ["only_message" => true], false);
        }
    }

    public function recoveryPassword()
    {
        return view("auth.recoveryPassword", [], false);
    }

    public function storeUser()
    {
        execute_post(function ($request) {
            $user = $this->model("user");
            $document_type = $this->model("documentType");

            $token = $this->model("tokenRegister");
            $type_documents = $document_type->select()->array();

            if (arrayEmpty(["token"], $request)) {
                addMessage("error", "No existe un token que podamos validar. Ingresa nuevamente con el link.");
                return view("auth.register", ["token_empty" => true], false);
            }
            if (arrayEmpty(["type_document", "document", "name", "email", "password"], $request)) {
                addMessage("error", "Deber llebar todos los campos");
                return view("auth.register", ["token" => $request->token, "type_documents" => $type_documents], false);
            }
            if (!intval($request->document) || intval($request->document) < 10000) {
                addMessage("error", "El numero de documento es invalido");
                return view("auth.register", ["token" => $request->token, "type_documents" => $type_documents], false);
            }
            if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                addMessage("error", "El correo es invalido");
                return view("auth.register", ["token" => $request->token, "type_documents" => $type_documents], false);
            }
            if ($user->has(["email[=]" => $request->email])->array()) {
                addMessage("error", "El correo ya se encuentra registrado");
                return view("auth.register", ["token" => $request->token, "type_documents" => $type_documents], false);
            }
            if ($user->has(["number_document[=]" => $request->document])->array()) {
                addMessage("error", "El numero de documento ya se encuentra registrado");
                return view("auth.register", ["token" => $request->token, "type_documents" => $type_documents], false);
            }
            if ($token->has(["token[=]" => $request->token, "status[=]" => 1, "AND"])->array()) {
                $result = $token->get("*", ["token[=]" => $request->token])->array();

                $data = [
                    "id_rol" => $result->id_rol,
                    "id_document_type" => $request->type_document,
                    "number_document" => $request->document,
                    "complete_name" => $request->name,
                    "email" => $request->email,
                    "password" => encrypt($request->password),
                    "status" => 1,
                    "update_at" => getCurrentDatetime(),
                    "create_at" => getCurrentDatetime()
                ];
                if ($user->insert($data)->array()) {
                    $this->notification->insert([$user->id(), "register"]);
                    $token->delete(["id_token_register[=]" => $result->id_token_register]);
                    $link = URL_PROJECT . "auth";
                    $a = "<a class='text-decoration-underline' href={$link}>Iniciar sesion</a>";
                    addMessage("success", "<b>Bien!</b>.Felicidades has sido registrado correctamente. {$a}");
                    return view("auth.register", ["token_empty" => true], false);
                } else {
                    addMessage("error", "Lo sentimos, no se pudo registrarte.");
                    return view("auth.register", ["token_empty" => true], false);
                }
            } else {
                addMessage("error", "Lo sentimos, El token se ha vencido.");
                return view("auth.register", ["token_empty" => true], false);
            }
        });
    }

    public function sendMail()
    {
        execute_post(function ($request) {
            if (!empty($request->email)) {
                if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                    $user_model = $this->model("user");
                    if ($user_model->has(["email[=]" => $request->email, "status[=]" => 1, "AND"])->array()) {
                        $domain = DOMAIN;
                        $headers = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                        $headers .= "From: Envio Automatico. No Reponder <no-reply@{$domain}>" . "\r\n";
                        $title = "Recuperacion de contraseña | Tus Finanzas";
                        $user = $user_model->get("*", ["email[=]" => $request->email])->array();
                        $token = token_();
                        $url_link = URL_PROJECT . "auth/receiveTokenRecoveryPassword/" . $token;
                        $config = [
                            "name_user" => ucwords($user->complete_name),
                            "name_company" => "Tus Finanzas",
                            "url_link" => $url_link,
                            "name_link" => substr($url_link, 0, 80) . "..."
                        ];
                        $content = structure_html_send_email($config);

                        if (@mail($user->email, $title, $content, $headers)) {
                            $data = [
                                "recovery_pass_token" => $token,
                            ];
                            if ($user_model->update($data, ["id_user[=]" => $user->id_user])->array()) {
                                return redirect("auth/recoveryPassword")->with("success", "Revisa tu correo y dale click al enlace.");
                            } else {
                                return redirect("auth/recoveryPassword")->with("error", "Error del token vuelve a intetar.");
                            }
                        } else {
                            return redirect("auth/recoveryPassword")->with("error", "No se pudo enviar el correo.");
                        }
                    } else {
                        return redirect("auth/recoveryPassword")->with("error", "El correo ingresado no existe.");
                    }
                } else {
                    return redirect("auth/recoveryPassword")->with("error", "Ingresa un correo valido");
                }
            } else {
                return redirect("auth/recoveryPassword")->with("error", "Debes ingresar un email");
            }
        });
    }


    function receiveTokenRecoveryPassword($token)
    {
        $user = $this->model("user");
        if ($user->has(["recovery_pass_token[=]" => $token])->array()) {
            return view("auth.newPassword", ["token" => $token], false);
        } else {
            addMessage("info", "El token ya se ha vencido");
            return view("auth.login", [], false);
        }
    }
    function changePasswordWithToken()
    {
        return execute_post(function ($request) {
            $user = $this->model("user");

            if (arrayEmpty(["token"], $request)) {
                addMessage("error", "No existe un token que podamos validar. Ingresa nuevamente con el link.");
                return view("auth.newPassword", ["token_empty" => true], false);
            }
            if (arrayEmpty(["password", "password_repeat"], $request)) {
                addMessage("error", "Deber llebar todos los campos");
                return view("auth.newPassword", ["token" => $request->token], false);
            }
            if ($request->password != $request->password_repeat) {
                addMessage("error", "Las contraseñan no coinciden");
                return view("auth.newPassword", ["token" => $request->token], false);
            }
            if (strlen($request->password) < 8) {
                addMessage("error", "Debes poner minimo 8 caracteres");
                return view("auth.newPassword", ["token" => $request->token], false);
            }
            if ($user->has(["recovery_pass_token[=]" => $request->token])->array()) {
                $data = [
                    "password" => encrypt($request->password),
                    "recovery_pass_token" => null
                ];
                $user_data = $user->get("id_user", ["recovery_pass_token[=]" => $request->token])->array();
                if ($user->update($data, ["id_user[=]" => $user_data->id_user])->array()) {
                    $link = route("auth");
                    $a = "<a class='text-decoration-underline text-light' href={$link}>Iniciar sesion</a>";
                    addMessage("success", "Bien! Contrasena actualizada!. {$a}");
                    return view("auth.newPassword", ["token_empty" => true], false);
                } else {
                    addMessage("error", "Lo sentimos. Error al realizar el proceso");
                    return view("auth.newPassword", ["token_empty" => true], false);
                }
                dd("ok");
            } else {
                addMessage("error", "Lo sentimos. El token ya se ha vencido");
                return view("auth.newPassword", ["token_empty" => true], false);
            }
        });
    }
}
