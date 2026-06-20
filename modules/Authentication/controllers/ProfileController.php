<?php
/**
 * GuardReport — Profile Controller
 * File: modules/Authentication/controllers/ProfileController.php
 */

require_once dirname(__DIR__) . '/models/ProfileModel.php';
require_once ROOT_PATH . '/helpers/UploadHelper.php';

class ProfileController {
    private $profileModel;
    private $uploadHelper;

    public function __construct() {
        $this->profileModel = new ProfileModel();
        $this->uploadHelper = new UploadHelper();
    }

    public function getProfile($userId) {
        return $this->profileModel->getProfile($userId);
    }

    public function getSettings($userId) {
        return $this->profileModel->getUserSettings($userId);
    }

    public function getNotificationSettings($userId) {
        return $this->profileModel->getNotificationSettings($userId);
    }

    public function getActivityLog($userId, $limit = 50) {
        return $this->profileModel->getActivityLog($userId, $limit);
    }

    public function updateProfile($userId, $data, $files = null) {
        try {
            $currentUser = $this->profileModel->getProfile($userId);
            if (isset($data['email']) && $data['email'] !== $currentUser['email']) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format");
                }
                if ($this->profileModel->emailExists($data['email'], $userId)) {
                    throw new Exception("Email already in use");
                }
            }

            if ($files && isset($files['avatar']) && $files['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadHelper->uploadFile($files['avatar'], 'users');
                if ($uploadResult['success']) {
                    if (!empty($currentUser['photo'])) {
                        $this->uploadHelper->deleteFile($currentUser['photo']);
                    }
                    $data['photo'] = $uploadResult['filepath'];
                }
            }

            $result = $this->profileModel->updateProfile($userId, $data);

            $this->logActivity($userId, 'profile_update', 'Updated profile information');

            return [
                'success' => $result,
                'message' => $result ? 'Profile updated successfully' : 'No changes made'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateSettings($userId, $data) {
        try {
            $result = $this->profileModel->updateSettings($userId, $data);
            return [
                'success' => $result,
                'message' => $result ? 'Preferences updated successfully' : 'No changes made'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function changePassword($userId, $data) {
        try {
            if (empty($data['current_password']) || empty($data['new_password'])) {
                throw new Exception("Current password and new password are required");
            }

            if (strlen($data['new_password']) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }

            if ($data['new_password'] !== ($data['confirm_password'] ?? '')) {
                throw new Exception("New passwords do not match");
            }

            $user = $this->profileModel->getUserForPasswordCheck($userId);
            if (!password_verify($data['current_password'], $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $result = $this->profileModel->updatePassword($userId, $hashedPassword);

            if ($result) {
                $this->logActivity($userId, 'password_change', 'Changed account password');
            }

            return [
                'success' => $result,
                'message' => $result ? 'Password changed successfully' : 'Failed to change password'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function uploadAvatar($userId, $files) {
        try {
            if (!isset($files['avatar']) || $files['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No valid avatar file uploaded");
            }

            $currentUser = $this->profileModel->getProfile($userId);
            $uploadResult = $this->uploadHelper->uploadFile($files['avatar'], 'users');

            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['message']);
            }

            if (!empty($currentUser['photo'])) {
                $this->uploadHelper->deleteFile($currentUser['photo']);
            }

            $result = $this->profileModel->updateProfile($userId, ['photo' => $uploadResult['filepath']]);

            return [
                'success' => $result,
                'filepath' => $uploadResult['filepath'],
                'message' => 'Avatar uploaded successfully'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateNotificationSettings($userId, $data) {
        try {
            $settings = [
                'email_login'            => (int)($data['email_login'] ?? 1),
                'email_incident_updates' => (int)($data['email_incident_updates'] ?? 1),
                'email_shift_reminders'  => (int)($data['email_shift_reminders'] ?? 1),
                'push_new_incidents'     => (int)($data['push_new_incidents'] ?? 0),
            ];

            $result = $this->profileModel->updateNotificationSettings($userId, $settings);

            return [
                'success' => $result,
                'message' => $result ? 'Notification preferences updated' : 'No changes made'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Logs into the shared `activity_log` table (module = 'profile'),
     * the same table Incidents/Shifts already write to.
     */
    private function logActivity($userId, $action, $description) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO activity_log (user_id, action, module, description, ip_address, created_at)
                VALUES (?, ?, 'profile', ?, ?, NOW())
            ");
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt->execute([$userId, $action, $description, $ip]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
}
