<div class="cm-toolbar">
    <div class="cm-toolbar-left">
        <?php if (!empty($search_placeholder)): ?>
        <div class="field has-addons cm-toolbar-search">
            <div class="control has-icons-left is-expanded">
                <input 
                    class="input is-small cm-toolbar-search-input" 
                    type="text" 
                    placeholder="<?= htmlspecialchars($search_placeholder) ?>"
                    value="<?= htmlspecialchars($search_value ?? '') ?>"
                >
                <span class="icon is-small is-left">
                    <i class="fas fa-search"></i>
                </span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php foreach ($filters ?? [] as $filter): ?>
        <div class="field cm-toolbar-filter">
            <?php if (($filter['type'] ?? 'select') === 'select'): ?>
            <div class="control">
                <div class="select is-small">
                    <select name="<?= htmlspecialchars($filter['name']) ?>">
                        <option value=""><?= htmlspecialchars($filter['label'] ?? 'Tous') ?></option>
                        <?php foreach ($filter['options'] ?? [] as $optVal => $optLabel): ?>
                        <option value="<?= htmlspecialchars($optVal) ?>" <?= ($filter['selected'] ?? '') == $optVal ? 'selected' : '' ?>>
                            <?= htmlspecialchars($optLabel) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="cm-toolbar-center">
        <?php if (isset($total_results)): ?>
        <span class="tag is-light cm-toolbar-count">
            <span class="icon"><i class="fas fa-list"></i></span>
            <span><?= number_format($total_results) ?> résultat<?= $total_results > 1 ? 's' : '' ?></span>
        </span>
        <?php endif; ?>
    </div>
    
    <div class="cm-toolbar-right">
        <?php if ($show_export ?? false): ?>
        <a href="<?= htmlspecialchars($export_url ?? '#') ?>" class="button is-small is-light" title="Exporter">
            <span class="icon"><i class="fas fa-download"></i></span>
            <span>Exporter</span>
        </a>
        <?php endif; ?>
        
        <?php if ($show_import ?? false): ?>
        <a href="<?= htmlspecialchars($import_url ?? '#') ?>" class="button is-small is-light" title="Importer">
            <span class="icon"><i class="fas fa-upload"></i></span>
            <span>Importer</span>
        </a>
        <?php endif; ?>
        
        <?php foreach ($actions ?? [] as $action): ?>
        <a href="<?= htmlspecialchars($action['url'] ?? '#') ?>" 
           class="button is-small <?= $action['class'] ?? 'is-light' ?>"
           <?= !empty($action['data']) ? 'data-' . implode(' data-', array_map(fn($k, $v) => "$k=\"$v\"", array_keys($action['data']), $action['data'])) : '' ?>>
            <?php if (!empty($action['icon'])): ?>
            <span class="icon"><i class="fas <?= $action['icon'] ?>"></i></span>
            <?php endif; ?>
            <?php if (!empty($action['label'])): ?>
            <span><?= htmlspecialchars($action['label']) ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
