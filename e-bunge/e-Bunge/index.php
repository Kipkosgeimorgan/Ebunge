
<?php
require_once __DIR__ . '/config.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

redirect('landing.php');
