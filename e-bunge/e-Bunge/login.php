
<?php
require_once __DIR__ . '/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

$title = 'Sign in';
$root_prefix = '';
$asset_prefix = '';

include __DIR__ . '/includes/header.php';
?>

<main class="auth-shell">

    <!-- LEFT SIDE: IMAGE SLIDESHOW -->
    <div class="auth-visual">

        <div class="auth-slideshow" aria-hidden="true">

            <img src="<?= e($asset_prefix . 'assets/images/login1.jpg') ?>"
                 class="auth-slide active" alt="County assembly">

            <img src="<?= e($asset_prefix . 'assets/images/login2.jpg') ?>"
                 class="auth-slide" alt="Assembly proceedings">

            <img src="<?= e($asset_prefix . 'assets/images/login3.jpg') ?>"
                 class="auth-slide" alt="Legislative session">

        </div>

        <!-- WELCOME CONTENT -->
        <div class="auth-visual-content">

            

            <div>
                <h1>Welcome.</h1>

                <h1>
                    <em>Continue the work.</em>
                </h1>
            </div>

            <div class="auth-quote">
                “In its proper meaning equality before the law means the right
                to participate in the making of the laws by which one is governed.”
                — Nelson Mandela
            </div>

        </div>

    </div>
  <!-- RIGHT SIDE: LOGIN FORM -->
    <div class="auth-card">

        <h2>Sign in to e-Bunge</h2>

        <p class="muted">
            Use your registered work email/username.
        </p>

        <form method="post"
              action="backend/auth.php"
              id="loginForm"
              autocomplete="off">

            <input type="hidden" name="action" value="login">

            <input type="hidden" name="csrf"
                   value="<?= e(csrf_token()) ?>">

            <label>
                Work email or username
                <input type="text"
                       id="login-identity"
                       name="identity"
                       autocomplete="off"
                       required
                       autofocus>
            </label>

            <label>
                Password
                <div class="password-wrap">

                    <input type="password"
                           id="login-password"
                           name="password"
                           autocomplete="new-password"
                           required>
                    <button type="button"
                            class="show-password"
                            data-target="login-password">
                        Show
                    </button>

                </div>
            </label>

            <div class="form-row">

                <label class="check-label">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>

                <a href="forgot_password.php">
                    Forgot password?
                </a>

            </div>

            <button class="btn btn-primary btn-block" type="submit">
                Sign in <span>&rarr;</span>
            </button>

        </form>

        <p class="auth-bottom">
            No account yet?
            <a href="register.php">Create an account</a>
        </p>

    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // SHOW / HIDE PASSWORD
    document.querySelectorAll('.show-password').forEach(function (button) {

        button.addEventListener('click', function () {

            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);

            if (!passwordInput) return;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.textContent = 'Hide';
            } else {
                passwordInput.type = 'password';
                this.textContent = 'Show';
            }

        });

    });

    // LOGIN SLIDESHOW
    const slides = document.querySelectorAll('.auth-slide');

    if (slides.length > 1) {

        let currentSlide = 0;

        setInterval(function () {

            slides[currentSlide].classList.remove('active');

            currentSlide = (currentSlide + 1) % slides.length;

            slides[currentSlide].classList.add('active');

        }, 5000);

    }

});

// CLEAR LOGIN DETAILS WHEN THE PAGE IS DISPLAYED
function clearLoginFields() {

    const form = document.getElementById('loginForm');

    if (!form) return;

    form.reset();

    document.getElementById('login-identity').value = '';
    document.getElementById('login-password').value = '';

    // Always restore hidden password mode
    document.getElementById('login-password').type = 'password';

    // Restore Show button text
    const showButton = document.querySelector('.show-password');

    if (showButton) {
        showButton.textContent = 'Show';
    }
}

// Handles normal page loads and browser Back/Forward navigation
window.addEventListener('pageshow', clearLoginFields);

</script>

</body>
</html>
