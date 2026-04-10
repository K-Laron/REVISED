<?php

declare(strict_types=1);

namespace App\Models;

class DashboardStats extends BaseModel
{
    // This model doesn't represent a single table, but provides cross-model metrics
    protected static string $table = ''; 

    public function getMetrics(): array
    {
        return $this->db->fetchAll(
            "SELECT metric_group, metric_key, metric_value
             FROM (
                 SELECT 'stats' AS metric_group, 'animals_total' AS metric_key, COUNT(*) AS metric_value
                 FROM animals
                 WHERE is_deleted = 0

                 UNION ALL

                 SELECT 'stats' AS metric_group, 'animals_under_care' AS metric_key, COUNT(*) AS metric_value
                 FROM animals
                 WHERE is_deleted = 0 AND status = 'Under Medical Care'

                 UNION ALL

                 SELECT 'stats' AS metric_group, 'adoption_pipeline' AS metric_key, COUNT(*) AS metric_value
                 FROM adoption_applications
                 WHERE is_deleted = 0 AND status NOT IN ('completed', 'rejected', 'withdrawn')

                 UNION ALL

                 SELECT 'occupancy' AS metric_group, COALESCE(status, 'Unknown') AS metric_key, COUNT(*) AS metric_value
                 FROM kennels
                 WHERE is_deleted = 0
                 GROUP BY status

                 UNION ALL

                 SELECT 'medical' AS metric_group, COALESCE(procedure_type, 'Unknown') AS metric_key, COUNT(*) AS metric_value
                 FROM medical_records
                 WHERE is_deleted = 0
                 GROUP BY procedure_type
             ) AS dashboard_metrics"
        );
    }

    public function getTrends(int $months = 12): array
    {
        return $this->db->fetchAll(
            "SELECT source, month_key, total
             FROM (
                 SELECT 'intake' AS source, DATE_FORMAT(intake_date, '%Y-%m') AS month_key, COUNT(*) AS total
                 FROM animals
                 WHERE intake_date >= DATE_SUB(CURDATE(), INTERVAL :months_intake MONTH)
                   AND is_deleted = 0
                 GROUP BY month_key

                 UNION ALL

                 SELECT 'adoptions' AS source, DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total
                 FROM adoption_applications
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months_adoptions MONTH)
                   AND is_deleted = 0
                 GROUP BY month_key
             ) AS trend_counts",
            [
                'months_intake' => $months - 1,
                'months_adoptions' => $months - 1,
            ]
        );
    }
}
