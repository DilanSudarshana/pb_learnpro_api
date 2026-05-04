<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class DepartmentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance();
    }

    /**
     * Get all non-deleted departments
     * 
     * @return array
     */
    public function getAllDepartments(): array
    {
        $query = "SELECT 
                    dep_id,
                    dep_name,
                    branch_id,
                    contact_number,
                    email,
                    fax,
                    is_active,
                    is_delete,
                    created_at,
                    updated_at
                  FROM departments
                  WHERE is_delete = 0
                  ORDER BY dep_name ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get a single department by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getDepartmentById(int $id): ?array
    {
        $query = "SELECT 
                    dep_id,
                    dep_name,
                    branch_id,
                    contact_number,
                    email,
                    fax,
                    is_active,
                    is_delete,
                    created_at,
                    updated_at
                  FROM departments
                  WHERE dep_id = ? AND is_delete = 0
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}
