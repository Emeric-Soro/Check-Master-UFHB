<?php
/**
 * CheckMaster Premium - Avatar Component
 * 
 * Génération d'avatars avec initiales ou images.
 */

/**
 * Render an avatar with initials
 * 
 * @param string $name - Nom complet pour générer les initiales
 * @param string $size - Taille (sm, default, lg, xl)
 * @param string|null $image - URL de l'image (optionnel)
 * @param string $type - Type de couleur de fond
 */
function renderAvatar(
    string $name,
    string $size = '',
    ?string $image = null,
    string $type = 'primary'
): string {
    $sizeClass = $size ? ' avatar-' . $size : '';
    
    // Generate initials from name
    $initials = getInitials($name);
    
    $colorClasses = [
        'primary' => 'bg-accent-light text-accent',
        'success' => 'bg-success-light text-success',
        'warning' => 'bg-warning-light text-warning',
        'danger'  => 'bg-danger-light text-danger'
    ];
    
    $colorClass = $colorClasses[$type] ?? $colorClasses['primary'];
    
    if ($image) {
        return sprintf(
            '<div class="avatar%s">
                <img src="%s" alt="%s" loading="lazy">
            </div>',
            $sizeClass,
            htmlspecialchars($image),
            htmlspecialchars($name)
        );
    }
    
    return sprintf(
        '<div class="avatar%s %s" title="%s">%s</div>',
        $sizeClass,
        $colorClass,
        htmlspecialchars($name),
        htmlspecialchars($initials)
    );
}

/**
 * Generate initials from name
 */
function getInitials(string $name): string {
    $name = trim($name);
    if (empty($name)) {
        return '?';
    }
    
    $parts = preg_split('/\s+/', $name);
    
    if (count($parts) >= 2) {
        // First letter of first and last name
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }
    
    // Single name: first two letters
    return strtoupper(mb_substr($name, 0, 2));
}

/**
 * Avatar group (multiple avatars stacked)
 */
function renderAvatarGroup(array $users, int $maxShow = 4, string $size = ''): string {
    $sizeClass = $size ? ' avatar-' . $size : '';
    $total = count($users);
    $visible = array_slice($users, 0, $maxShow);
    $remaining = $total - $maxShow;
    
    $html = '<div class="flex -space-x-2">';
    
    foreach ($visible as $user) {
        $name = $user['name'] ?? $user['nom'] ?? 'User';
        $image = $user['image'] ?? $user['photo'] ?? null;
        
        $html .= sprintf(
            '<div class="avatar%s border-2 border-white">%s</div>',
            $sizeClass,
            $image 
                ? '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($name) . '">' 
                : htmlspecialchars(getInitials($name))
        );
    }
    
    if ($remaining > 0) {
        $html .= sprintf(
            '<div class="avatar%s bg-muted-light text-muted border-2 border-white">+%d</div>',
            $sizeClass,
            $remaining
        );
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Avatar with status indicator
 */
function renderAvatarStatus(
    string $name,
    string $status = 'online',
    string $size = '',
    ?string $image = null
): string {
    $statusColors = [
        'online'  => 'bg-success',
        'offline' => 'bg-muted',
        'busy'    => 'bg-danger',
        'away'    => 'bg-warning'
    ];
    
    $statusClass = $statusColors[$status] ?? $statusColors['offline'];
    $sizeClass = $size ? ' avatar-' . $size : '';
    
    $initials = getInitials($name);
    
    $content = $image 
        ? '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($name) . '">' 
        : htmlspecialchars($initials);
    
    return sprintf(
        '<div class="relative inline-block">
            <div class="avatar%s">%s</div>
            <span class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white %s"></span>
        </div>',
        $sizeClass,
        $content,
        $statusClass
    );
}

/**
 * User info with avatar
 */
function renderUserInfo(
    string $name,
    string $role = '',
    ?string $image = null,
    string $size = ''
): string {
    $avatar = renderAvatar($name, $size, $image);
    
    $roleHtml = $role 
        ? '<span class="text-sm text-muted">' . htmlspecialchars($role) . '</span>' 
        : '';
    
    return sprintf(
        '<div class="flex items-center gap-md">
            %s
            <div>
                <div class="font-medium">%s</div>
                %s
            </div>
        </div>',
        $avatar,
        htmlspecialchars($name),
        $roleHtml
    );
}
