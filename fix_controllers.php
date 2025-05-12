<?php

/**
 * This script will fix the User model to ensure the HasRoles trait is properly imported
 * and used to resolve the issues with hasRole and can methods in controllers.
 */

// Update the User model
$userModelPath = __DIR__ . '/app/Models/User.php';
$userModelContent = file_get_contents($userModelPath);

// Ensure the HasRoles trait is properly imported and used
$updatedUserModelContent = $userModelContent;
if (!strpos($userModelContent, 'use Spatie\Permission\Traits\HasRoles;')) {
    $updatedUserModelContent = str_replace(
        "use Illuminate\Notifications\Notifiable;",
        "use Illuminate\Notifications\Notifiable;\nuse Spatie\Permission\Traits\HasRoles;",
        $userModelContent
    );
}

if (!strpos($userModelContent, 'use HasRoles;') && !strpos($userModelContent, 'use HasFactory, Notifiable, HasRoles;')) {
    $updatedUserModelContent = preg_replace(
        '/use HasFactory, Notifiable;/',
        'use HasFactory, Notifiable, HasRoles;',
        $updatedUserModelContent
    );
}

file_put_contents($userModelPath, $updatedUserModelContent);

echo "Fixed User model to properly use HasRoles trait.\n";

echo "Script completed successfully.\n";
