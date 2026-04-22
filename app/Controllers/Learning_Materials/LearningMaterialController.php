<?php

declare(strict_types=1);

namespace App\Controllers\Learning_Materials;

use App\Core\Controller;
use App\Models\LearningMaterial;
use App\Utils\JwtHelper;

/**
 * LearningMaterialController — CRUD + file upload / download
 *
 * UPLOAD_BASE_PATH in .env should point to the learning_materials folder:
 *   C:\xampp\htdocs\learnpro-api\public\uploads\learning_materials
 *
 * Physical file layout  : {UPLOAD_BASE_PATH}\{training_id}\{filename}
 * DB file_path stored as: {training_id}/{filename}   ← relative, forward-slash
 *
 * Example:
 *   - .env: UPLOAD_BASE_PATH=C:/xampp/htdocs/learnpro-api/public/uploads/learning_materials
 *   - DB file_path: 1/test_abc123.pdf
 *   - Physical path: C:\xampp\htdocs\learnpro-api\public\uploads\learning_materials\1\test_abc123.pdf
 */
class LearningMaterialController extends Controller
{
    private LearningMaterial $model;

    /**
     * Absolute path to uploads root — taken directly from UPLOAD_BASE_PATH.
     * No trailing slash / backslash.
     */
    private string $uploadBasePath;

    /** Allowed MIME types mapped to their canonical material_type label. */
    private array $allowedMimeTypes = [
        'application/pdf'                                                           => 'pdf',
        'application/msword'                                                        => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/vnd.ms-excel'                                                  => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'image/jpeg'                                                                => 'image',
        'image/png'                                                                 => 'image',
        'image/gif'                                                                 => 'image',
        'image/webp'                                                                => 'image',
        'video/mp4'                                                                 => 'video',
        'video/webm'                                                                => 'video',
        'video/avi'                                                                 => 'video',
        'video/quicktime'                                                           => 'video',
        'audio/mpeg'                                                                => 'audio',
        'audio/wav'                                                                 => 'audio',
        'text/plain'                                                                => 'text',
        'application/zip'                                                           => 'zip',
        'application/x-zip-compressed'                                              => 'zip',
    ];

    /** Maximum upload size in bytes (default 50 MB). */
    private int $maxFileSize = 52_428_800;

    public function __construct()
    {
        $this->model = new LearningMaterial();

        // Get UPLOAD_BASE_PATH from .env and strip any trailing slash/backslash
        $this->uploadBasePath = rtrim(
            $_ENV['UPLOAD_BASE_PATH'] ?? 'C:/xampp/htdocs/learnpro-api/public/uploads/learning_materials',
            '/\\'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Convert the relative DB path to an absolute filesystem path.
     *
     * DB stores  :  {training_id}/{filename}  (e.g., "1/test.pdf")
     * Returns    :  C:\...\learning_materials\{training_id}\{filename}
     *
     * Example:
     *   Input:  "1/test.pdf"
     *   Output: "C:\xampp\htdocs\learnpro-api\public\uploads\learning_materials\1\test.pdf"
     */
    private function absolutePath(string $relPath): string
    {
        // Convert forward slashes to the OS-appropriate directory separator
        // Remove any leading slashes from the relative path
        $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, ltrim($relPath, '/\\'));

        return $this->uploadBasePath . DIRECTORY_SEPARATOR . $normalizedPath;
    }

    /**
     * Build the relative path to persist in the DB.
     *
     * Always uses forward-slash regardless of OS:
     *   {training_id}/{filename}
     *
     * Example: relativePath(1, "test.pdf") → "1/test.pdf"
     */
    private function relativePath(int $trainingId, string $fileName): string
    {
        return $trainingId . '/' . $fileName;
    }

    /**
     * Absolute path to the per-training upload directory.
     *
     * Example: C:\...\learning_materials\{training_id}
     */
    private function destDir(int $trainingId): string
    {
        return $this->uploadBasePath . DIRECTORY_SEPARATOR . $trainingId;
    }

    /**
     * Validate size + MIME, generate a unique filename, and move the uploaded
     * file into $destDir.
     *
     * Returns the unique filename on success.
     * Returns null and sends a JSON error response on failure — caller must return immediately.
     */
    private function processUploadedFile(array $file, string $destDir): ?string
    {
        // Size check
        if ($file['size'] > $this->maxFileSize) {
            $maxMb = $this->maxFileSize / 1_048_576;
            $this->json(['message' => "File exceeds maximum allowed size of {$maxMb} MB"], 400);
            return null;
        }

        // MIME check
        $mime = mime_content_type($file['tmp_name']) ?: $file['type'];
        if (!array_key_exists($mime, $this->allowedMimeTypes)) {
            $this->json(['message' => 'File type not allowed'], 415);
            return null;
        }

        // Build unique filename
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        $extension    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeBaseName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);
        $uniqueName   = $safeBaseName . '_' . uniqid('', true) . '.' . $extension;

        // Ensure directory exists
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            $this->json(['message' => 'Failed to create upload directory'], 500);
            return null;
        }

