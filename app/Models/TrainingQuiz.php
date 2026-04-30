<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class TrainingQuiz extends Model
{
    protected string $table      = 'training_quizzes';
    protected string $primaryKey = 'quiz_id';

    // ─────────────────────────────────────────────────────────────────────────
    // training_quizzes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch all active quizzes with training session name and assigned question count.
     */
    public function getAllActive(): array
    {
        $sql = "
            SELECT
                tq.quiz_id,
                tq.training_id,
           
                tq.title,
                tq.time_limit,
                tq.total_marks,
                tq.is_active,
                tq.created_at,
                tq.updated_at,
                ud.user_id      AS created_by_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS created_by_name,
                COUNT(qqa.question_id) AS assigned_questions
            FROM {$this->table} tq
            LEFT JOIN training_session ts  ON ts.category_id  = tq.category_id
            LEFT JOIN user_details      ud  ON ud.user_id      = tq.created_by
            LEFT JOIN quiz_question_allocations qqa
                   ON qqa.quiz_id   = tq.quiz_id
                  AND qqa.is_active = 1
            WHERE tq.is_active = 1
              AND tq.is_delete  = 0
            GROUP BY tq.quiz_id
            ORDER BY tq.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Find a single quiz by primary key with its allocated questions.
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                tq.quiz_id,
                tq.training_id,
                
                tq.title,
                tq.time_limit,
                tq.total_marks,
                tq.is_active,
                tq.is_delete,
                tq.created_at,
                tq.updated_at,
                ud.user_id      AS created_by_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS created_by_name
            FROM {$this->table} tq
            LEFT JOIN training_sessions ts ON ts.training_id = tq.training_id
            LEFT JOIN user_details      ud ON ud.user_id     = tq.created_by
            WHERE tq.quiz_id  = :id
              AND tq.is_delete = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        $result['questions'] = $this->getAllocatedQuestions((int) $result['quiz_id']);

        return $result;
    }

    /**
     * Soft delete a quiz.
     */
    public function softDelete(int $id): bool
    {
        $sql  = "UPDATE {$this->table} SET is_delete = 1, updated_at = :updated_at WHERE quiz_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update an existing quiz.
     */
    public function updateQuiz(int $id, array $data): bool
    {
        $allowedFields = [
            'training_id',
            'title',
            'time_limit',
            'total_marks',
            'is_active',
            'updated_at',
            'updated_by',
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue;
            }
            $fields[]        = "$key = :$key";
            $params[":$key"] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "
            UPDATE {$this->table}
            SET " . implode(', ', $fields) . "
            WHERE {$this->primaryKey} = :id
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // quiz_question_allocations
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Allocate a single question to a quiz.
     * Ignores duplicate (INSERT IGNORE) — safe to call in a loop.
     */
    public function allocateQuestion(int $quizId, int $questionId): bool
    {
        $sql = "
            INSERT IGNORE INTO quiz_question_allocations
                (quiz_id, question_id, is_active, created_at, updated_at)
            VALUES
                (:quiz_id, :question_id, 1, :created_at, :updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':quiz_id'     => $quizId,
            ':question_id' => $questionId,
            ':created_at'  => date('Y-m-d H:i:s'),
            ':updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove a single question allocation from a quiz.
     */
    public function removeQuestion(int $quizId, int $questionId): bool
    {
        $sql  = "DELETE FROM quiz_question_allocations WHERE quiz_id = :quiz_id AND question_id = :question_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':quiz_id'     => $quizId,
            ':question_id' => $questionId,
        ]);
    }

    /**
     * Replace all allocations for a quiz (delete then re-insert).
     */
    public function syncQuestions(int $quizId, array $questionIds): bool
    {
        // Delete existing
        $deleteSql  = "DELETE FROM quiz_question_allocations WHERE quiz_id = :quiz_id";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->execute([':quiz_id' => $quizId]);

        if (empty($questionIds)) {
            return true;
        }

        // Re-insert
        foreach ($questionIds as $questionId) {
            $this->allocateQuestion($quizId, (int) $questionId);
        }

        return true;
    }

    /**
     * Fetch all active allocated questions for a quiz with full question details.
     */
    public function getAllocatedQuestions(int $quizId): array
    {
        $sql = "
            SELECT
                qq.question_id,
                qq.question_text,
                qq.question_type,
                qq.marks,
                qq.order_no,
                qqa.is_active
            FROM quiz_question_allocations qqa
            INNER JOIN quiez_questions qq
                    ON qq.question_id = qqa.question_id
                   AND qq.is_delete   = 0
            WHERE qqa.quiz_id   = :quiz_id
              AND qqa.is_active = 1
            ORDER BY qq.order_no ASC, qq.question_id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':quiz_id' => $quizId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Recalculate and update total_marks from allocated questions.
     */
    public function recalculateTotalMarks(int $quizId): bool
    {
        $sql = "
            UPDATE {$this->table} tq
            SET tq.total_marks = (
                SELECT COALESCE(SUM(qq.marks), 0)
                FROM quiz_question_allocations qqa
                INNER JOIN quiez_questions qq ON qq.question_id = qqa.question_id
                WHERE qqa.quiz_id   = :quiz_id
                  AND qqa.is_active = 1
                  AND qq.is_delete  = 0
            ),
            tq.updated_at = :updated_at
            WHERE tq.quiz_id = :quiz_id2
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':quiz_id'     => $quizId,
            ':updated_at'  => date('Y-m-d H:i:s'),
            ':quiz_id2'    => $quizId,
        ]);
    }
}
