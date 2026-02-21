<input 
    type="hidden"
    name="<?= htmlspecialchars($name) ?>"
    value="<?= htmlspecialchars($value ?? '') ?>"
    <?php foreach ($attrs ?? [] as $attr => $val): ?>
        <?= htmlspecialchars($attr) ?>="<?= htmlspecialchars($val) ?>"
    <?php endforeach; ?>
>
