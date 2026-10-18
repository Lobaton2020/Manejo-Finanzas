<?php

/**
 * Inbox/staging de transacciones capturadas desde notificaciones (Android).
 *
 * Esta tabla NO es el registro final. Sirve para que una UI "pre-egreso"
 * muestre las capturas automáticas, el usuario complete datos faltantes
 * y luego promueva la tx a `outflows`. Ver api-spec.md para el contrato.
 *
 * Idempotencia: la PK es el UUID generado por el cliente Android.
 * Re-envíos del mismo batch reportan los IDs duplicados sin error.
 *
 * El schema lo crea el operador a mano (no hay migraciones en este proyecto);
 * ver odd/tasks/notifications-sync-endpoint.md para el DDL exacto.
 */
class NotificationsTransaction extends Orm
{
    public function __construct()
    {
        parent::__construct("notifications_transactions");
    }

    /**
     * Inserta un batch y devuelve accepted/duplicates particionados.
     *
     * @param string $batchId
     * @param string $clientId
     * @param array<int, array<string, mixed>> $rows  Filas YA validadas por el controller.
     * @return array{accepted: string[], duplicates: string[]}
     */
    public function ingestBatch(string $batchId, string $clientId, array $rows): array
    {
        if (empty($rows)) {
            return ['accepted' => [], 'duplicates' => []];
        }

        // 1) Detectar duplicados existentes con un solo SELECT.
        $ids = array_map(fn($r) => $r['id'], $rows);
        $existing = $this->select('id', ['id[[]]' => $ids])->array();
        $existingSet = [];
        foreach ($existing as $row) {
            $existingSet[(string) $row->id] = true;
        }

        $accepted = [];
        $duplicates = [];

        // 2) INSERT IGNORE por fila. Sólo las que no existen ya en la tabla.
        //    rowCount() distingue insertado (1) vs duplicado (0).
        foreach ($rows as $tx) {
            $id = (string) $tx['id'];
            if (isset($existingSet[$id])) {
                $duplicates[] = $id;
                continue;
            }

            $this->querye(
                "INSERT IGNORE INTO notifications_transactions
                    (id, client_id, batch_id, amount, currency, merchant,
                     category, bank, card_brand, card_last_four, payment_method,
                     datetime, captured_at, sources, raw_text)
                 VALUES
                    (:id, :client_id, :batch_id, :amount, :currency, :merchant,
                     :category, :bank, :card_brand, :card_last_four, :payment_method,
                     :datetime, :captured_at, :sources, :raw_text)"
            );
            $this->bind(':id',             $id);
            $this->bind(':client_id',      $clientId);
            $this->bind(':batch_id',       $batchId);
            $this->bind(':amount',         $tx['amount']);
            $this->bind(':currency',       $tx['currency']);
            $this->bind(':merchant',       $tx['merchant']);
            $this->bind(':category',       $tx['category']);
            $this->bind(':bank',           $tx['bank']);
            $this->bind(':card_brand',     $tx['card_brand']  ?? null);
            $this->bind(':card_last_four', $tx['card_last_four'] ?? null);
            $this->bind(':payment_method', $tx['payment_method']);
            $this->bind(':datetime',       (int) $tx['datetime']);
            $this->bind(':captured_at',    (int) $tx['captured_at']);
            $this->bind(':sources',        json_encode($tx['sources'], JSON_UNESCAPED_UNICODE));
            $this->bind(':raw_text',       json_encode($tx['raw_text'], JSON_UNESCAPED_UNICODE));

            $this->execute();
            if ($this->rowCount() > 0) {
                $accepted[] = $id;
            } else {
                // Otro request concurrente lo insertó entre el SELECT y el INSERT.
                $duplicates[] = $id;
            }
        }

        return ['accepted' => $accepted, 'duplicates' => $duplicates];
    }
}