<?php
/** GuardReport — Upload Helper | File: helpers/UploadHelper.php */
class UploadHelper {
    private static array $allowedImages = ['jpg','jpeg','png','gif','webp'];
    private static array $allowedDocs   = ['pdf','doc','docx','xls','xlsx','txt'];
    private static int   $maxBytes      = 10485760; // 10 MB

    public static function uploadEvidence(array $file, int $incidentId): array {
        if ($file['error'] !== UPLOAD_ERR_OK)
            return ['success' => false, 'message' => self::uploadErrorMsg($file['error'])];
        if ($file['size'] > self::$maxBytes)
            return ['success' => false, 'message' => 'File too large (max 10 MB)'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array_merge(self::$allowedImages, self::$allowedDocs);
        if (!in_array($ext, $allowed))
            return ['success' => false, 'message' => "File type .$ext not allowed"];

        // Verify actual MIME vs extension
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $safeMimes = ['image/jpeg','image/png','image/gif','image/webp',
                      'application/pdf','application/msword',
                      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                      'application/vnd.ms-excel',
                      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                      'text/plain'];
        if (!in_array($mime, $safeMimes))
            return ['success' => false, 'message' => 'File content does not match its extension'];

        $dir = UPLOADS_PATH . '/evidence/' . $incidentId . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $unique   = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
        $fullPath = $dir . $unique;
        if (!move_uploaded_file($file['tmp_name'], $fullPath))
            return ['success' => false, 'message' => 'Failed to save file'];

        return [
            'success'   => true,
            'file_path' => 'evidence/' . $incidentId . '/' . $unique,
            'file_name' => $file['name'],
            'file_type' => $mime,
            'file_size' => $file['size'],
        ];
    }

    public static function uploadUserPhoto(array $file, int $userId): array {
        if ($file['error'] !== UPLOAD_ERR_OK)
            return ['success' => false, 'message' => self::uploadErrorMsg($file['error'])];
        if ($file['size'] > 2097152)
            return ['success' => false, 'message' => 'Photo too large (max 2 MB)'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedImages))
            return ['success' => false, 'message' => 'Photo must be jpg, png, gif, or webp'];

        $dir = UPLOADS_PATH . '/users/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $unique   = bin2hex(random_bytes(6)) . '_' . $userId . '.' . $ext;
        $fullPath = $dir . $unique;
        if (!move_uploaded_file($file['tmp_name'], $fullPath))
            return ['success' => false, 'message' => 'Failed to save photo'];
        return ['success' => true, 'file_path' => 'users/' . $unique];
    }

    public static function deleteFile(string $relativePath): bool {
        $full = UPLOADS_PATH . '/' . ltrim($relativePath, '/');
        return file_exists($full) && unlink($full);
    }

    public static function isImage(string $mimeOrExt): bool {
        return str_starts_with($mimeOrExt, 'image/') || in_array(strtolower($mimeOrExt), self::$allowedImages);
    }

    private static function uploadErrorMsg(int $code): string {
        return match($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds maximum upload size',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            default => 'Upload error (code ' . $code . ')',
        };
    }
}