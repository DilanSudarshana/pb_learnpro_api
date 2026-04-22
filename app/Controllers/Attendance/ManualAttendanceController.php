<?php

declare(strict_types=1);

namespace App\Controllers\Attendance;

use App\Core\Controller;
use App\Models\TrainingAttendance;
use App\Utils\JwtHelper;

/**
 * ManualAttendanceController — CRUD for training_attendance (Manual Attendance Management)
 * Handles creation, retrieval, updating, and deletion of training attendance records
 */
class ManualAttendanceController extends Controller
{
    private TrainingAttendance $model;

    public function __construct()
    {
        $this->model = new TrainingAttendance();
    }

    /**
     * GET /api/manual-attendance
     * Retrieve all attendance records with optional filters
     * Query params: training_allocation_id, user_id, status, attendance_date_from, attendance_date_to
     */
    public function index(): void
    {
        $query = $_GET ?? [];

        $filters = [];

        if (!empty($query['training_allocation_id'])) {
            $filters['training_allocation_id'] = (int) $query['training_allocation_id'];
        }

        if (!empty($query['user_id'])) {
            $filters['user_id'] = (int) $query['user_id'];
        }

        if (!empty($query['status'])) {
            $filters['status'] = (int) $query['status'];
        }

        if (!empty($query['attendance_date_from'])) {
            $filters['attendance_date_from'] = $query['attendance_date_from'];
        }

        if (!empty($query['attendance_date_to'])) {
            $filters['attendance_date_to'] = $query['attendance_date_to'];
        }

        $records = $this->model->getAllWithFilters($filters);

        $this->json([
            'message' => 'Training attendance records retrieved',
            'data'    => $records,
            'count'   => count($records),
        ]);
    }

    /**
     * GET /api/manual-attendance/{id}
     * Retrieve a single attendance record by ID
     */
    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        $record = $this->model->find($id);

        if (!$record) {
            $this->json(['message' => 'Attendance record not found'], 404);
            return;
        }

