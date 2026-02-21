<input 
    type="hidden"
    name="<?= htmlspecialchars($name ?? '_csrf_token') ?>"
    value="<?= htmlspecialchars($token ?? '') ?>"
    class="csrf-token"
>
