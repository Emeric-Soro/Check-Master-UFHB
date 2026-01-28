<?php
/**
 * CheckMaster Premium - Select Component
 * 
 * Composant select avec support pour les options multiples et groupées.
 */

/**
 * Select dropdown
 * 
 * @param string $name - Nom du champ
 * @param string $label - Label du champ
 * @param array $options - Options du select ['value' => 'Label'] ou objets avec propriétés
 * @param string|array $selected - Valeur(s) sélectionnée(s)
 * @param array $config - Configuration additionnelle
 */
function renderSelect(
    string $name,
    string $label = '',
    array $options = [],
    $selected = '',
    array $config = []
): string {
    $defaults = [
        'placeholder' => 'Sélectionner...',
        'required' => false,
        'disabled' => false,
        'multiple' => false,
        'class' => '',
        'id' => $name,
        'hint' => '',
        'error' => '',
        'attr' => '',
        'valueKey' => 'value',
        'labelKey' => 'label'
    ];
    
    $opts = array_merge($defaults, $config);
    
    $requiredClass = $opts['required'] ? ' form-label-required' : '';
    $requiredAttr = $opts['required'] ? ' required' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    $multipleAttr = $opts['multiple'] ? ' multiple' : '';
    $errorClass = $opts['error'] ? ' error' : '';
    
    // Convertir selected en array si nécessaire
    $selectedValues = is_array($selected) ? $selected : [$selected];
    
    $labelHtml = $label 
        ? '<label for="' . htmlspecialchars($opts['id']) . '" class="form-label' . $requiredClass . '">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $hintHtml = $opts['hint'] 
        ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' 
        : '';
    
    $errorHtml = $opts['error'] 
        ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' 
        : '';
    
    // Construction des options
    $optionsHtml = '';
    if ($opts['placeholder']) {
        $optionsHtml .= '<option value="">' . htmlspecialchars($opts['placeholder']) . '</option>';
    }
    
    foreach ($options as $key => $option) {
        // Support pour les arrays simples et les objets
        if (is_object($option)) {
            $value = $option->{$opts['valueKey']} ?? $key;
            $text = $option->{$opts['labelKey']} ?? (string) $option;
        } elseif (is_array($option)) {
            $value = $option[$opts['valueKey']] ?? $key;
            $text = $option[$opts['labelKey']] ?? (string) $option;
        } else {
            $value = $key;
            $text = $option;
        }
        
        $isSelected = in_array((string) $value, array_map('strval', $selectedValues)) ? ' selected' : '';
        
        $optionsHtml .= sprintf(
            '<option value="%s"%s>%s</option>',
            htmlspecialchars((string) $value),
            $isSelected,
            htmlspecialchars((string) $text)
        );
    }
    
    $selectHtml = sprintf(
        '<select id="%s" name="%s%s" class="form-select%s %s"%s%s%s %s>%s</select>',
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        $opts['multiple'] ? '[]' : '',
        $errorClass,
        htmlspecialchars($opts['class']),
        $requiredAttr,
        $disabledAttr,
        $multipleAttr,
        $opts['attr'],
        $optionsHtml
    );
    
    return sprintf(
        '<div class="form-group">%s%s%s%s</div>',
        $labelHtml,
        $hintHtml,
        $selectHtml,
        $errorHtml
    );
}

/**
 * Select avec groupes d'options
 */
function renderSelectGrouped(
    string $name,
    string $label = '',
    array $groups = [], // ['Group Name' => ['value' => 'Label']]
    $selected = '',
    array $config = []
): string {
    $defaults = [
        'placeholder' => 'Sélectionner...',
        'required' => false,
        'disabled' => false,
        'class' => '',
        'id' => $name,
        'hint' => '',
        'error' => '',
        'attr' => ''
    ];
    
    $opts = array_merge($defaults, $config);
    
    $requiredClass = $opts['required'] ? ' form-label-required' : '';
    $requiredAttr = $opts['required'] ? ' required' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    $errorClass = $opts['error'] ? ' error' : '';
    
    $labelHtml = $label 
        ? '<label for="' . htmlspecialchars($opts['id']) . '" class="form-label' . $requiredClass . '">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $hintHtml = $opts['hint'] 
        ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' 
        : '';
    
    $errorHtml = $opts['error'] 
        ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' 
        : '';
    
    // Construction des options groupées
    $optionsHtml = '';
    if ($opts['placeholder']) {
        $optionsHtml .= '<option value="">' . htmlspecialchars($opts['placeholder']) . '</option>';
    }
    
    foreach ($groups as $groupName => $options) {
        $optionsHtml .= '<optgroup label="' . htmlspecialchars($groupName) . '">';
        
        foreach ($options as $value => $text) {
            $isSelected = ((string) $value === (string) $selected) ? ' selected' : '';
            $optionsHtml .= sprintf(
                '<option value="%s"%s>%s</option>',
                htmlspecialchars((string) $value),
                $isSelected,
                htmlspecialchars((string) $text)
            );
        }
        
        $optionsHtml .= '</optgroup>';
    }
    
    $selectHtml = sprintf(
        '<select id="%s" name="%s" class="form-select%s %s"%s%s %s>%s</select>',
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        $errorClass,
        htmlspecialchars($opts['class']),
        $requiredAttr,
        $disabledAttr,
        $opts['attr'],
        $optionsHtml
    );
    
    return sprintf(
        '<div class="form-group">%s%s%s%s</div>',
        $labelHtml,
        $hintHtml,
        $selectHtml,
        $errorHtml
    );
}
