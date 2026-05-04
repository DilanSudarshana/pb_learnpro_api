<?php

declare(strict_types=1);

namespace App\Models;

class BranchModel
{
    private $db;

    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance();
    }

    /**
     * Get all non-deleted branches
     * 
     * @return array
     */
    public function getAllBranches(): array
    {
        $query = "SELECT 
                    branch_id,
                    branch_name,
                    location,
                    contact_number,
                    email,
                    fax,
                    is_active,
                    is_delete,
                    created_at,
                    updated_at
                  FROM branches
                  WHERE is_delete = 0
                  ORDER BY branch_name ASC";

        $stmt = $this->db->query($query);
        if ($stmt === false) {
            return [];
        }

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows === false ? [] : $rows;
    }

    /**
     * Get a single branch by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getBranchById(int $id): ?array
    {
        $query = "SELECT 
                    branch_id,
                    branch_name,
                    location,
                    contact_number,
                    email,
                    fax,
                    is_active,
                    is_delete,
                    created_at,
                    updated_at
                  FROM branches
                  WHERE branch_id = ? AND is_delete = 0
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        if ($stmt === false) {
            return null;
        }

        if (!$stmt->execute([$id])) {
            return null;
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }
}
