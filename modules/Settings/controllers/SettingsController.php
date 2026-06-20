<?php
/**
 * GuardReport — Settings Controller
 * File: modules/Settings/controllers/SettingsController.php
 */
require_once dirname(__DIR__, 1) . '/models/SettingsModel.php';

class SettingsController {
    private SettingsModel $sm;
    public function __construct() { $this->sm = new SettingsModel(); }

    public function index(): array {
        return ['schema' => $this->sm->getSchema(), 'values' => $this->sm->getAll()];
    }

    public function update(array $data, int $userId): array {
        try {
            $count = $this->sm->updateMany($data, $userId);
            return ['success' => true, 'message' => $count . ' setting(s) saved'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
