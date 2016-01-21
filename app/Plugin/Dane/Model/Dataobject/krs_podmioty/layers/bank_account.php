<?

return $this->DB->selectAssoc("
    SELECT id, bank_account, created_at, updated_at, status
    FROM krs_pozycje_bank_accounts
    WHERE krs_pozycje_id = {$id}
    ORDER BY id DESC
    LIMIT 1
");