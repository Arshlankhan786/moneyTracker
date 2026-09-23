<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function require_guest(): void
{
    if (current_user_id()) redirect('dashboard.php');
}