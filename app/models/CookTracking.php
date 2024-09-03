<?php
class CookTracking extends Orm
{
    public function __construct()
    {
        parent::__construct("cook_tracking");
    }

    public function getTotalTodo($user_id)
    {
        $sql = 'SELECT COUNT(*) AS total_todo from cook_tracking where user_id = :user_id';
        $this->querye($sql);
        $this->bind('user_id', $user_id);
        $this->execute();
        return new JSON($this->fetch());
    }
    public function getMarkedDaysPerAnioMonth($anio, $mes, $user_id)
    {
        $sql = "SELECT
        DATE_FORMAT(`date`, '%Y-%m') AS mes_anio,
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'dia', DATE_FORMAT(`date`, '%d'),
                'descripcion', descripcion,
                'title', title,
                'id', id
            )
        ) AS registros
    FROM
        cook_tracking
    WHERE
        DATE_FORMAT(`date`, '%Y-%m') = CONCAT(:anio, '-', :mes) and user_id = :user_id
    GROUP BY
        mes_anio
    ORDER BY
        mes_anio;
    ";
        $this->querye($sql);
        $this->bind(":anio", $anio);
        $this->bind(":mes", $mes);
        $this->bind(':user_id', $user_id);
        $this->execute();
        $res = $this->fetch();
        if (is_object($res)) {
            return new JSON(json_decode($res->registros));
        }
        return new JSON([]);
    }
}
