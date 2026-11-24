<?php 
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../public_portal.php');
}

verify_csrf();

if (!filter_var($_POST['petitioner_email'], FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Enter a valid email address.');
    redirect('../public_portal.php#petition');
}

$stmt = db()->prepare('INSERT INTO petitions(subject, details, petitioner_name, petitioner_email, source) VALUES(?, ?, ?, ?, "public")');
$stmt->execute([
    $_POST['subject'],
    $_POST['details'],
    $_POST['petitioner_name'],
    $_POST['petitioner_email']
]);

flash('success', 'Your petition has been submitted for review.');
redirect('../public_portal.php#petition');