<?php
/**
 * CheckMaster Premium - Form Input Components
 * 
 * Composants de formulaire (Input, TextArea, Select, etc.)
 */

/**
 * Text Input
 */
function renderInput(
    string $name,
    string $label = '',
    string $value = '',
    string $type = 'text',
    array $options = []
): string {
    $defaults = [
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'class' => '',
        'id' => $name,
        'hint' => '',
        'error' => '',
        'icon' => '',
        'attr' => ''
    ];
    
    $opts = array_merge($defaults, $options);
    
    $requiredClass = $opts['required'] ? ' form-label-required' : '';
    $requiredAttr = $opts['required'] ? ' required' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    $readonlyAttr = $opts['readonly'] ? ' readonly' : '';
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
    
    $inputHtml = sprintf(
        '<input type="%s" id="%s" name="%s" value="%s" placeholder="%s" class="form-input%s %s"%s%s%s %s>',
        htmlspecialchars($type),
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        htmlspecialchars($value),
        htmlspecialchars($opts['placeholder']),
        $errorClass,
        htmlspecialchars($opts['class']),
        $requiredAttr,
        $disabledAttr,
        $readonlyAttr,
        $opts['attr']
    );
    
    // With icon wrapper
    if ($opts['icon']) {
        $inputHtml = sprintf(
            '<div class="search-wrapper">
                <i class="fas %s search-icon"></i>
                %s
            </div>',
            htmlspecialchars($opts['icon']),
            $inputHtml
        );
    }
    
    return sprintf(
        '<div class="form-group">%s%s%s%s</div>',
        $labelHtml,
        $inputHtml,
        $hintHtml,
        $errorHtml
    );
}

/**
 * TextArea
 */
function renderTextArea(
    string $name,
    string $label = '',
    string $value = '',
    array $options = []
): string {
    $defaults = [
        'placeholder' => '',
        'required' => false,
        'disabled' => false,
        'rows' => 4,
        'class' => '',
        'id' => $name,
        'hint' => '',
        'error' => ''
    ];
    
    $opts = array_merge($defaults, $options);
    
    $requiredClass = $opts['required'] ? ' form-label-required' : '';
    $requiredAttr = $opts['required'] ? ' required' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    $errorClass = $opts['error'] ? ' error' : '';
    
    $labelHtml = $label 
        ? '<label for="' . htmlspecialchars($opts['id']) . '" class="form-label' . $requiredClass . '">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $hintHtml = $opts['hint'] ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' : '';
    $errorHtml = $opts['error'] ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' : '';
    
    return sprintf(
        '<div class="form-group">
            %s
            <textarea id="%s" name="%s" placeholder="%s" rows="%d" class="form-textarea%s %s"%s%s>%s</textarea>
            %s%s
        </div>',
        $labelHtml,
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        htmlspecialchars($opts['placeholder']),
        $opts['rows'],
        $errorClass,
        htmlspecialchars($opts['class']),
        $requiredAttr,
        $disabledAttr,
        htmlspecialchars($value),
        $hintHtml,
        $errorHtml
    );
}

/**
 * Select Dropdown
 */
function renderSelect(
    string $name,
    array $options_list,
    string $label = '',
    $selected = '',
    array $options = []
): string {
    $defaults = [
        'placeholder' => 'Sélectionner...',
        'required' => false,
        'disabled' => false,
        'class' => '',
        'id' => $name,
        'hint' => '',
        'error' => '',
        'multiple' => false
    ];
    
    $opts = array_merge($defaults, $options);
    
    $requiredClass = $opts['required'] ? ' form-label-required' : '';
    $requiredAttr = $opts['required'] ? ' required' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    $multipleAttr = $opts['multiple'] ? ' multiple' : '';
    $errorClass = $opts['error'] ? ' error' : '';
    
    $labelHtml = $label 
        ? '<label for="' . htmlspecialchars($opts['id']) . '" class="form-label' . $requiredClass . '">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $hintHtml = $opts['hint'] ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' : '';
    $errorHtml = $opts['error'] ? '<span class="form-error">' . htmlspecialchars($opts['error']) . '</span>' : '';
    
    // Build options HTML
    $optionsHtml = '';
    if ($opts['placeholder'] && !$opts['multiple']) {
        $optionsHtml .= '<option value="">' . htmlspecialchars($opts['placeholder']) . '</option>';
    }
    
    foreach ($options_list as $value => $text) {
        $isSelected = '';
        if (is_array($selected)) {
            $isSelected = in_array($value, $selected) ? ' selected' : '';
        } else {
            $isSelected = ((string) $value === (string) $selected) ? ' selected' : '';
        }
        
        $optionsHtml .= sprintf(
            '<option value="%s"%s>%s</option>',
            htmlspecialchars((string) $value),
            $isSelected,
            htmlspecialchars($text)
        );
    }
    
    return sprintf(
        '<div class="form-group">
            %s
            <select id="%s" name="%s" class="form-select%s %s"%s%s%s>%s</select>
            %s%s
        </div>',
        $labelHtml,
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        $errorClass,
        htmlspecialchars($opts['class']),
        $requiredAttr,
        $disabledAttr,
        $multipleAttr,
        $optionsHtml,
        $hintHtml,
        $errorHtml
    );
}

/**
 * Checkbox
 */
