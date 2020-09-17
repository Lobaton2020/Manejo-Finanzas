<?php
function countVisits($countVisits, $id_user)
{
    $id_user = $id_user == null ? 0 : $id_user;
    if ($countVisits->has(["id_user[=]" => $id_user])->array()) {
        $last_count = $countVisits->get(["count"], ["id_user[=]" => $id_user])->array()->count;
        $data = [
            "count" => intval($last_count) + 1,
            "update_at" => getCurrentDatetime()
        ];
        $countVisits->update($data, ["id_user[=]" => $id_user])->array();
    } else {
        $data = [
            "id_user" => $id_user,
            "count" => 1,
            "update_at" => getCurrentDatetime(),
            "create_at" => getCurrentDatetime(),
        ];
        $countVisits->insert($data)->array();
    }
}
