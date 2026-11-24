
<?php
require_once __DIR__ . '/config.php';

$title = 'Public portal';
$root_prefix = '';
$asset_prefix = '';

include __DIR__ . '/includes/header.php';

/*
|--------------------------------------------------------------------------
| SEARCH INPUTS
|--------------------------------------------------------------------------
*/
$search = trim($_GET['search'] ?? '');
$category = strtolower(trim($_GET['category'] ?? 'all'));

$allowedCategories = [
    'all',
    'motions',
    'committees',
    'sittings',
    'meetings',
    'petitions',
    'reports'
];

if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

$publicResults = [];
$searchPerformed = ($search !== '');
$searchError = false;

/*
|--------------------------------------------------------------------------
| SEARCH HELPER
|--------------------------------------------------------------------------
*/
function publicSearch(PDO $pdo, string $sql, string $term): array
{
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':s1' => $term,
        ':s2' => $term,
        ':s3' => $term,
        ':s4' => $term
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| SEARCH PUBLIC RECORDS
|--------------------------------------------------------------------------
| Nothing is retrieved or displayed until a visitor submits a keyword.
|--------------------------------------------------------------------------
*/
if ($searchPerformed) {

    $term = '%' . $search . '%';

    try {

        /*
        |--------------------------------------------------------------------------
        | APPROVED MOTIONS
        |--------------------------------------------------------------------------
        */
        if ($category === 'all' || $category === 'motions') {

            $rows = publicSearch(
                db(),
                "
                SELECT
                    id,
                    motion_topic,
                    motion_details,
                    proposed_resolution,
                    area_of_concern,
                    created_at
                FROM motions
                WHERE LOWER(status) = 'approved'
                AND (
                    motion_topic LIKE :s1
                    OR motion_details LIKE :s2
                    OR proposed_resolution LIKE :s3
                    OR area_of_concern LIKE :s4
                )
                ORDER BY id DESC
                LIMIT 50
                ",
                $term
            );

            foreach ($rows as $row) {
                $publicResults[] = [
                    'type' => 'Approved motion',
                    'title' => $row['motion_topic'] ?: 'Untitled motion',
                    'description' => $row['motion_details'] ?? '',
                    'extra' => !empty($row['proposed_resolution'])
                        ? 'Proposed resolution: ' . $row['proposed_resolution']
                        : '',
                    'date' => $row['created_at'] ?? ''
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVED COMMITTEES
        |--------------------------------------------------------------------------
        */
        if ($category === 'all' || $category === 'committees') {

            $rows = publicSearch(
                db(),
                "
                SELECT
                    id,
                    committee_name,
                    committee_type,
                    status,
                    created_at
                FROM committees
                WHERE LOWER(approval_status) = 'approved'
                AND LOWER(status) = 'active'
                AND (
                    committee_name LIKE :s1
                    OR committee_type LIKE :s2
                    OR status LIKE :s3
                    OR committee_name LIKE :s4
                )
                ORDER BY id DESC
                LIMIT 50
                ",
                $term
            );

            foreach ($rows as $row) {
                $publicResults[] = [
                    'type' => 'Committee',
                    'title' => $row['committee_name'],
                    'description' => 'Committee type: ' .
                        ($row['committee_type'] ?? 'Not specified'),
                    'extra' => 'Status: ' . ($row['status'] ?? 'Active'),
                    'date' => $row['created_at'] ?? ''
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SCHEDULED SITTINGS
        |--------------------------------------------------------------------------
        */
        if ($category === 'all' || $category === 'sittings') {

            $rows = publicSearch(
                db(),
                "
                SELECT
                    id,
                    title,
                    sitting_date,
                    venue,
                    agenda,
                    purpose,
                    sitting_type,
                    start_time,
                    mode,
                    status
                FROM sittings
                WHERE LOWER(status) IN ('approved', 'scheduled')
                AND (
                    title LIKE :s1
                    OR venue LIKE :s2
                    OR agenda LIKE :s3
                    OR purpose LIKE :s4
                )
                ORDER BY sitting_date DESC
                LIMIT 50
                ",
                $term
            );

            foreach ($rows as $row) {

                $description = $row['agenda']
                    ?: ($row['purpose'] ?? '');

                $publicResults[] = [
                    'type' => 'Scheduled sitting',
                    'title' => $row['title'],
                    'description' => $description,
                    'extra' =>
                        'Date: ' . ($row['sitting_date'] ?? '') .
                        ' | Time: ' . ($row['start_time'] ?? '') .
                        ' | Venue: ' . ($row['venue'] ?? '') .
                        ' | Mode: ' . ($row['mode'] ?? ''),
                    'date' => $row['sitting_date'] ?? ''
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SCHEDULED COMMITTEE MEETINGS
        |--------------------------------------------------------------------------
        */
        if ($category === 'all' || $category === 'meetings') {

            $rows = publicSearch(
                db(),
                "
                SELECT
                    cm.id,
                    cm.meeting_title,
                    cm.meeting_date,
                    cm.start_time,
                    cm.venue,
                    cm.agenda,
                    cm.status,
                    c.committee_name
                FROM committee_meetings cm
                INNER JOIN committees c
                    ON c.id = cm.committee_id
                WHERE LOWER(cm.status) = 'scheduled'
                AND LOWER(c.approval_status) = 'approved'
                AND LOWER(c.status) = 'active'
                AND (
                    cm.meeting_title LIKE :s1
                    OR cm.venue LIKE :s2
                    OR cm.agenda LIKE :s3
                    OR c.committee_name LIKE :s4
                )
                ORDER BY cm.meeting_date DESC
                LIMIT 50
                ",
                $term
            );

            foreach ($rows as $row) {
                $publicResults[] = [
                    'type' => 'Scheduled committee meeting',
                    'title' => $row['meeting_title'],
                    'description' => $row['agenda'] ?? '',
                    'extra' =>
                        'Committee: ' . $row['committee_name'] .
                        ' | Date: ' . ($row['meeting_date'] ?? '') .
                        ' | Time: ' . ($row['start_time'] ?? '') .
                        ' | Venue: ' . ($row['venue'] ?? ''),
                    'date' => $row['meeting_date'] ?? ''
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVED PETITIONS
        |--------------------------------------------------------------------------
        | Petitioner names and email addresses are not displayed.
        |--------------------------------------------------------------------------
        */
        if ($category === 'all' || $category === 'petitions') {

            $rows = publicSearch(
                db(),
                "
                SELECT
                    id,
                    subject,
                    details,
                    created_at
                FROM petitions
                WHERE LOWER(status) = 'approved'
                AND (
                    subject LIKE :s1
                    OR details LIKE :s2
                    OR subject LIKE :s3
                    OR details LIKE :s4
                )
                ORDER BY id DESC
                LIMIT 50
                ",
                $term
            );

            foreach ($rows as $row) {
                $publicResults[] = [
                    'type' => 'Approved petition',
                    'title' => $row['subject'],
                    'description' => $row['details'],
                    'extra' => '',
                    'date' => $row['created_at'] ?? ''
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | APPROVED REPORTS
        |--------------------------------------------------------------------------
        */
        if ($category === 'all' || $category === 'reports') {

            $rows = publicSearch(
                db(),
                "
                SELECT
                    id,
                    title,
                    category,
                    content,
                    created_at
                FROM reports
                WHERE LOWER(status) = 'approved'
                AND (
                    title LIKE :s1
                    OR category LIKE :s2
                    OR content LIKE :s3
                    OR title LIKE :s4
                )
                ORDER BY id DESC
                LIMIT 50
                ",
                $term
            );

            foreach ($rows as $row) {
                $publicResults[] = [
                    'type' => 'Approved report',
                    'title' => $row['title'],
                    'description' => $row['content'],
                    'extra' => 'Category: ' . ($row['category'] ?? ''),
                    'date' => $row['created_at'] ?? ''
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SORT RESULTS BY DATE
        |--------------------------------------------------------------------------
        */
        usort($publicResults, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

    } catch (PDOException $e) {

        $searchError = true;
        $publicResults = [];

        // Log the actual database error for troubleshooting.
        error_log('Public portal search error: ' . $e->getMessage());
    }
}
?>

<main class="public-portal">

    <!-- =====================================================
         PUBLIC PORTAL HERO
    ====================================================== -->
    <section class="portal-hero">

        <!-- COUNTY ASSEMBLY LOGO -->
      <a href="landing.php" class="portal-logo">
    <img
        src="assets/images/logo.png"
        alt="Elgeyo Marakwet County Assembly Logo"
    >
</a>

        <p class="eyebrow">OPEN ASSEMBLY INFORMATION</p>

        <h1>Public information  portal</h1>

        <p>
            Search public legislative information and submit a petition.
        </p>

        <a class="btn btn-primary" href="#petition">
            Submit a petition <span>&rarr;</span>
        </a>

    </section>


    <!-- =====================================================
         TWO-COLUMN PUBLIC PORTAL LAYOUT
    ====================================================== -->
    <section class="public-grid">

        <!-- =================================================
             LEFT COLUMN: PUBLIC SEARCH
        ================================================== -->
        <div class="public-motions-section">

            <div class="section-head">
                <div>
                    <p class="eyebrow">PUBLIC INFORMATION</p>
                    <h2>Search Assembly Records</h2>
                </div>
            </div>

            <!-- SEARCH FORM -->
            <form
                method="GET"
                action="public_portal.php"
                class="public-motion-search"
            >

                <div class="motion-search-box">

                    <!-- SEARCH INPUT -->
                    <input
                        type="search"
                        name="search"
                        class="public-search-input"
                        placeholder="Enter a keyword to search..."
                        value="<?= e($search) ?>"
                        aria-label="Search public Assembly records"
                        required
                    >

                    <!-- CATEGORY DROPDOWN -->
                    <select
                        name="category"
                        class="public-search-category"
                        aria-label="Select search category"
                    >

                        <option value="all"
                            <?= $category === 'all' ? 'selected' : '' ?>>
                            All records
                        </option>

                        <option value="motions"
                            <?= $category === 'motions' ? 'selected' : '' ?>>
                            Motions
                        </option>

                        <option value="committees"
                            <?= $category === 'committees' ? 'selected' : '' ?>>
                            Committees
                        </option>

                        <option value="sittings"
                            <?= $category === 'sittings' ? 'selected' : '' ?>>
                            Scheduled sittings
                        </option>

                        <option value="meetings"
                            <?= $category === 'meetings' ? 'selected' : '' ?>>
                            Committee meetings
                        </option>

                        <option value="petitions"
                            <?= $category === 'petitions' ? 'selected' : '' ?>>
                            Petitions
                        </option>

                        <option value="reports"
                            <?= $category === 'reports' ? 'selected' : '' ?>>
                            Reports
                        </option>

                    </select>

                    <!-- SEARCH BUTTON -->
                    <button
                        type="submit"
                        class="btn btn-primary public-search-button"
                    >
                        Search
                    </button>

                </div>

                <?php if ($searchPerformed): ?>

                    <div class="search-feedback">

                        Results for:
                        <strong><?= e($search) ?></strong>

                        <a href="public_portal.php">
                            Clear search
                        </a>

                    </div>

                <?php endif; ?>

            </form>


            <!-- =================================================
                 SEARCH RESULTS
            ================================================== -->
            <div class="public-motion-results">

                <?php if (!$searchPerformed): ?>

                    <div class="public-card empty-state">

                        <h3>Search Assembly information</h3>

                        <p>
                            Enter a keyword and select a category to find
                            approved motions, committees, scheduled sittings,
                            petitions, reports and other public records.
                        </p>

                    </div>

                <?php elseif ($searchError): ?>

                    <div class="public-card empty-state">

                        <h3>Search could not be completed</h3>

                        <p>
                            There was a problem retrieving the records.
                            Please try again.
                        </p>

                    </div>

                <?php elseif (!empty($publicResults)): ?>

                    <p class="eyebrow">
                        <?= count($publicResults) ?> matching record(s) found
                    </p>

                    <?php foreach ($publicResults as $result): ?>

                        <article class="public-card">

                            <span class="public-label">
                                <?= e($result['type']) ?>
                            </span>

                            <h3>
                                <?= e($result['title']) ?>
                            </h3>

                            <?php if (!empty($result['description'])): ?>

                                <p>
                                    <?= nl2br(e($result['description'])) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($result['extra'])): ?>

                                <div class="public-motion-resolution">
                                    <?= nl2br(e($result['extra'])) ?>
                                </div>

                            <?php endif; ?>

                            <?php if (!empty($result['date'])): ?>

                                <small>
                                    Date: <?= e($result['date']) ?>
                                </small>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="public-card empty-state">

                        <h3>No matching public records found</h3>

                        <p>
                            No records matched
                            "<?= e($search) ?>".
                            Try another keyword or select a different category.
                        </p>

                        <a
                            href="public_portal.php"
                            class="btn btn-primary"
                        >
                            Clear search
                        </a>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- =================================================
             RIGHT COLUMN: PETITION FORM
        ================================================== -->
        <div>

            <div class="section-head">

                <div>
                    <p class="eyebrow">PUBLIC PARTICIPATION</p>
                    <h2>Submit a petition</h2>
                </div>

            </div>

            <section class="panel" id="petition">

                <form
                    method="post"
                    action="backend/public_petition.php"
                >

                    <input
                        type="hidden"
                        name="csrf"
                        value="<?= e(csrf_token()) ?>"
                    >

                    <label>
                        Your name

                        <input
                            type="text"
                            name="petitioner_name"
                            required
                        >
                    </label>

                    <label>
                        Email address

                        <input
                            type="email"
                            name="petitioner_email"
                            required
                        >
                    </label>

                    <label>
                        Petition subject

                        <input
                            type="text"
                            name="subject"
                            required
                        >
                    </label>

                    <label>
                        Petition details

                        <textarea
                            name="details"
                            rows="6"
                            required
                        ></textarea>
                    </label>

                    <button
                        class="btn btn-primary btn-block"
                        type="submit"
                    >
                        Submit petition
                    </button>

                    <small class="form-note">
                        Your petition will be reviewed by the Hansard Office
                        and the Office of the Speaker.
                    </small>

                </form>

            </section>

        </div>

    </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
