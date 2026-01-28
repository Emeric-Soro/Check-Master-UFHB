<?php
/**
 * CheckMaster Premium - Checkbox & Radio Components
 * 
 * Composants pour cases à cocher et boutons radio.
 */

/**
 * Checkbox
 * 
 * @param string $name - Nom du champ
 * @param string $label - Label du checkbox
 * @param bool $checked - État coché
 * @param string $value - Valeur du checkbox
 * @param array $config - Configuration additionnelle
 */
function renderCheckbox(
    string $name,
    string $label = '',
    bool $checked = false,
    string $value = '1',
    array $config = []
): string {
    $defaults = [
        'disabled' => false,
        'class' => '',
        'id' => $name . '_' . uniqid(),
        'hint' => '',
        'error' => '',
        'attr' => ''
    ];
    
    $opts = array_merge($defaults, $config);
    
    $checkedAttr = $checked ? ' checked' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    $errorClass = $opts['error'] ? ' error' : '';
    
    $hintHtml = $opts['hint'] 
        ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' 
        : '';
    
    $errorHtml = $opts['error'] 
        ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' 
        : '';
    
    return sprintf(
        '<div class="form-check%s">
            <input type="checkbox" id="%s" name="%s" value="%s" class="form-checkbox %s"%s%s %s>
            <label for="%s" class="form-check-label">%s%s</label>
            %s
        </div>',
        $errorClass,
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        htmlspecialchars($value),
        htmlspecialchars($opts['class']),
        $checkedAttr,
        $disabledAttr,
        $opts['attr'],
        htmlspecialchars($opts['id']),
        htmlspecialchars($label),
        $hintHtml,
        $errorHtml
    );
}

/**
 * Groupe de checkboxes
 * 
 * @param string $name - Nom de base (sera suffixé par [])
 * @param string $label - Label du groupe
 * @param array $options - Options ['value' => 'Label']
 * @param array $checked - Valeurs cochées
 * @param array $config - Configuration additionnelle
 */
function renderCheckboxGroup(
    string $name,
    string $label = '',
    array $options = [],
    array $checked = [],
    array $config = []
): string {
    $defaults = [
        'disabled' => false,
        'class' => '',
        'hint' => '',
        'error' => '',
        'inline' => false
    ];
    
    $opts = array_merge($defaults, $config);
    
    $labelHtml = $label 
        ? '<label class="form-label">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $hintHtml = $opts['hint'] 
        ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' 
        : '';
    
    $errorHtml = $opts['error'] 
        ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' 
        : '';
    
    $inlineClass = $opts['inline'] ? ' form-check-inline' : '';
    
    $checkboxesHtml = '';
    foreach ($options as $value => $optionLabel) {
        $isChecked = in_array((string) $value, array_map('strval', $checked));
        $checkboxId = $name . '_' . $value;
        
        $checkboxesHtml .= sprintf(
            '<div class="form-check%s">
                <input type="checkbox" id="%s" name="%s[]" value="%s" class="form-checkbox"%s%s>
                <label for="%s" class="form-check-label">%s</label>
            </div>',
            $inlineClass,
            htmlspecialchars($checkboxId),
            htmlspecialchars($name),
            htmlspecialchars((string) $value),
            $isChecked ? ' checked' : '',
            $opts['disabled'] ? ' disabled' : '',
            htmlspecialchars($checkboxId),
            htmlspecialchars($optionLabel)
        );
    }
    
    return sprintf(
        '<div class="form-group">
            %s%s
            <div class="form-check-group %s">%s</div>
            %s
        </div>',
        $labelHtml,
        $hintHtml,
        htmlspecialchars($opts['class']),
        $checkboxesHtml,
        $errorHtml
    );
}

/**
 * Radio button
 * 
 * @param string $name - Nom du groupe radio
 * @param string $label - Label du bouton radio
 * @param string $value - Valeur du radio
 * @param bool $checked - État coché
 * @param array $config - Configuration additionnelle
 */
