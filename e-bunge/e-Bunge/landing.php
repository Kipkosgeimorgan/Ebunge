
<?php
require_once __DIR__ . '/config.php';

$title = 'Home';
$root_prefix = '';
$asset_prefix = '';
$hide_user_topbar = true;

/* Load slideshow images */
$slideshow_directory = __DIR__ . '/assets/images/slideshow/';
$slideshow_images = [];

if (is_dir($slideshow_directory)) {
    $files = glob(
        $slideshow_directory . '*.{jpg,jpeg,png,webp}',
        GLOB_BRACE
    );

    foreach ($files ?: [] as $file) {
        $slideshow_images[] =
            'assets/images/slideshow/' . rawurlencode(basename($file));
    }
}

/* Fallback image */
if (empty($slideshow_images)) {
    $slideshow_images[] = 'assets/images/picture%201.jpg';
}

include __DIR__ . '/includes/header.php';
?>

<header class="public-nav">

    <a class="brand" href="landing.php">
        <span class="brand-mark">
            <img src="assets/images/logo.png" alt="County Assembly Logo">
        </span>
        <span>e-Bunge</span>
    </a>

    <nav class="landing-nav-links">
        <a href="landing.php">Home</a>
        <a href="#about">About</a>
        <a href="#features">Services</a>
        <a href="#workflow">How It Works</a>
        <a href="public_portal.php">Public Portal</a>
        <a href="#contact">Contact</a>
    </nav>

    <div class="nav-actions">
        <a class="btn btn-outline" href="login.php">Sign In</a>
        <a class="btn btn-primary" href="register.php">Get Started</a>
    </div>

</header>

<main class="landing">

    <!-- ================= HERO SECTION ================= -->

   <section class="hero hero--photo">

    <!-- BACKGROUND SLIDESHOW -->
    <div class="hero-slideshow">

        <?php foreach ($slideshow_images as $index => $image): ?>

            <div
                class="hero-slide <?= $index === 0 ? 'active' : '' ?>"
                style="background-image: url('<?= e($image) ?>')">
            </div>

        <?php endforeach; ?>

    </div>

    <!-- CENTERED CONTENT -->
    <div class="hero-content">

        <!-- COUNTY ASSEMBLY LOGO -->
        <div class="hero-centered-logo">
    <img
        src="assets/images/logo.png"
        alt="Elgeyo Marakwet County Assembly Logo">
