
<?php

require_once __DIR__ . '/config.php';

// Page configuration
$title = 'Reset Password';
$root_prefix = '';
$asset_prefix = '';

// Load header
include __DIR__ . '/includes/header.php';

?>

<main class="simple-auth">

    <div class="auth-card">

        <!-- e-Bunge branding -->
        <a class="brand" href="landing.php">
           <span class="brand-mark">
    <img src="assets/images/logo.png" alt="e-Bunge Logo">
</span>
            <span>e-Bunge</span>
        </a>

        <!-- Page heading -->

        <h2>Forgot your password?</h2>

        <p class="muted">
            Enter your registered work email address.
            An authorised administrator will verify your request
            and assist you in restoring access to your account.
        </p>

        <!-- Password reset request form -->
        <form method="POST" action="backend/auth.php">

            <input
                type="hidden"
                name="action"
                value="forgot"
            >

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf_token()); ?>"
            >

            <label for="work_email">
                Work email

                <input
                    type="email"
                    id="work_email"
                    name="work_email"
                    placeholder="Enter your work email"
                    autocomplete="email"
                    required
                >
            </label>

            <button
                type="submit"
                class="btn btn-primary btn-block"
            >
                Request password reset
            </button>

        </form>

        <!-- Return to login -->
        <p class="auth-bottom">
            <a href="login.php">
                ← Back to sign in
            </a>
        </p>

    </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