function renderRadio(
    string $name,
    string $label = '',
    string $value = '',
    bool $checked = false,
    array $config = []
): string {
    $defaults = [
        'disabled' => false,
        'class' => '',
        'id' => $name . '_' . $value,
        'hint' => '',
        'error' => '',
        'attr' => ''
    ];
    
    $opts = array_merge($defaults, $config);
    
    $checkedAttr = $checked ? ' checked' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    $errorClass = $opts['error'] ? ' error' : '';
    
    $hintHtml = $opts['hint'] 
        ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' 
        : '';
    
    $errorHtml = $opts['error'] 
        ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' 
        : '';
    
    return sprintf(
        '<div class="form-check%s">
            <input type="radio" id="%s" name="%s" value="%s" class="form-radio %s"%s%s %s>
            <label for="%s" class="form-check-label">%s%s</label>
            %s
        </div>',
        $errorClass,
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        htmlspecialchars($value),
        htmlspecialchars($opts['class']),
        $checkedAttr,
        $disabledAttr,
        $opts['attr'],
        htmlspecialchars($opts['id']),
        htmlspecialchars($label),
        $hintHtml,
        $errorHtml
    );
}

/**
 * Groupe de radio buttons
 * 
 * @param string $name - Nom du groupe radio
 * @param string $label - Label du groupe
 * @param array $options - Options ['value' => 'Label']
 * @param string $selected - Valeur sélectionnée
 * @param array $config - Configuration additionnelle
 */
function renderRadioGroup(
    string $name,
    string $label = '',
    array $options = [],
    string $selected = '',
    array $config = []
): string {
    $defaults = [
        'disabled' => false,
        'required' => false,
        'class' => '',
        'hint' => '',
        'error' => '',
        'inline' => false
    ];
    
    $opts = array_merge($defaults, $config);
    
    $requiredClass = $opts['required'] ? ' form-label-required' : '';
    
    $labelHtml = $label 
        ? '<label class="form-label' . $requiredClass . '">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $hintHtml = $opts['hint'] 
        ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' 
        : '';
    
    $errorHtml = $opts['error'] 
        ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' 
        : '';
    
    $inlineClass = $opts['inline'] ? ' form-check-inline' : '';
    
    $radiosHtml = '';
    foreach ($options as $value => $optionLabel) {
        $isChecked = ((string) $value === (string) $selected);
        $radioId = $name . '_' . $value;
        
        $radiosHtml .= sprintf(
            '<div class="form-check%s">
                <input type="radio" id="%s" name="%s" value="%s" class="form-radio"%s%s%s>
                <label for="%s" class="form-check-label">%s</label>
            </div>',
            $inlineClass,
            htmlspecialchars($radioId),
            htmlspecialchars($name),
            htmlspecialchars((string) $value),
            $isChecked ? ' checked' : '',
            $opts['disabled'] ? ' disabled' : '',
            $opts['required'] ? ' required' : '',
            htmlspecialchars($radioId),
            htmlspecialchars($optionLabel)
        );
    }
    
    return sprintf(
        '<div class="form-group">
            %s%s
            <div class="form-check-group %s">%s</div>
            %s
        </div>',
        $labelHtml,
        $hintHtml,
        htmlspecialchars($opts['class']),
        $radiosHtml,
        $errorHtml
    );
}

/**
 * Toggle Switch (checkbox stylisé)
 */
function renderToggle(
    string $name,
    string $label = '',
    bool $checked = false,
    array $config = []
): string {
    $defaults = [
        'disabled' => false,
        'class' => '',
        'id' => $name,
        'hint' => '',
        'attr' => ''
    ];
    
    $opts = array_merge($defaults, $config);
    
    $checkedAttr = $checked ? ' checked' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    
    $hintHtml = $opts['hint'] 
        ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' 
        : '';
    
    return sprintf(
        '<div class="form-toggle">
            <input type="checkbox" id="%s" name="%s" class="toggle-input %s"%s%s %s>
            <label for="%s" class="toggle-label">
                <span class="toggle-switch"></span>
                <span class="toggle-text">%s</span>
            </label>
            %s
        </div>',
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        htmlspecialchars($opts['class']),
        $checkedAttr,
        $disabledAttr,
        $opts['attr'],
        htmlspecialchars($opts['id']),
        htmlspecialchars($label),
        $hintHtml
    );
}