</div>

        <p class="hero-eyebrow">
            ELGEYO MARAKWET COUNTY ASSEMBLY
        </p>

        <h1>
            Welcome to <span>e-Bunge</span>
        </h1>

        <p class="hero-description">
            Transforming county assembly operations through
            digital governance, transparent legislative processes,
            secure electronic voting and efficient management
            of assembly proceedings.
        </p>

        <div class="hero-buttons">

            <a href="login.php" class="hero-btn hero-btn-primary">
                Access e-Bunge &rarr;
            </a>

            <a href="public_portal.php" class="hero-btn hero-btn-outline">
                Explore Public Portal
            </a>

        </div>

       

    </div>

    <a href="#what-we-do" class="hero-scroll">
        Discover More ↓
    </a>

    <!-- SLIDE INDICATORS -->
    <?php if (count($slideshow_images) > 1): ?>
        <div class="slideshow-indicators">
            <?php foreach ($slideshow_images as $index => $image): ?>
                <button
                    class="slide-indicator <?= $index === 0 ? 'active' : '' ?>"
                    data-slide="<?= $index ?>"
                    aria-label="Slide <?= $index + 1 ?>">
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>


    <!-- ================= WHAT WE DO ================= -->

    <section class="what-we-do" id="what-we-do">

        <div class="landing-section-heading">

            <p class="section-eyebrow">OUR DIGITAL PLATFORM</p>

            <h2>What We Do</h2>

            <p>
                e-Bunge provides an integrated digital environment
                for managing legislative activities, improving
                institutional efficiency and promoting transparency
                within the County Assembly.
            </p>

        </div>

        <div class="what-we-do-grid">

            <article class="service-item">

                <div class="service-icon">01</div>

                <h3>Legislative Management</h3>

                <p>
                    Digitally manage motions, petitions, assembly
                    sittings and legislative documentation through
                    organized workflows.
                </p>

                <a href="#features">Learn More &rarr;</a>

            </article>

            <article class="service-item">

                <div class="service-icon">02</div>

                <h3>Electronic Voting</h3>

                <p>
                    Support secure electronic voting with Yes,
                    No and Abstain options, automatic vote tallying
                    and reliable voting records.
                </p>

                <a href="#features">Learn More &rarr;</a>

            </article>

            <article class="service-item">

                <div class="service-icon">03</div>

                <h3>Digital Accountability</h3>

                <p>
                    Improve institutional accountability through
                    audit trails, controlled information access
                    and transparent public information.
                </p>

                <a href="#features">Learn More &rarr;</a>

            </article>

        </div>

    </section>


    <!-- ================= ABOUT SECTION ================= -->

    <section class="about-section" id="about">

        <div class="about-image">
            <img
                src="assets/images/picture 1.jpg"
                alt="County Assembly legislative proceedings"
                loading="lazy"
            >
        </div>

        <div class="about-content">

            <p class="section-eyebrow">ABOUT THE PLATFORM</p>

            <h2>Modernising County Assembly Legislative Operations</h2>

            <p>
                e-Bunge is a digital County Assembly platform
                developed to improve the management of legislative
                processes and assembly proceedings.
            </p>

            <p>
                The platform brings together Members of County
                Assembly, the Speaker, the Clerk, Hansard Officers
                and committee personnel through a centralized
                digital environment.
            </p>

            <a href="login.php" class="about-btn">
                Access the Platform &rarr;
            </a>

        </div>

    </section>


    <!-- ================= PLATFORM FEATURES ================= -->

    <section class="platform-features" id="features">

        <div class="landing-section-heading">

            <p class="section-eyebrow">PLATFORM CAPABILITIES</p>

            <h2>Everything in One Digital Platform</h2>

            <p>
                Integrated tools designed to support efficient
                legislative administration and assembly operations.
            </p>

        </div>

        <div class="features-grid">

            <article class="platform-feature">
                <span class="platform-icon">01</span>
                <h3>Motion Management</h3>
                <p>
                    Submit, review, track and manage legislative
                    motions through structured workflows.
                </p>
            </article>

            <article class="platform-feature">
                <span class="platform-icon">02</span>
                <h3>Electronic Voting</h3>
                <p>
                    Conduct digital voting with automatic results
                    and recorded voting activities.
                </p>
            </article>

            <article class="platform-feature">
                <span class="platform-icon">03</span>
                <h3>Assembly Sittings</h3>
                <p>
                    Coordinate sitting schedules, attendance
                    and assembly proceedings.
                </p>
            </article>

            <article class="platform-feature">
                <span class="platform-icon">04</span>
                <h3>Petition Management</h3>
                <p>
                    Manage public petitions, submissions,
                    reviews and progress tracking.
                </p>
            </article>

            <article class="platform-feature">
                <span class="platform-icon">05</span>
                <h3>Committee Management</h3>
                <p>
                    Organise committee activities, meetings,
                    assignments and reports.
                </p>
            </article>

            <article class="platform-feature">
                <span class="platform-icon">06</span>
                <h3>Document Management</h3>
                <p>
                    Organise legislative documents, reports
                    and official assembly records.
                </p>
            </article>

        </div>

    </section>


    <!-- ================= WORKFLOW SECTION ================= -->

    <section class="workflow-section" id="workflow">

        <div class="workflow-intro">

            <p class="section-eyebrow">HOW IT WORKS</p>

            <h2>A More Efficient Legislative Process</h2>

            <p>
                e-Bunge supports the movement of legislative
                business through organized digital processes,
                from submission to final records.
            </p>

            <a href="login.php" class="about-btn">
                Get Started &rarr;
            </a>

        </div>

        <div class="workflow-list">

            <div>
                <b>01</b>
                <span>Motion Submission</span>
                <p>Members submit motions and legislative proposals.</p>
            </div>

            <div>
                <b>02</b>
                <span>Review and Approval</span>
                <p>Authorized officers review and process submissions.</p>
            </div>

            <div>
                <b>03</b>
                <span>Assembly Proceedings</span>
                <p>Members deliberate on scheduled legislative business.</p>
            </div>

            <div>
                <b>04</b>
                <span>Electronic Voting</span>
                <p>Members cast votes and the system calculates results.</p>
            </div>

            <div>
                <b>05</b>
                <span>Records and Publication</span>
                <p>Results and approved documents are securely recorded.</p>
            </div>

        </div>

    </section>


    <!-- ================= PUBLIC PARTICIPATION ================= -->

    <section class="public-participation">

        <div class="participation-content">

            <p class="section-eyebrow">PUBLIC ENGAGEMENT</p>

            <h2>Promoting Transparency and Public Participation</h2>

            <p>
                Access published assembly information, follow
                legislative activities and participate in public
                engagement through the e-Bunge public portal.
            </p>

            <a href="public_portal.php" class="hero-btn hero-btn-primary">
                Visit Public Portal &rarr;
            </a>

        </div>

    </section>


</main>
<!-- SLIDESHOW JAVASCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const slides = document.querySelectorAll('.hero-slide');
    const indicators = document.querySelectorAll('.slide-indicator');

    if (slides.length <= 1) return;

    let current = 0;
    let timer;

    function showSlide(index) {

        slides[current].classList.remove('active');

        if (indicators[current]) {
            indicators[current].classList.remove('active');
        }

        current = (index + slides.length) % slides.length;

        slides[current].classList.add('active');

        if (indicators[current]) {
            indicators[current].classList.add('active');
        }
    }

    function startSlideshow() {
        clearInterval(timer);

        timer = setInterval(function () {
            showSlide(current + 1);
        }, 6000);
    }

    indicators.forEach(function (button) {
        button.addEventListener('click', function () {
            showSlide(Number(this.dataset.slide));
            startSlideshow();
        });
    });

    startSlideshow();

});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

