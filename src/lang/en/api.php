<?php

declare(strict_types=1);

return ['invalid_credentials' => 'Invalid credentials.',
    'logout_success' => 'Successfully logged out.',
    'user_not_found' => 'User not found.',
    'report_not_completed' => 'Report is not completed yet',
    'report_file_not_found' => 'Report file not found in storage',
    'unauthorized' => 'Unauthorized request. Provide a valid Bearer token.',
    'invalid_role' => 'Token does not contain a valid role. Refresh it via /auth/refresh.',
    'cannot_change_own_role' => 'You cannot change your own role.',
    'last_admin' => 'You cannot demote the last administrator.',
    'invalid_user_role' => 'Invalid user role.',
    'forbidden_role' => 'Access denied. Required role: :role'];
