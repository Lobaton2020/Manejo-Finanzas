<?php
require_once "../initializer.php";
require_once "../models/MoneyLoanListener.php";
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
setlocale(LC_TIME, 'es_CO.UTF-8');
class Templates {
    public const DEUDA = 'deuda';
    public const SUBSCRIPCION = 'subsription';
}
function addOneDay($date){
    return date('Y-m-d', strtotime($date . ' +1 day'));
}
function formatDateColombia($date) {
    $timestamp = strtotime($date);
    return strftime('%e de %B de %Y', $timestamp);
}

function formatSubscriptionDay($date) {
    $day = date('j', strtotime($date));
    return "el $day del presente mes";
}
function httpRequestPost($url, $data)
{
    $ch = curl_init(trim($url));

    $payload = json_encode($data);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrNo = curl_errno($ch);

    $debugInfo = [
        'url' => $url,
        'payload' => $data,
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'response' => $response,
    ];

    curl_close($ch);

    if ($curlErrNo || $httpCode >= 400) {
        throw new Exception("HTTP Request failed:\n" . print_r($debugInfo, true));
    }

    return $response;
}


try {
    $moneyLoanListener = new MoneyLoanListener();
    $listNotifications = $moneyLoanListener->getNotificationListeners()->array();
    $ids = [];
    foreach ($listNotifications as $notification) {
        $template = $notification->is_subscription
                    ? Templates::SUBSCRIPCION
                    : Templates::DEUDA;
        $fecha = $notification->is_subscription
                    ? formatSubscriptionDay(addOneDay($notification->expiration_date))
                    : formatDateColombia(addOneDay($notification->expiration_date));
        $subject =  $notification->is_subscription
                    ? "¡Tienes un pago pendiente! Recordatorio de renovación de suscripción"
                    : "¡Recordatorio amable! Tu crédito esta pendiente de pago";
        $payload = [
            "from" => "Andres Lobaton <noreply@" . $_ENV["DOMAIN_EMAIL"] . ">",
            "to" => ["andrespipe021028+copy@gmail.com", $notification->email_to],
            "subject" => $subject,
            "template" => $template,
            "payload_template" => [
                "nombre_destinatario" => $notification->username_to,
                "monto_pendiente" => number_format($notification->total, 0, '', '.'),
                "nombre_empresa" => "Lobaton SAS",
                "fecha_vencimiento" =>  $fecha,
            ]
        ];
        if($notification->is_subscription) {
            $payload["payload_template"]["nombre_servicio"] = "Youtube premium";
        }
        $response = httpRequestPost($_ENV["URL_EMAIL_NOTIFIER_MS"], $payload);

        $moneyLoanListener->actualizarUltimoLlamado($notification->id);
        array_push($ids, $notification->id);
    }
    http_response_code(200);
    echo json_encode(["is_error" => false, "message" => "Proceso finalizado con exito", "data" => [ "ids" => $ids]]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["is_error" => true, "message" => $e->getMessage()]);
}