function renderCheckbox(
    string $name,
    string $label,
    bool $checked = false,
    $value = '1',
    array $options = []
): string {
    $defaults = [
        'disabled' => false,
        'id' => $name
    ];
    
    $opts = array_merge($defaults, $options);
    
    $checkedAttr = $checked ? ' checked' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    
    return sprintf(
        '<label class="form-checkbox-label">
            <input type="checkbox" name="%s" value="%s" class="form-checkbox" id="%s"%s%s>
            <span>%s</span>
        </label>',
        htmlspecialchars($name),
        htmlspecialchars((string) $value),
        htmlspecialchars($opts['id']),
        $checkedAttr,
        $disabledAttr,
        htmlspecialchars($label)
    );
}

/**
 * Radio Button
 */
function renderRadio(
    string $name,
    string $label,
    $value,
    bool $checked = false,
    array $options = []
): string {
    $defaults = [
        'disabled' => false,
        'id' => $name . '_' . $value
    ];
    
    $opts = array_merge($defaults, $options);
    
    $checkedAttr = $checked ? ' checked' : '';
    $disabledAttr = $opts['disabled'] ? ' disabled' : '';
    
    return sprintf(
        '<label class="form-radio-label">
            <input type="radio" name="%s" value="%s" class="form-radio" id="%s"%s%s>
            <span>%s</span>
        </label>',
        htmlspecialchars($name),
        htmlspecialchars((string) $value),
        htmlspecialchars($opts['id']),
        $checkedAttr,
        $disabledAttr,
        htmlspecialchars($label)
    );
}

/**
 * Radio Group
 */
function renderRadioGroup(
    string $name,
    array $options_list,
    string $label = '',
    $selected = '',
    bool $inline = false
): string {
    $labelHtml = $label 
        ? '<label class="form-label">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $wrapperClass = $inline ? 'flex gap-md' : 'space-y-2';
    
    $radiosHtml = '<div class="' . $wrapperClass . '">';
    foreach ($options_list as $value => $text) {
        $checked = ((string) $value === (string) $selected);
        $radiosHtml .= renderRadio($name, $text, $value, $checked);
    }
    $radiosHtml .= '</div>';
    
    return sprintf('<div class="form-group">%s%s</div>', $labelHtml, $radiosHtml);
}

/**
 * Date Picker Input
 */
function renderDatePicker(
    string $name,
    string $label = '',
    string $value = '',
    array $options = []
): string {
    $defaults = [
        'placeholder' => 'JJ/MM/AAAA',
        'required' => false,
        'min' => '',
        'max' => '',
        'class' => '',
        'id' => $name
    ];
    
    $opts = array_merge($defaults, $options);
    
    // Convert date format if needed (YYYY-MM-DD to DD/MM/YYYY for display)
    $displayValue = $value;
    if ($value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        $displayValue = date('d/m/Y', strtotime($value));
    }
    
    $minAttr = $opts['min'] ? ' min="' . htmlspecialchars($opts['min']) . '"' : '';
    $maxAttr = $opts['max'] ? ' max="' . htmlspecialchars($opts['max']) . '"' : '';
    
    return renderInput($name, $label, $value, 'date', array_merge($opts, [
        'class' => 'form-input datepicker ' . $opts['class']
    ]));
}

/**
 * Search Bar
 */
function renderSearchBar(
    string $placeholder = 'Rechercher...',
    string $name = 'search',
    string $value = '',
    string $action = ''
): string {
    $formWrapper = $action ? '<form action="' . htmlspecialchars($action) . '" method="GET">' : '';
    $formClose = $action ? '</form>' : '';
    
    return sprintf(
        '%s
        <div class="search-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="%s" class="search-input" placeholder="%s" value="%s">
        </div>
        %s',
        $formWrapper,
        htmlspecialchars($name),
        htmlspecialchars($placeholder),
        htmlspecialchars($value),
        $formClose
    );
}

/**
 * Hidden Input
 */
function renderHidden(string $name, $value): string {
    return sprintf(
        '<input type="hidden" name="%s" value="%s">',
        htmlspecialchars($name),
        htmlspecialchars((string) $value)
    );
}

/**
 * File Input
 */
function renderFileInput(
    string $name,
    string $label = '',
    array $options = []
): string {
    $defaults = [
        'accept' => '',
        'multiple' => false,
        'required' => false,
        'id' => $name,
        'hint' => ''
    ];
    
    $opts = array_merge($defaults, $options);
    
    $acceptAttr = $opts['accept'] ? ' accept="' . htmlspecialchars($opts['accept']) . '"' : '';
    $multipleAttr = $opts['multiple'] ? ' multiple' : '';
    $requiredAttr = $opts['required'] ? ' required' : '';
    $requiredClass = $opts['required'] ? ' form-label-required' : '';
    
    $labelHtml = $label 
        ? '<label for="' . htmlspecialchars($opts['id']) . '" class="form-label' . $requiredClass . '">' . htmlspecialchars($label) . '</label>' 
        : '';
    
    $hintHtml = $opts['hint'] ? '<span class="form-hint">' . htmlspecialchars($opts['hint']) . '</span>' : '';
    
    return sprintf(
        '<div class="form-group">
            %s
            <input type="file" id="%s" name="%s" class="form-input"%s%s%s>
            %s
        </div>',
        $labelHtml,
        htmlspecialchars($opts['id']),
        htmlspecialchars($name),
        $acceptAttr,
        $multipleAttr,
        $requiredAttr,
        $hintHtml
    );
}
