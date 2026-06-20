<?php
/**
 * GuardReport — Report Builder Controller
 * File: modules/Reports/controllers/ReportBuilderController.php
 */
require_once dirname(__DIR__, 1) . '/models/ReportBuilderModel.php';
require_once dirname(__DIR__, 3) . '/modules/Settings/models/SettingsModel.php';

class ReportBuilderController {
    private ReportBuilderModel $rbm;
    private SettingsModel      $settings;

    public function __construct() {
        $this->rbm      = new ReportBuilderModel();
        $this->settings = new SettingsModel();
    }

    public function catalog(): array {
        return ReportBuilderModel::getCatalog();
    }

    public function data(string $type, array $rawFilters): array {
        $filters = $this->normalizeFilters($rawFilters);
        $rows    = $this->rbm->getData($type, $filters);
        return ['rows' => $rows, 'filters' => $filters, 'count' => count($rows)];
    }

    /**
     * Returns ['html' => ...] always (usable for on-screen print / Ctrl+P → Save as PDF).
     * Returns an additional ['pdf_base64' => ...] key ONLY when Dompdf is available.
     */
    public function export(string $type, array $fields, array $rawFilters, int $userId): array {
        $catalog = ReportBuilderModel::getCatalog();
        if (!isset($catalog[$type])) throw new Exception('Unknown report type');

        $allFields = $catalog[$type]['fields'];
        $fields    = array_values(array_intersect($fields, array_keys($allFields)));
        if (empty($fields)) $fields = $catalog[$type]['defaults'];

        $filters = $this->normalizeFilters($rawFilters);
        $rows    = $this->rbm->getData($type, $filters);
        $values  = $this->settings->getAll();

        $html = $this->buildHtml($catalog[$type]['label'], $allFields, $fields, $rows, $filters, $values);

        $result = ['html' => $html, 'count' => count($rows)];

        $pdfBinary = $this->tryRenderPdf($html);
        if ($pdfBinary !== null) {
            $result['pdf_base64'] = base64_encode($pdfBinary);
        }

        $this->logExport($userId, $type, count($rows));

        return $result;
    }

    private function normalizeFilters(array $f): array {
        $dateTo   = !empty($f['date_to'])   ? $f['date_to']   : date('Y-m-d');
        $dateFrom = !empty($f['date_from']) ? $f['date_from'] : date('Y-m-d', strtotime('-30 days'));
        if (strtotime($dateFrom) > strtotime($dateTo)) {
            throw new Exception('"Date from" cannot be after "date to"');
        }
        $out = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        foreach (['site_id','guard_id','role_id','severity','status'] as $k) {
            if (!empty($f[$k])) $out[$k] = $f[$k];
        }
        return $out;
    }

    private function buildHtml(string $title, array $allFields, array $fields, array $rows, array $filters, array $settingsValues): string {
        $company = htmlspecialchars($settingsValues['company_name'] ?: $settingsValues['app_name']);
        $footer  = htmlspecialchars($settingsValues['report_footer_text']);
        $genAt   = date('d M Y, H:i');
        $range   = htmlspecialchars($filters['date_from']) . ' &nbsp;to&nbsp; ' . htmlspecialchars($filters['date_to']);

        $thead = '<tr>';
        foreach ($fields as $f) $thead .= '<th>' . htmlspecialchars($allFields[$f]) . '</th>';
        $thead .= '</tr>';

        $tbody = '';
        foreach ($rows as $r) {
            $tbody .= '<tr>';
            foreach ($fields as $f) {
                $val = $r[$f] ?? '';
                $tbody .= '<td>' . $this->formatCell($f, $val) . '</td>';
            }
            $tbody .= '</tr>';
        }
        if (empty($rows)) {
            $tbody = '<tr><td colspan="' . count($fields) . '" style="text-align:center;color:#888;padding:24px">No records found for the selected range and filters.</td></tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="utf-8">
<style>
  * { box-sizing:border-box; }
  body { font-family: 'DejaVu Sans', Arial, sans-serif; color:#1a2230; margin:0; padding:32px; font-size:11.5px; }
  .rpt-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #0F2744; padding-bottom:14px; margin-bottom:18px; }
  .rpt-company { font-size:18px; font-weight:800; color:#0F2744; letter-spacing:.02em; }
  .rpt-title { font-size:13px; color:#1d4ed8; font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-top:4px; }
  .rpt-meta { text-align:right; font-size:11px; color:#555; line-height:1.6; }
  table { width:100%; border-collapse:collapse; }
  th { background:#0F2744; color:#fff; text-align:left; padding:8px 9px; font-size:10.5px; text-transform:uppercase; letter-spacing:.03em; }
  td { padding:7px 9px; border-bottom:1px solid #e3e7ed; vertical-align:top; }
  tr:nth-child(even) td { background:#f7f9fb; }
  .rpt-footer { margin-top:22px; padding-top:10px; border-top:1px solid #e3e7ed; font-size:9.5px; color:#888; display:flex; justify-content:space-between; }
  .badge-critical{color:#dc2626;font-weight:700;} .badge-high{color:#d97706;font-weight:700;}
  .badge-open{color:#1d4ed8;font-weight:700;} .badge-resolved,.badge-completed{color:#16a34a;font-weight:700;}
</style></head>
<body>
  <div class="rpt-header">
    <div>
      <div class="rpt-company">{$company}</div>
      <div class="rpt-title">{$title} Report</div>
    </div>
    <div class="rpt-meta">
      Generated: {$genAt}<br>
      Range: {$range}
    </div>
  </div>
  <table>
    <thead>{$thead}</thead>
    <tbody>{$tbody}</tbody>
  </table>
  <div class="rpt-footer">
    <span>{$footer}</span>
    <span>{$genAt}</span>
  </div>
</body></html>
HTML;
    }

    private function formatCell(string $field, $val): string {
        if ($val === null) return '—';
        if (in_array($field, ['severity','status'])) {
            $cls = 'badge-' . strtolower($val);
            return '<span class="' . $cls . '">' . htmlspecialchars(ucfirst($val)) . '</span>';
        }
        if (in_array($field, ['start_time','end_time','incident_date','created_at','last_login'])) {
            return $val ? htmlspecialchars(date('d M Y, H:i', strtotime($val))) : '—';
        }
        if ($val === '') return '—';
        return htmlspecialchars((string)$val);
    }

    /**
     * Tries Dompdf if it's been installed via Composer (composer require dompdf/dompdf).
     * Returns raw PDF bytes, or null if the library isn't available — the caller falls
     * back to the browser's own Print → Save as PDF, which needs no server dependency.
     */
    private function tryRenderPdf(string $html): ?string {
        $autoload = ROOT_PATH . '/vendor/autoload.php';
        if (!file_exists($autoload)) return null;
        require_once $autoload;
        if (!class_exists('Dompdf\Dompdf')) return null;

        try {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->loadHtml($html);
            $dompdf->render();
            return $dompdf->output();
        } catch (\Throwable $e) {
            error_log('Dompdf render failed: ' . $e->getMessage());
            return null;
        }
    }

    private function logExport(int $userId, string $type, int $rowCount): void {
        try {
            Database::getConnection()->prepare(
                "INSERT INTO activity_log (user_id,action,module,description,ip_address) VALUES (:u,'report_export','reports',:d,:ip)"
            )->execute([
                ':u'=>$userId,
                ':d'=>"Exported $type report ($rowCount rows)",
                ':ip'=>$_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Exception $e) { error_log('Log: '.$e->getMessage()); }
    }
}