        // Move temp file to destination
        $destPath = $destDir . DIRECTORY_SEPARATOR . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->json(['message' => 'Failed to save uploaded file'], 500);
            return null;
        }

        return $uniqueName;
    }

    /**
     * Map PHP upload error codes to human-readable messages.
     */
    private function resolveUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds the maximum allowed size',
            UPLOAD_ERR_PARTIAL                        => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE                        => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR                     => 'Missing temporary folder on server',
            UPLOAD_ERR_CANT_WRITE                     => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION                      => 'A PHP extension blocked the upload',
            default                                   => 'An unknown upload error occurred',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // READ
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/learning-materials
     */
    public function index(): void
    {
        $materials = $this->model->getAllActive();
        $this->json(['message' => 'Learning materials retrieved', 'data' => $materials]);
    }

    /**
     * GET /api/learning-materials/{id}
     */
    public function show(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $material = $this->model->find($id);

        if (!$material) {
            $this->json(['message' => 'Learning material not found'], 404);
            return;
        }

        $this->json(['message' => 'Learning material retrieved', 'data' => $material]);
    }

    /**
     * GET /api/learning-materials/training/{training_id}
     * Returns all materials belonging to a specific training session.
     */
    public function byTraining(array $params): void
    {
        $trainingId = (int) ($params['training_id'] ?? 0);

        if ($trainingId <= 0) {
            $this->json(['message' => 'Invalid training ID'], 400);
            return;
        }

        $materials = $this->model->getByTraining($trainingId);
        $this->json(['message' => 'Training materials retrieved', 'data' => $materials]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPLOAD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/learning-materials/upload
     *
     * Expects multipart/form-data:
     *   - file               (required) binary
     *   - training_id        (required) target session ID
     *   - uploaded_by        (required) user_id
     *   - additional_details (optional)
     *
     * File lands at:
     *   C:\...\learning_materials\{training_id}\{unique_filename}
     *
     * DB file_path:
     *   {training_id}/{unique_filename}
     */
    public function upload(): void
    {
        // 1. File present and error-free?
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $this->json(['message' => $this->resolveUploadError($code)], 400);
            return;
        }

        // 2. Required POST fields
        $trainingId        = (int) ($_POST['training_id']      ?? 0);
        $uploadedBy        = (int) ($_POST['uploaded_by']      ?? 0);
        $additionalDetails = trim($_POST['additional_details'] ?? '');

        if ($trainingId <= 0) {
            $this->json(['message' => 'training_id is required'], 400);
            return;
        }

        if ($uploadedBy <= 0) {
            $this->json(['message' => 'uploaded_by (user_id) is required'], 400);
            return;
        }

        // 3. Validate + move file into {UPLOAD_BASE_PATH}\{training_id}\
        $destDir    = $this->destDir($trainingId);
        $uniqueName = $this->processUploadedFile($_FILES['file'], $destDir);

        if ($uniqueName === null) {
            return; // processUploadedFile already sent JSON error
        }

        // 4. Resolve material_type from the saved file (more reliable than tmp)
        $savedPath    = $destDir . DIRECTORY_SEPARATOR . $uniqueName;
        $mime         = mime_content_type($savedPath) ?: $_FILES['file']['type'];
        $materialType = $this->allowedMimeTypes[$mime] ?? 'file';

        // 5. Persist to DB — store relative path with forward-slashes
        $relPath = $this->relativePath($trainingId, $uniqueName);

        $id = $this->model->create([
            'training_id'        => $trainingId,
            'material_type'      => $materialType,
            'file_name'          => $uniqueName,
            'file_path'          => $relPath,
            'additional_details' => $additionalDetails ?: null,
            'uploaded_by'        => $uploadedBy,
            'is_active'          => 1,
            'is_delete'          => 0,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'       => 'File uploaded successfully',
            'material_id'   => $id,
            'file_name'     => $uniqueName,
            'file_path'     => $relPath,
            'material_type' => $materialType,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOWNLOAD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/learning-materials/{id}/download
     *
     * Resolves DB file_path → absolute path and streams the file as an
     * attachment (forces browser download).
     */
    public function download(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $material = $this->model->find($id);

        if (!$material) {
            $this->json(['message' => 'Learning material not found'], 404);
            return;
        }

        // DB: "1/test.pdf"
        // Abs: C:\...\learning_materials\1\test.pdf
        $absolutePath = $this->absolutePath($material['file_path']);

        if (!file_exists($absolutePath) || !is_readable($absolutePath)) {
            $this->json(['message' => 'File not found on server'], 404);
            return;
        }

        $mime     = mime_content_type($absolutePath) ?: 'application/octet-stream';
        $fileSize = filesize($absolutePath);
        $fileName = $material['file_name'];

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        readfile($absolutePath);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PREVIEW
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/learning-materials/{id}/preview
     *
     * Streams the file inline so the browser can render it (PDFs, images, etc.).
     */
    public function preview(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $material = $this->model->find($id);

        if (!$material) {
            $this->json(['message' => 'Learning material not found'], 404);
            return;
        }

        $absolutePath = $this->absolutePath($material['file_path']);

        if (!file_exists($absolutePath) || !is_readable($absolutePath)) {
            $this->json(['message' => 'File not found on server'], 404);
            return;
        }

        $mime     = mime_content_type($absolutePath) ?: 'application/octet-stream';
        $fileSize = filesize($absolutePath);
        $fileName = $material['file_name'];

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($fileName) . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: public, max-age=86400');

        readfile($absolutePath);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PUT /api/learning-materials/{id}
     *
     * Body (JSON or multipart): { additional_details?, is_active? }
     * Optionally include a new `file` field (multipart) to replace the file.
     * When a new file is provided the old physical file is deleted from disk.
     */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['message' => 'Invalid material ID'], 400);
            return;
        }

        $material = $this->model->find($id);

        if (!$material) {
            $this->json(['message' => 'Learning material not found'], 404);
            return;
        }

        $authUser = JwtHelper::getAuthUserFromRequest();

        if (!isset($authUser['user_id'])) {
            $this->json(['message' => 'Unauthorized user'], 401);
            return;
        }

        // Accept both JSON body and multipart form-data
        $body = !empty($_POST) ? $_POST : $this->getBody();
        $data = [];

        foreach (['additional_details', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }

        // ── Optional file replacement ──────────────────────────────────────
        if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {

            $trainingId = (int) $material['training_id'];
            $destDir    = $this->destDir($trainingId);
            $uniqueName = $this->processUploadedFile($_FILES['file'], $destDir);

            if ($uniqueName === null) {
                return; // processUploadedFile already sent JSON error
            }

            // Remove the old physical file
            $oldAbsPath = $this->absolutePath($material['file_path']);
            if (file_exists($oldAbsPath)) {
                @unlink($oldAbsPath);
            }

            // Resolve material_type from the newly saved file
            $newAbsPath   = $destDir . DIRECTORY_SEPARATOR . $uniqueName;
            $mime         = mime_content_type($newAbsPath) ?: $_FILES['file']['type'];
            $materialType = $this->allowedMimeTypes[$mime] ?? 'file';

            $data['file_name']     = $uniqueName;
            $data['file_path']     = $this->relativePath($trainingId, $uniqueName);
            $data['material_type'] = $materialType;
        }

        if (empty($data)) {
            $this->json(['message' => 'No updatable fields provided'], 400);
            return;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = $authUser['user_id'];

        $updated = $this->model->updateMaterial($id, $data);

        if (!$updated) {
            $this->json(['message' => 'Failed to update learning material'], 500);
            return;
        }

        $this->json(['message' => 'Learning material updated successfully']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TOGGLE STATUS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PUT /api/learning-materials/{id}/toggle-status
     */
    public function toggleStatus(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $material = $this->model->find($id);

        if (!$material) {
            $this->json(['message' => 'Learning material not found'], 404);
            return;
        }

        $newStatus = ((int) $material['is_active'] === 1) ? 0 : 1;

        $this->model->update($id, [
            'is_active'  => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'message'   => $newStatus ? 'Learning material activated' : 'Learning material deactivated',
            'is_active' => $newStatus,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * DELETE /api/learning-materials/{id}
     * Soft delete — sets is_delete = 1. Physical file is retained on disk.
     */
    public function destroy(array $params): void
    {
        $id       = (int) ($params['id'] ?? 0);
        $material = $this->model->find($id);

        if (!$material) {
            $this->json(['message' => 'Learning material not found'], 404);
            return;
        }

        $this->model->softDelete($id);

        $this->json(['message' => 'Learning material deleted']);
    }
}
