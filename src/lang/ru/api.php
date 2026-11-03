<?php

declare(strict_types=1);

return ['invalid_credentials' => 'Неверные учётные данные.',
    'logout_success' => 'Успешный выход.',
    'user_not_found' => 'Пользователь не найден.',
    'report_not_completed' => 'Отчёт ещё не завершён',
    'report_file_not_found' => 'Файл отчёта не найден в хранилище',
    'unauthorized' => 'Неавторизованный запрос. Укажите валидный Bearer-токен.',
    'invalid_role' => 'Токен не содержит валидной роли. Обновите токен через /auth/refresh.',
    'cannot_change_own_role' => 'Нельзя изменить собственную роль.',
    'last_admin' => 'Нельзя снять роль последнего администратора.',
    'invalid_user_role' => 'Недопустимая роль пользователя.',
    'forbidden_role' => 'Доступ запрещён. Требуется роль: :role'];