        $this->json([
            'message' => 'Attendance record retrieved',
            'data'    => $record,
        ]);
    }

    /**
     * POST /api/manual-attendance
     * Create a new manual attendance record
     * Body: {
     *   training_allocation_id,
     *   user_id,
     *   attendance_date,
     *   in_time (optional),
     *   out_time (optional),
     *   attendance_type (optional),
     *   status (0=pending, 1=present, 2=absent, 3=late, 4=half-day, 5=leave),
     *   is_marked (optional, default 0),
     *   is_late (optional, default 0),
     *   is_early_leave (optional, default 0)
     * }
     */
    public function store(): void
    {
        $body = $this->getBody();

        // Validate required fields
        $trainingAllocationId = (int) ($body['training_allocation_id'] ?? 0);
        $userId               = (int) ($body['user_id'] ?? 0);
        $attendanceDate       = trim($body['attendance_date'] ?? '');

        if ($trainingAllocationId <= 0) {
            $this->json(['message' => 'training_allocation_id is required'], 400);
            return;
        }

        if ($userId <= 0) {
            $this->json(['message' => 'user_id is required'], 400);
            return;
        }

        if (empty($attendanceDate) || !$this->isValidDate($attendanceDate)) {
            $this->json(['message' => 'Valid attendance_date is required (YYYY-MM-DD)'], 400);
            return;
        }

        // Check if record already exists for this allocation and user on this date
        $existingRecord = $this->model->findByAllocationUserDate($trainingAllocationId, $userId, $attendanceDate);
        if ($existingRecord) {
            $this->json(['message' => 'Attendance record already exists for this date'], 409);
            return;
        }

        // Get authenticated user
        $authUser = JwtHelper::getAuthUserFromRequest();
        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        // Prepare data
        $data = [
            'training_allocation_id' => $trainingAllocationId,
            'user_id'                => $userId,
            'attendance_date'        => $attendanceDate,
            'in_time'                => !empty($body['in_time']) ? $body['in_time'] : null,
            'out_time'               => !empty($body['out_time']) ? $body['out_time'] : null,
            'attendance_type'        => !empty($body['attendance_type']) ? trim($body['attendance_type']) : null,
            'status'                 => isset($body['status']) ? (int) $body['status'] : 0,
            'is_marked'              => isset($body['is_marked']) ? (int) $body['is_marked'] : 0,
            'is_late'                => isset($body['is_late']) ? (int) $body['is_late'] : 0,
            'is_early_leave'         => isset($body['is_early_leave']) ? (int) $body['is_early_leave'] : 0,
            'created_by'             => $authUser['user_id'],
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ];

        $id = $this->model->create($data);

        $this->json([
            'message'     => 'Attendance record created successfully',
            'attendance_id' => $id,
        ], 201);
    }

    /**
     * PUT /api/manual-attendance/{id}
     * Update an existing attendance record
     * Body: {
     *   in_time?,
     *   out_time?,
     *   attendance_type?,
     *   status?,
     *   is_marked?,
     *   is_late?,
     *   is_early_leave?
     * }
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid attendance ID'], 400);
            return;
        }

        $record = $this->model->find($id);

        if (!$record) {
            $this->json(['message' => 'Attendance record not found'], 404);
            return;
        }

        $body = $this->getBody();

        $allowedFields = [
            'in_time',
            'out_time',
            'attendance_type',
            'status',
            'is_marked',
            'is_late',
            'is_early_leave',
        ];

        $data = [];

        // Whitelist input fields
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $body)) {
                if (in_array($field, ['status', 'is_marked', 'is_late', 'is_early_leave'], true)) {
                    $data[$field] = (int) $body[$field];
                } else {
                    $data[$field] = $body[$field];
                }
            }
        }

        if (empty($data)) {
            $this->json(['message' => 'No updatable fields provided'], 400);
            return;
        }

        // Get authenticated user
        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $authUser['user_id'];

        $updated = $this->model->updateAttendance($id, $data);

        if (!$updated) {
            $this->json(['message' => 'Failed to update attendance record'], 500);
            return;
        }

        $this->json(['message' => 'Attendance record updated successfully']);
    }

    /**
     * PUT /api/manual-attendance/{id}/mark-status
     * Quick update attendance status
     * Body: { status }
     */
    public function markStatus(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid attendance ID'], 400);
            return;
        }

        $record = $this->model->find($id);

        if (!$record) {
            $this->json(['message' => 'Attendance record not found'], 404);
            return;
        }

        $body   = $this->getBody();
        $status = isset($body['status']) ? (int) $body['status'] : null;

        if ($status === null || $status < 0 || $status > 5) {
            $this->json([
                'message' => 'Invalid status. Allowed values: 0=pending, 1=present, 2=absent, 3=late, 4=half-day, 5=leave',
            ], 400);
            return;
        }

        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        $updated = $this->model->updateAttendance($id, [
            'status'     => $status,
            'is_marked'  => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $authUser['user_id'],
        ]);

        if (!$updated) {
            $this->json(['message' => 'Failed to mark attendance'], 500);
            return;
        }

        $statusLabels = [
            0 => 'pending',
            1 => 'present',
            2 => 'absent',
            3 => 'late',
            4 => 'half-day',
            5 => 'leave',
        ];

        $this->json([
            'message' => 'Attendance marked as ' . $statusLabels[$status],
            'status'  => $status,
            'is_marked' => 1,
        ]);
    }

    /**
     * DELETE /api/manual-attendance/{id}
     * Delete an attendance record (hard delete)
     * Note: Consider soft delete if audit trail is needed
     */
    public function destroy(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid attendance ID'], 400);
            return;
        }

        $record = $this->model->find($id);

        if (!$record) {
            $this->json(['message' => 'Attendance record not found'], 404);
            return;
        }

        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        $updated = $this->model->softDelete($id, (int) $authUser['user_id']);

        if (!$updated) {
            $this->json(['message' => 'Failed to delete attendance record'], 500);
            return;
        }

        $this->json(['message' => 'Attendance record deleted successfully']);
    }

    /**
     * GET /api/manual-attendance/allocation/{allocation_id}/summary
     * Get attendance summary for a training allocation
     */
    public function allocationSummary(array $params): void
    {
        $allocationId = (int) ($params['allocation_id'] ?? 0);

        if ($allocationId <= 0) {
            $this->json(['message' => 'Invalid allocation ID'], 400);
            return;
        }

        $summary = $this->model->getAttendanceSummary($allocationId);

        $this->json([
            'message'      => 'Attendance summary retrieved',
            'allocation_id' => $allocationId,
            'data'         => $summary,
        ]);
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
