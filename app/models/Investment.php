<?php
class Investment extends Orm
{
    static $InvestmentState = [
        "HIDDED" => "Ocultar",
        "CREATED" => "Creado",
        "EXPIRED" => "Expirado",
        "CANCELED" => "Cancelado",
        "ACTIVED" => "Activo",
        "COMPLETED" => "Completado",
        "LOST" => "Perdido",
    ];
    static $levelRisk = [
        "LOW" => "Conservador",
        "MEDIUM" => 'Moderado',
        "HIGH" => 'Agresivo'
    ];
    public function __construct()
    {
        parent::__construct("investments");
    }

    public function create($outflowId)
    {
        $this->insert([
            "id_outflow" => $outflowId,
            "state" => Investment::$InvestmentState["CREATED"],
            "risk_level" => Investment::$levelRisk["LOW"],
            "created_at" => getCurrentDatetime(),
            "updated_at" => getCurrentDatetime()
        ]);
    }

    public function updateExpirationState()
    {
        $this->update(
            [
                "updated_at" => getCurrentDatetime(),
                "state" => [
                    "alias" => "state_xx",
                    "value" => Investment::$InvestmentState["EXPIRED"]
                ]
            ],
            ["end_date[<]" => date("Y-m-d"), "state[=]" => Investment::$InvestmentState["ACTIVED"], "AND"]
        );
    }
}