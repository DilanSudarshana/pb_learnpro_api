<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class QuizQuestion extends Model
{
    protected string $table      = 'quiez_questions';
    protected string $primaryKey = 'question_id';

    // ─────────────────────────────────────────────────────────────────────────
    // quiez_questions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch all active quiz questions with their answers.
     */
    public function getAllActive(): array
    {
        $sql = "
            SELECT
                qq.question_id,
                qq.question_text,
                qq.question_type,
                qq.marks,
                qq.order_no,
                qq.is_active,
                qq.created_at,
                qq.updated_at,
                ud.user_id AS created_by_id,
                CONCAT(ud.first_name, ' ', ud.last_name) AS created_by_name
            FROM {$this->table} qq
            LEFT JOIN user_details ud ON ud.user_id = qq.created_by
            WHERE qq.is_active = 1
              AND qq.is_delete = 0
            ORDER BY qq.order_no ASC, qq.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $questions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Attach answers to each question
        foreach ($questions as &$question) {
            $question['answers'] = $this->getAnswers(
                (int) $question['question_id'],
                $question['question_type']
            );
        }

        return $questions;
    }

    /**
     * Find a single quiz question by primary key with its answers.
     */
    public function find($id): ?array
    {
        $sql = "
            SELECT
                qq.question_id,
                qq.question_text,
                qq.question_type,
                qq.marks,
                qq.order_no,
                qq.is_active,
                qq.is_delete,
                qq.created_at,
                qq.updated_at,
                ud.user_id AS created_by_id,
                TRIM(CONCAT(ud.first_name, ' ', ud.last_name)) AS created_by_name
            FROM {$this->table} qq
            LEFT JOIN user_details ud ON ud.user_id = qq.created_by
            WHERE qq.question_id = :id
              AND qq.is_delete = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        $result['answers'] = $this->getAnswers(
            (int) $result['question_id'],
            $result['question_type']
        );

        return $result;
    }

    /**
     * Soft delete a quiz question by setting is_delete = 1.
     */
    public function softDelete(int $id): bool
    {
        $sql  = "UPDATE {$this->table} SET is_delete = 1, updated_at = :updated_at WHERE question_id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update an existing quiz question.
     */
    public function updateQuestion(int $id, array $data): bool
    {
        $allowedFields = [
            'question_text',
            'question_type',
            'marks',
            'order_no',
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
    // quiez_question_options  (MCQ + True/False)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Insert a single option row (MCQ or True/False).
     */
    public function createOption(array $data): int
    {
        $sql = "
            INSERT INTO quiez_question_options
                (question_id, option_label, option_text, is_correct, order_no,
                 created_by, created_at, updated_at)
            VALUES
                (:question_id, :option_label, :option_text, :is_correct, :order_no,
                 :created_by, :created_at, :updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':question_id'  => $data['question_id'],
            ':option_label' => $data['option_label'] ?? null,
            ':option_text'  => $data['option_text'],
            ':is_correct'   => $data['is_correct']   ?? 0,
            ':order_no'     => $data['order_no']      ?? 0,
            ':created_by'   => $data['created_by']    ?? null,
            ':created_at'   => $data['created_at']    ?? date('Y-m-d H:i:s'),
            ':updated_at'   => $data['updated_at']    ?? date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Delete all options for a question (used before re-inserting on update).
     */
    public function deleteOptions(int $questionId): bool
    {
        $sql  = "DELETE FROM quiez_question_options WHERE question_id = :question_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':question_id' => $questionId]);
    }

    /**
     * Fetch all active options for a question.
     */
    public function getOptions(int $questionId): array
    {
        $sql = "
            SELECT
                option_id,
                option_label,
                option_text,
                is_correct,
                order_no
            FROM quiez_question_options
            WHERE question_id = :question_id
              AND is_active    = 1
              AND is_delete    = 0
            ORDER BY order_no ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':question_id' => $questionId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // quiez_question_short_answers  (Short Answer)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Insert or update the expected answer for a Short Answer question.
     * Uses ON DUPLICATE KEY because question_id is UNIQUE in this table.
     */
    public function saveShortAnswer(array $data): bool
    {
        $sql = "
            INSERT INTO quiez_question_short_answers
                (question_id, expected_answer, created_by, created_at, updated_at)
            VALUES
                (:question_id, :expected_answer, :created_by, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                expected_answer = VALUES(expected_answer),
                updated_at      = VALUES(updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':question_id'     => $data['question_id'],
            ':expected_answer' => $data['expected_answer'] ?? null,
            ':created_by'      => $data['created_by']      ?? null,
            ':created_at'      => $data['created_at']      ?? date('Y-m-d H:i:s'),
            ':updated_at'      => $data['updated_at']      ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Fetch the expected answer for a Short Answer question.
     */
    public function getShortAnswer(int $questionId): ?array
    {
        $sql = "
            SELECT
                short_answer_id,
                expected_answer
            FROM quiez_question_short_answers
            WHERE question_id = :question_id
              AND is_active    = 1
              AND is_delete    = 0
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':question_id' => $questionId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return the correct answer block based on question_type.
     */
    public function getAnswers(int $questionId, string $questionType): array|null
    {
        return match ($questionType) {
            'MCQ', 'True/False' => $this->getOptions($questionId),
            'Short Answer'      => $this->getShortAnswer($questionId),
            default             => null,
        };
    }
}
