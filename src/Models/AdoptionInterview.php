<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AdoptionInterview
{
    public function find(int $id): array|false
    {
        return Database::fetch(
            'SELECT ai.*,
                    CONCAT(u.first_name, " ", u.last_name) AS conducted_by_name
             FROM adoption_interviews ai
             LEFT JOIN users u ON u.id = ai.conducted_by
             WHERE ai.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function listByApplication(int $applicationId): array
    {
        return Database::fetchAll(
            'SELECT ai.*,
                    CONCAT(u.first_name, " ", u.last_name) AS conducted_by_name
             FROM adoption_interviews ai
             LEFT JOIN users u ON u.id = ai.conducted_by
             WHERE ai.application_id = :application_id
             ORDER BY ai.scheduled_date DESC, ai.id DESC',
            ['application_id' => $applicationId]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO adoption_interviews (
                application_id, scheduled_date, interview_type, video_call_link, location, status,
                screening_checklist, home_assessment_notes, pet_care_knowledge_score,
                overall_recommendation, interviewer_notes, conducted_by, completed_at
             ) VALUES (
                :application_id, :scheduled_date, :interview_type, :video_call_link, :location, :status,
                :screening_checklist, :home_assessment_notes, :pet_care_knowledge_score,
                :overall_recommendation, :interviewer_notes, :conducted_by, :completed_at
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        Database::execute(
            'UPDATE adoption_interviews
             SET scheduled_date = :scheduled_date,
                 interview_type = :interview_type,
                 video_call_link = :video_call_link,
                 location = :location,
                 status = :status,
                 screening_checklist = :screening_checklist,
                 home_assessment_notes = :home_assessment_notes,
                 pet_care_knowledge_score = :pet_care_knowledge_score,
                 overall_recommendation = :overall_recommendation,
                 interviewer_notes = :interviewer_notes,
                 conducted_by = :conducted_by,
                 completed_at = :completed_at
             WHERE id = :id',
            $data
        );
    }
}
