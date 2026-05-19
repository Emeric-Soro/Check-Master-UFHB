<?php
/**
 * Dashboard Securite
 * Affiche les IP bloquees, tentatives echouees, heatmap des tentatives
 * Table: auth_rate_limits (ip, action, attempts, blocked_until, window_start, last_attempt)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../app/utils/permissions_helper.php';

if (!canView('dashboard_securite')) {
    $_SESSION['error_message'] = "Acces refuse au dashboard securite.";
    header('Location: ?page=dashboard');
    exit;
}

$db = Database::getConnection();

// --- Statistiques ---
$stats = [];

// IP actuellement bloquees
$stmt = $db->query("SELECT COUNT(DISTINCT ip) AS total FROM auth_rate_limits WHERE blocked_until IS NOT NULL AND blocked_until > NOW()");
$stats['ip_bloquees'] = (int) ($stmt->fetchColumn() ?: 0);

// Tentatives echouees (24h)
$stmt = $db->query("SELECT COUNT(*) AS total FROM auth_rate_limits WHERE last_attempt >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$stats['tentatives_24h'] = (int) ($stmt->fetchColumn() ?: 0);

// Total tentatives
$stmt = $db->query("SELECT SUM(attempts) AS total FROM auth_rate_limits");
$stats['total_tentatives'] = (int) ($stmt->fetchColumn() ?: 0);

// Actions les plus attaquees
$stmt = $db->query("SELECT action, COUNT(DISTINCT ip) AS nb_ips, SUM(attempts) AS nb_tentatives
                    FROM auth_rate_limits
                    GROUP BY action
                    ORDER BY nb_tentatives DESC
                    LIMIT 10");
$topActions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Liste des IP bloquees ---
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$actionFilter = trim((string) ($_GET['action_filter'] ?? ''));

$ipSql = "SELECT ip, action, attempts, blocked_until, window_start, last_attempt
          FROM auth_rate_limits
          WHERE blocked_until IS NOT NULL AND blocked_until > NOW()";
$countSql = "SELECT COUNT(*) FROM auth_rate_limits WHERE blocked_until IS NOT NULL AND blocked_until > NOW()";
$ipParams = [];

if ($actionFilter !== '') {
    $ipSql .= " AND action = :action_filter";
    $countSql .= " AND action = :action_filter";
    $ipParams[':action_filter'] = $actionFilter;
}

$ipSql .= " ORDER BY blocked_until DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($ipSql);
foreach ($ipParams as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ipBloquees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare($countSql);
foreach ($ipParams as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->execute();
$totalBloquees = (int) ($stmt->fetchColumn() ?: 0);
$totalPages = max(1, (int) ceil($totalBloquees / $perPage));

// --- Heatmap (dernieres 24h) ---
$stmt = $db->query("SELECT DATE_FORMAT(last_attempt, '%Y-%m-%d %H:00:00') AS heure, COUNT(*) AS nb
                    FROM auth_rate_limits
                    WHERE last_attempt >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY DATE_FORMAT(last_attempt, '%Y-%m-%d %H:00:00')
                    ORDER BY heure");
$heatmap = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="cm-prd3-screen">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-title">
            <i class="fas fa-shield-alt cm-text-primary"></i>
            Dashboard Securite
        </h1>
        <p class="cm-page-subtitle">Surveillance des tentatives de connexion et IP bloquees</p>
    </div>

    <!-- Cartes statistiques -->
    <div class="cm-grid-3 cm-mb-4">
        <div class="cm-card cm-p-4" style="border-left: 4px solid var(--cm-danger);">
            <div class="cm-text-2xl cm-text-bold cm-text-danger"><?php echo $stats['ip_bloquees']; ?></div>
            <div class="cm-text-muted">IP bloquee(s)</div>
        </div>
        <div class="cm-card cm-p-4" style="border-left: 4px solid var(--cm-warning);">
            <div class="cm-text-2xl cm-text-bold cm-text-warning"><?php echo $stats['tentatives_24h']; ?></div>
            <div class="cm-text-muted">Tentatives echouees (24h)</div>
        </div>
        <div class="cm-card cm-p-4" style="border-left: 4px solid var(--cm-info);">
            <div class="cm-text-2xl cm-text-bold cm-text-info"><?php echo $stats['total_tentatives']; ?></div>
            <div class="cm-text-muted">Total tentatives enregistrees</div>
        </div>
    </div>

    <div class="cm-grid-2 cm-mb-4">
        <!-- Top actions attaquees -->
        <div class="cm-card">
            <div class="cm-card-header cm-p-3 cm-bg-light">
                <h3 class="cm-text-semibold">Actions les plus ciblees</h3>
            </div>
            <div class="cm-p-3">
                <?php if (empty($topActions)): ?>
                    <p class="cm-text-muted">Aucune donnee disponible.</p>
                <?php else: ?>
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Action</th>
                                <th class="cm-data-table__th">IP distinctes</th>
                                <th class="cm-data-table__th">Tentatives</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topActions as $act): ?>
                                <?php $actionName = (string) ($act['action'] ?? ''); ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=dashboard_securite&action_filter=<?php echo urlencode($actionName); ?>">
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars($actionName !== '' ? $actionName : '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo (int) ($act['nb_ips'] ?? 0); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo (int) ($act['nb_tentatives'] ?? 0); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- IP bloquee -->
        <div class="cm-card">
            <div class="cm-card-header cm-p-3 cm-bg-light">
                <h3 class="cm-text-semibold">IP bloquee</h3>
            </div>
            <div class="cm-p-3">
                <?php if ($actionFilter !== ''): ?>
                    <p class="cm-text-sm cm-mb-3">
                        Filtre actif :
                        <strong><?php echo htmlspecialchars($actionFilter, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <a href="?page=dashboard_securite" class="cm-btn is-light is-xs cm-ml-2">Réinitialiser</a>
                    </p>
                <?php endif; ?>
                <?php if (empty($ipBloquees)): ?>
                    <p class="cm-text-muted">Aucune IP bloquee actuellement.</p>
                <?php else: ?>
                    <div class="cm-table-wrapper" style="max-height: 300px; overflow-y: auto;">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">IP</th>
                                    <th class="cm-data-table__th">Action</th>
                                    <th class="cm-data-table__th">Tentatives</th>
                                    <th class="cm-data-table__th">Bloquee jusqu'a</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ipBloquees as $ip): ?>
                                    <?php
                                    $ipAddr = (string) ($ip['ip'] ?? '');
                                    $detailHref = '?page=dashboard_securite&detail_ip=' . urlencode($ipAddr);
                                    if ($actionFilter !== '') {
                                        $detailHref .= '&action_filter=' . urlencode($actionFilter);
                                    }
                                    ?>
                                    <tr class="cm-data-table__row cm-clickable-row"
                                        data-href="<?php echo htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8'); ?>">
                                        <td class="cm-data-table__td">
                                            <code><?php echo htmlspecialchars($ipAddr, ENT_QUOTES, 'UTF-8'); ?></code>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php echo htmlspecialchars((string) ($ip['action'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <span class="cm-badge is-danger"><?php echo (int) ($ip['attempts'] ?? 0); ?></span>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php
                                            $blocked = $ip['blocked_until'] ?? '';
                                            echo $blocked !== '' ? date('d/m/Y H:i', strtotime((string) $blocked)) : '-';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Heatmap des tentatives -->
    <div class="cm-card">
        <div class="cm-card-header cm-p-3 cm-bg-light">
            <h3 class="cm-text-semibold">Activite des tentatives (24h)</h3>
        </div>
        <div class="cm-p-3">
            <?php if (empty($heatmap)): ?>
                <p class="cm-text-muted">Aucune activite recente.</p>
            <?php else: ?>
                <div class="cm-heatmap-grid" style="display: flex; flex-wrap: wrap; gap: 4px; align-items: flex-end;">
                    <?php
                    $maxNb = max(array_column($heatmap, 'nb'));
                    $maxNb = max($maxNb, 1);
                    foreach ($heatmap as $h):
                        $ratio = (int) ($h['nb'] ?? 0) / $maxNb;
                        $opacity = 0.2 + ($ratio * 0.8);
                        ?>
                        <div style="flex: 0 0 60px; text-align: center; margin-bottom: 4px;"
                             title="<?php echo htmlspecialchars((string) ($h['heure'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>: <?php echo (int) ($h['nb'] ?? 0); ?> tentative(s)">
                            <div style="height: 40px; background: rgba(220, 38, 38, <?php echo $opacity; ?>); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #fff;">
                                <?php echo (int) ($h['nb'] ?? 0); ?>
                            </div>
                            <small style="font-size: 8px; color: #999;">
                                <?php echo date('H:00', strtotime((string) ($h['heure'] ?? ''))); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination IP bloquee -->
    <?php if ($totalPages > 1): ?>
        <div class="cm-flex cm-justify-center cm-mt-4 cm-gap-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=dashboard_securite&p=<?php echo $i; ?><?php echo $actionFilter !== '' ? '&action_filter=' . urlencode($actionFilter) : ''; ?>"
                   class="cm-btn is-sm <?php echo $i === $page ? 'is-primary' : 'is-light'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

    <!-- Detail IP -->
    <?php
    $detailIp = trim((string) ($_GET['detail_ip'] ?? ''));
    if ($detailIp !== ''):
        $stmt = $db->prepare("SELECT * FROM auth_rate_limits WHERE ip = ? ORDER BY last_attempt DESC LIMIT 50");
        $stmt->execute([$detailIp]);
        $ipDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="cm-card cm-mt-4">
            <div class="cm-card-header cm-p-3 cm-bg-light cm-flex cm-items-center cm-justify-between">
                <h3 class="cm-text-semibold">
                    <i class="fas fa-search"></i>
                    Details IP: <code><?php echo htmlspecialchars($detailIp, ENT_QUOTES, 'UTF-8'); ?></code>
                </h3>
                <a href="?page=dashboard_securite" class="cm-btn is-light is-sm">Fermer</a>
            </div>
            <div class="cm-p-3">
                <?php if (empty($ipDetails)): ?>
                    <p class="cm-text-muted">Aucun enregistrement pour cette IP.</p>
                <?php else: ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Action</th>
                                    <th class="cm-data-table__th">Tentatives</th>
                                    <th class="cm-data-table__th">Derniere tentative</th>
                                    <th class="cm-data-table__th">Deblocage</th>
                                    <th class="cm-data-table__th">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ipDetails as $det): ?>
                                    <tr class="cm-data-table__row">
                                        <td class="cm-data-table__td">
                                            <?php echo htmlspecialchars((string) ($det['action'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php echo (int) ($det['attempts'] ?? 0); ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php
                                            $last = $det['last_attempt'] ?? '';
                                            echo $last !== '' ? date('d/m/Y H:i:s', strtotime((string) $last)) : '-';
                                            ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php
                                            $blocked = $det['blocked_until'] ?? '';
                                            if ($blocked !== '' && strtotime((string) $blocked) > time()) {
                                                echo '<span class="cm-badge is-danger">Bloquee jusqu\'au ' . date('d/m/Y H:i', strtotime((string) $blocked)) . '</span>';
                                            } elseif ($blocked !== '') {
                                                echo '<span class="cm-badge is-success">Debloquee</span>';
                                            } else {
                                                echo '<span class="cm-badge is-info">Non bloquee</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php
                                            $blockedUntil = $det['blocked_until'] ?? '';
                                            if ($blockedUntil !== '' && strtotime((string) $blockedUntil) > time()) {
                                                echo '<span class="cm-badge is-danger">Bloquee</span>';
                                            } else {
                                                echo '<span class="cm-badge is-success">Active</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
