<?php

declare(strict_types=1);

namespace App\Models;

class FeeSchedule extends BaseModel
{
    protected static string $table = 'fee_schedule';
    protected static bool $useSoftDeletes = false; // Uses is_active and dates instead

    public function list(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM fee_schedule';

        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1 AND (effective_to IS NULL OR effective_to >= CURDATE())';
        }

        $sql .= ' ORDER BY category ASC, name ASC';

        return $this->db->fetchAll($sql);
    }
}
