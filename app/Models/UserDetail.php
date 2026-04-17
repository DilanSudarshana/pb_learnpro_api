<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Represents the user_details table
 *
 * Columns:
 *   user_id, first_name, last_name, phone_no, nic, dob, address,
 *   gender, marital_status, blood_group, role_id, department_id,
 *   branch_id, employment_type, date_joined, probation_end_date,
 *   date_left, basic_salary, bank_account_number, tax_id, epf_no,
 *   manager_id, emergency_contact_name, emergency_contact_relationship,
 *   emergency_contact_phone, additional_details, pro_pic,
 *   is_active, is_delete, is_online, createdAt, updatedAt
 */
class UserDetail extends Model
{
    protected string $table      = 'user_details';
    protected string $primaryKey = 'user_id';

    // ── Allowed detail fields (everything except auto-managed ones) ──────
    private const ALLOWED_FIELDS = [
        'first_name',
        'last_name',
        'phone_no',
        'nic',
        'dob',
        'address',
        'gender',
        'marital_status',
        'blood_group',
        'role_id',
        'department_id',
        'branch_id',
        'employment_type',
        'date_joined',
        'probation_end_date',
        'date_left',
        'basic_salary',
        'bank_account_number',
        'tax_id',
        'epf_no',
        'manager_id',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'additional_details',
        'pro_pic',
    ];

    /**
     * Create a new user_details record linked to a user_main.
     * Only allowed fields are inserted — auto fields are set here.
     */
    public function createUserDetail(int $userId, array $data): int
    {
        // Strip unknown / auto-managed keys
        $filtered = array_intersect_key($data, array_flip(self::ALLOWED_FIELDS));

        $filtered['user_id']   = $userId;
        $filtered['is_active'] = $data['is_active'] ?? 1;
        $filtered['is_delete'] = 0;
        $filtered['is_online'] = 0;
        $filtered['createdAt'] = date('Y-m-d H:i:s');
        $filtered['updatedAt'] = date('Y-m-d H:i:s');

        return $this->create($filtered);
    }

    /**
     * Find user detail record by user_id
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->findBy('user_id', $userId);
    }
}
