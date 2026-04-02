# Profiled Index Review

Date: April 2, 2026

## Queries To Explain

```sql
EXPLAIN SELECT action, module, record_id, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 18;
EXPLAIN SELECT status, COUNT(*) AS total FROM kennels WHERE is_deleted = 0 GROUP BY status ORDER BY status;
EXPLAIN SELECT DATE_FORMAT(intake_date, '%Y-%m') AS month_key, COUNT(*) AS total FROM animals WHERE intake_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) AND is_deleted = 0 GROUP BY month_key ORDER BY month_key;
EXPLAIN SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total FROM adoption_applications WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) AND is_deleted = 0 GROUP BY month_key ORDER BY month_key;
EXPLAIN SELECT mr.*, a.animal_id AS animal_code, a.name AS animal_name FROM medical_records mr INNER JOIN animals a ON a.id = mr.animal_id WHERE mr.is_deleted = 0 ORDER BY mr.record_date DESC, mr.id DESC LIMIT 26 OFFSET 0;
EXPLAIN SELECT i.*, a.animal_id AS animal_code, a.name AS animal_name FROM invoices i LEFT JOIN animals a ON a.id = i.animal_id WHERE i.is_deleted = 0 ORDER BY i.created_at DESC LIMIT 26 OFFSET 0;
EXPLAIN SELECT u.*, r.name AS role_name, r.display_name AS role_display_name FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE u.is_deleted = 0 ORDER BY u.created_at DESC, u.id DESC LIMIT 26 OFFSET 0;
```

## Findings

| Query | Current Key | Rows | Extra | Action |
| --- | --- | --- | --- | --- |
| `audit_logs` recent activity | `idx_audit_date` | `18` | `Backward index scan` | Keep current index. The query is already index-backed. |
| `kennels` grouped status counts | `idx_kennels_status` | `21` | `Using where` | Add `(is_deleted, status)` to align the active-row filter with the grouped status rollup. |
| `animals` monthly intake | `NULL` | `83` | `Using where; Using temporary; Using filesort` | Add `(is_deleted, intake_date)` to cut the scan to active rows and the measured date range. |
| `adoption_applications` monthly created | `NULL` | `19` | `Using where; Using temporary; Using filesort` | Add `(is_deleted, created_at, id)` to support the active-row time series. |
| `medical_records` page load | `NULL` | `51` | `Using where; Using filesort` | Add `(is_deleted, record_date, id, animal_id)` so the sorted active-record window can be read in index order. |
| `invoices` page load | `NULL` | `4` | `Using where; Using filesort` | Add `(is_deleted, created_at, id, animal_id)` for active invoice ordering and join lookup. |
| `users` page load | `idx_users_role` | `2` per joined role row | `Using where` plus a temporary/filesort on `roles` | Add `(is_deleted, created_at, id, role_id)` so the active user window can be read in the requested order. |

## Migration Scope

- Add new composite indexes only for the measured query families that still scan or filesort.
- Do not replace or drop existing indexes in this pass.
- Leave `audit_logs` unchanged because the current plan is already good enough.
