<?php

if (!function_exists('cm_student_record_value')) {
    function cm_student_record_value($value, string $fallback = '—'): string
    {
        if ($value === null) {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            $text = trim($value);
            if ($text === '' || $text === '0000-00-00' || $text === '0000-00-00 00:00:00') {
                return $fallback;
            }

            return $text;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $fallback;
        }

        if (is_array($value)) {
            $parts = array_values(array_filter(array_map(static function ($item): string {
                return is_scalar($item) ? trim((string) $item) : '';
            }, $value), static function ($item): bool {
                return $item !== '';
            }));

            return empty($parts) ? $fallback : implode(', ', $parts);
        }

        return $fallback;
    }
}

if (!function_exists('cm_student_record_html')) {
    function cm_student_record_html($value, string $fallback = '—'): string
    {
        return htmlspecialchars(cm_student_record_value($value, $fallback), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cm_student_record_date')) {
    function cm_student_record_date($value, string $fallback = '—', string $format = 'd/m/Y'): string
    {
        $text = cm_student_record_value($value, '');
        if ($text === '') {
            return $fallback;
        }

        $formatted = FormattingUtils::formatDate($text, $format);
        return trim((string) $formatted) !== '' ? $formatted : $fallback;
    }
}

if (!function_exists('cm_student_record_time')) {
    function cm_student_record_time($value, string $fallback = '—'): string
    {
        $text = cm_student_record_value($value, '');
        if ($text === '') {
            return $fallback;
        }

        $timestamp = strtotime($text);
        if ($timestamp !== false) {
            return date('H:i', $timestamp);
        }

        if (preg_match('/^\d{2}:\d{2}/', $text) === 1) {
            return substr($text, 0, 5);
        }

        return $fallback;
    }
}

if (!function_exists('cm_student_record_period')) {
    function cm_student_record_period($start, $end, string $fallback = '—'): string
    {
        $startLabel = cm_student_record_date($start, '');
        $endLabel = cm_student_record_date($end, '');

        if ($startLabel === '' && $endLabel === '') {
            return $fallback;
        }

        if ($startLabel !== '' && $endLabel !== '') {
            return $startLabel . ' - ' . $endLabel;
        }

        return $startLabel !== '' ? $startLabel : $endLabel;
    }
}

if (!function_exists('cm_student_record_days_between')) {
    function cm_student_record_days_between($start, $end): ?int
    {
        $startLabel = cm_student_record_value($start, '');
        $endLabel = cm_student_record_value($end, '');
        if ($startLabel === '' || $endLabel === '') {
            return null;
        }

        $startTs = strtotime($startLabel);
        $endTs = strtotime($endLabel);
        if ($startTs === false || $endTs === false || $endTs < $startTs) {
            return null;
        }

        return (int) floor((($endTs - $startTs) / 86400)) + 1;
    }
}

if (!function_exists('cm_student_record_decimal')) {
    function cm_student_record_decimal($value, int $decimals = 2, string $suffix = '', string $fallback = '—'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return number_format((float) $value, $decimals, ',', ' ') . $suffix;
    }
}

if (!function_exists('cm_student_record_money')) {
    function cm_student_record_money($value, string $fallback = '—'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return FormattingUtils::formatMoney((float) $value) . ' FCFA';
    }
}

if (!function_exists('cm_student_record_percent')) {
    function cm_student_record_percent($part, $whole, int $precision = 0): float
    {
        $wholeValue = (float) $whole;
        if ($wholeValue <= 0) {
            return 0.0;
        }

        $percentage = ((float) $part / $wholeValue) * 100;
        return max(0.0, min(100.0, round($percentage, $precision)));
    }
}

if (!function_exists('cm_student_record_score_tone')) {
    function cm_student_record_score_tone($score): string
    {
        if ($score === null || $score === '') {
            return 'info';
        }

        $value = (float) $score;
        if ($value >= 14) {
            return 'success';
        }

        if ($value >= 10) {
            return 'warning';
        }

        return 'danger';
    }
}

if (!function_exists('cm_student_record_status_type')) {
    function cm_student_record_status_type(string $status): string
    {
        $normalized = mb_strtolower(trim($status), 'UTF-8');
        $normalized = strtr($normalized, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'à' => 'a',
            'ù' => 'u',
            'ô' => 'o',
            'î' => 'i',
            'ï' => 'i',
        ]);

        $successTokens = ['valide', 'resolu', 'sold', 'termine', 'disponible', 'publie', 'admis', 'oui'];
        foreach ($successTokens as $token) {
            if ($normalized !== '' && str_contains($normalized, $token)) {
                return 'success';
            }
        }

        $dangerTokens = ['rejet', 'impaye', 'retard', 'ajour', 'non', 'annule', 'erreur'];
        foreach ($dangerTokens as $token) {
            if ($normalized !== '' && str_contains($normalized, $token)) {
                return 'danger';
            }
        }

        $warningTokens = ['attente', 'partiel', 'cours', 'pending', 'analyse', 'revision'];
        foreach ($warningTokens as $token) {
            if ($normalized !== '' && str_contains($normalized, $token)) {
                return 'warning';
            }
        }

        return 'info';
    }
}

if (!function_exists('cm_student_record_event_icon')) {
    function cm_student_record_event_icon(string $type): string
    {
        return match ($type) {
            'inscription' => 'fa-file-signature',
            'candidature' => 'fa-envelope-open-text',
            'depot_rapport' => 'fa-file-lines',
            'validation' => 'fa-stamp',
            'soutenance' => 'fa-microphone-lines',
            'reclamation' => 'fa-life-ring',
            default => 'fa-circle',
        };
    }
}

if (!function_exists('cm_student_record_event_tone')) {
    function cm_student_record_event_tone(string $type): string
    {
        return match ($type) {
            'validation' => 'success',
            'reclamation' => 'warning',
            'soutenance' => 'primary',
            default => 'info',
        };
    }
}

if (!function_exists('cm_student_record_document_label')) {
    function cm_student_record_document_label(string $type): string
    {
        return match ($type) {
            'fiche_inscription' => 'Fiche inscription',
            'compte_rendu' => 'Compte rendu',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}

if (!function_exists('cm_student_record_document_tone')) {
    function cm_student_record_document_tone(string $type): string
    {
        return match ($type) {
            'rapport' => 'primary',
            'compte_rendu' => 'warning',
            'fiche_inscription' => 'info',
            default => 'light',
        };
    }
}

if (!function_exists('cm_student_record_document_icon')) {
    function cm_student_record_document_icon(string $type): string
    {
        return match ($type) {
            'rapport' => 'fa-file-lines',
            'compte_rendu' => 'fa-file-contract',
            'fiche_inscription' => 'fa-file-circle-check',
            default => 'fa-file',
        };
    }
}

if (!function_exists('cm_student_record_format_size')) {
    function cm_student_record_format_size($bytes): string
    {
        if ($bytes === null || $bytes === '' || (int) $bytes <= 0) {
            return '—';
        }

        $value = (float) $bytes;
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $index = 0;
        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, $index === 0 ? 0 : 1, ',', ' ') . ' ' . $units[$index];
    }
}

if (!function_exists('cm_student_record_capture')) {
    function cm_student_record_capture(callable $renderer): string
    {
        ob_start();
        $renderer();
        return (string) ob_get_clean();
    }
}

if (!function_exists('cm_student_record_badge')) {
    function cm_student_record_badge(string $text, ?string $type = null): void
    {
        $label = trim($text);
        if ($label === '') {
            return;
        }

        cm_component('ui/badge', [
            'text' => $label,
            'type' => $type ?: cm_student_record_status_type($label),
        ]);
    }
}

if (!function_exists('cm_student_record_badge_html')) {
    function cm_student_record_badge_html(string $text, ?string $type = null): string
    {
        return cm_student_record_capture(static function () use ($text, $type): void {
            cm_student_record_badge($text, $type);
        });
    }
}

if (!function_exists('cm_student_record_metric')) {
    function cm_student_record_metric(string $label, string $value, string $icon = 'fa-circle', string $tone = 'primary', string $meta = ''): void
    {
        $allowedTones = ['primary', 'success', 'warning', 'danger', 'info'];
        if (!in_array($tone, $allowedTones, true)) {
            $tone = 'primary';
        }
        ?>
        <article class="cm-student-record__metric is-<?= htmlspecialchars($tone, ENT_QUOTES, 'UTF-8') ?>">
            <span class="cm-student-record__metric-icon" aria-hidden="true">
                <i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
            </span>
            <div class="cm-student-record__metric-content">
                <strong class="cm-student-record__metric-value"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="cm-student-record__metric-label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($meta !== ''): ?>
                    <span class="cm-student-record__metric-meta"><?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }
}

if (!function_exists('cm_student_record_field_list')) {
    function cm_student_record_field_list(array $items): void
    {
        ?>
        <div class="cm-student-record__list">
            <?php foreach ($items as $item): ?>
                <?php if (!empty($item['hide'])) {
                    continue;
                } ?>
                <?php
                $label = (string) ($item['label'] ?? '');
                $html = $item['html'] ?? null;
                $value = $item['value'] ?? '—';
                $muted = !empty($item['muted']);
                ?>
                <div class="cm-student-record__row">
                    <span class="cm-student-record__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="cm-student-record__value<?= $muted ? ' is-muted' : '' ?>">
                        <?php if ($html !== null): ?>
                            <?= $html ?>
                        <?php else: ?>
                            <?= htmlspecialchars(cm_student_record_value($value), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

if (!function_exists('cm_student_record_document_actions')) {
    function cm_student_record_document_actions(string $type, $id, string $title = 'Document', bool $allowPreview = true): void
    {
        if ($id === null || $id === '') {
            return;
        }

        $previewUrl = '?page=docviewer&type=' . urlencode($type) . '&id=' . urlencode((string) $id) . '&action=preview';
        $downloadUrl = '?page=docviewer&type=' . urlencode($type) . '&id=' . urlencode((string) $id) . '&action=download';
        $safeType = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        $safeId = htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        ?>
        <div class="cm-student-record__document-actions">
            <?php if ($allowPreview): ?>
                <a href="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>"
                   class="cm-btn is-primary is-sm"
                   onclick="if (window.CM && typeof CM.openDocViewer === 'function') { CM.openDocViewer('<?= $safeType ?>', '<?= $safeId ?>', {title: '<?= $safeTitle ?>'}); return false; }">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                    <span>Voir</span>
                </a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-light is-sm">
                <i class="fas fa-download" aria-hidden="true"></i>
                <span>Télécharger</span>
            </a>
        </div>
        <?php
    }
}

if (!function_exists('cm_student_record_empty')) {
    function cm_student_record_empty(string $title, string $message = '', string $icon = 'fa-inbox'): void
    {
        echo '<div class="cm-student-record__empty">';
        cm_component('ui/empty-state', [
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
        ]);
        echo '</div>';
    }
}
