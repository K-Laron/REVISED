<?php

declare(strict_types=1);

namespace App\Models;

class AdoptionSeminar extends BaseModel
{
    protected static string $table = 'adoption_seminars';
    protected static bool $useSoftDeletes = false; // Currently not using is_deleted in this table

    public function list(array $filters = []): array
    {
        $clauses = ['1 = 1'];
        $bindings = [];

        if (($filters['status'] ?? '') !== '') {
            $clauses[] = 's.status = :status';
            $bindings['status'] = $filters['status'];
        }

        return $this->db->fetchAll(
            "SELECT s.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS facilitator_name,
                    COUNT(sa.id) AS attendee_count
             FROM adoption_seminars s
             LEFT JOIN users u ON u.id = s.facilitator_id
             LEFT JOIN seminar_attendees sa ON sa.seminar_id = s.id
             WHERE " . implode(' AND ', $clauses) . "
             GROUP BY s.id
             ORDER BY s.scheduled_date DESC, s.id DESC",
            $bindings
        );
    }

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
            "SELECT s.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS facilitator_name,
                    COUNT(sa.id) AS attendee_count
             FROM adoption_seminars s
             LEFT JOIN users u ON u.id = s.facilitator_id
             LEFT JOIN seminar_attendees sa ON sa.seminar_id = s.id
             WHERE s.id = :id
             GROUP BY s.id
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function attendees(int $seminarId): array
    {
        return $this->db->fetchAll(
            'SELECT sa.*,
                    aa.application_number,
                    aa.status AS application_status,
                    CONCAT(u.first_name, " ", u.last_name) AS adopter_name,
                    a.animal_code AS animal_code,
                    a.name AS animal_name
             FROM seminar_attendees sa
             INNER JOIN adoption_applications aa ON aa.id = sa.application_id
             INNER JOIN users u ON u.id = aa.adopter_id
             LEFT JOIN animals a ON a.id = aa.animal_id
             WHERE sa.seminar_id = :seminar_id
             ORDER BY sa.created_at ASC, sa.id ASC',
            ['seminar_id' => $seminarId]
        );
    }

    public function listByApplication(int $applicationId): array
    {
        return $this->db->fetchAll(
            'SELECT s.*,
                    sa.id AS attendee_id,
                    sa.attendance_status,
                    sa.marked_at,
                    COUNT(all_sa.id) AS attendee_count
             FROM seminar_attendees sa
             INNER JOIN adoption_seminars s ON s.id = sa.seminar_id
             LEFT JOIN seminar_attendees all_sa ON all_sa.seminar_id = s.id
             WHERE sa.application_id = :application_id
             GROUP BY s.id, sa.id
             ORDER BY s.scheduled_date DESC, s.id DESC',
            ['application_id' => $applicationId]
        );
    }

    public function attendee(int $seminarId, int $applicationId): array|false
    {
        return $this->db->fetch(
            'SELECT *
             FROM seminar_attendees
             WHERE seminar_id = :seminar_id
               AND application_id = :application_id
             LIMIT 1',
            [
                'seminar_id' => $seminarId,
                'application_id' => $applicationId,
            ]
        );
    }

    public function addAttendee(int $seminarId, int $applicationId): int
    {
        return (int) $this->db->execute(
            'INSERT INTO seminar_attendees (seminar_id, application_id, attendance_status)
             VALUES (:seminar_id, :application_id, :attendance_status)',
            [
                'seminar_id' => $seminarId,
                'application_id' => $applicationId,
                'attendance_status' => 'registered',
            ]
        );
    }

    public function updateAttendance(int $seminarId, int $applicationId, string $attendanceStatus, ?int $markedBy): void
    {
        $this->db->execute(
            'UPDATE seminar_attendees
             SET attendance_status = :attendance_status,
                 marked_by = :marked_by,
                 marked_at = NOW()
             WHERE seminar_id = :seminar_id
               AND application_id = :application_id',
            [
                'seminar_id' => $seminarId,
                'application_id' => $applicationId,
                'attendance_status' => $attendanceStatus,
                'marked_by' => $markedBy,
            ]
        );
    }
}
