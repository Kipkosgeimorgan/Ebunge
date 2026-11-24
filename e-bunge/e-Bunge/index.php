<?php

require_once __DIR__ . '/config.php';

// Check whether the user is already logged in
if (current_user() !== null) {

    // Redirect logged-in users to the dashboard
    redirect('dashboard.php');

} else {

    // Redirect visitors to the landing page
    redirect('landing.php');

}

?>

