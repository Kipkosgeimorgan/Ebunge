
<?php
require_once __DIR__ . '/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

$title = 'Register account';
$root_prefix = '';
$asset_prefix = '';

include __DIR__ . '/includes/header.php';
?>

<main class="auth-shell">

    <!-- LEFT SIDE: BACKGROUND SLIDESHOW -->
    <div class="auth-visual registration-visual">

        <!-- Slideshow images -->
        <div class="auth-slideshow" aria-hidden="true">

            <img src="assets/images/login1.jpg"
                 class="auth-slide active"
                 alt="County assembly">

            <img src="assets/images/login2.jpg"
                 class="auth-slide"
                 alt="Assembly proceedings">

            <img src="assets/images/login3.jpg"
                 class="auth-slide"
                 alt="Legislative session">

        </div>
  
        <div class="registration-content">

            <h1>
                Your role.<br>
                <em>Your workspace.</em>
            </h1>
            <p>
                Choose the account role assigned to your office.
                Permissions are applied automatically.
        </p>

        </div>

        

    </div>
  <!-- RIGHT SIDE: REGISTRATION FORM -->
    <div class="auth-card wide">

        <!-- MOBILE BRAND -->
        <a class="mobile-brand brand" href="landing.php">

            <img src="assets/images/logo.png"
                 alt="Elgeyo Marakwet County Assembly Logo"
                 class="brand-logo">

            <span>e-Bunge</span>

        </a>

        <p class="eyebrow">NEW ACCOUNT</p>

        <h2>Create your account</h2>

        <p class="muted">
            Use your official work details.
            Speaker accounts are limited to two users.
        </p>

        <form method="post" action="backend/auth.php">

            <input type="hidden" name="action" value="register">

            <input type="hidden"
                   name="csrf"
                   value="<?= e(csrf_token()) ?>">
            <label>
                Work email
                <input type="email"
                       name="work_email"
                       required
                       placeholder="name@countyassembly.go.ke">
            </label>

            <label>
                Username
                <input type="text"
                       name="username"
                       required
                       minlength="3">
            </label>

            <label>
                Account role
                <select name="role" required>

                    <option value="">Select your office</option>

                    <?php foreach (ROLES as $key => $name): ?>

                        <option value="<?= e($key) ?>">
                            <?= e($name) ?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </label>

            <!-- PASSWORD FIELDS -->
            <div class="two-col">

                <label>
                    Password

                    <div class="password-wrap">

                        <input type="password"
                               id="register-password"
                               name="password"
                               required
                               minlength="8">

                        <button type="button"
                                class="show-password"
                                data-target="register-password">
                            Show
                        </button>

                    </div>
                </label>

                <label>
                    Confirm password

                    <div class="password-wrap">

                        <input type="password"
                               id="confirm-password"
                               name="confirm_password"
                               required
                               minlength="8">

                        <button type="button"
                                class="show-password"
                                data-target="confirm-password">
                            Show
                        </button>

                    </div>
                </label>

            </div>

            <label class="check-label">

                <input type="checkbox" required>

                I confirm these are my official account details.

            </label>

            <button class="btn btn-primary btn-block" type="submit">
                Create account <span>&rarr;</span>
            </button>

        </form>

        <p class="auth-bottom">
            Already registered?
            <a href="login.php">Sign in</a>
        </p>

    </div>

</main>


<!-- SLIDESHOW AND PASSWORD SCRIPT -->
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


    // AUTOMATIC BACKGROUND SLIDESHOW
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
</script>

</body>
</html>
