<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Metoda niedozwolona.'
    ]);
    exit;
}

function sanitize_text(string $value): string {
    $value = trim($value);
    $value = str_replace(["\r", "\n"], ' ', $value);
    return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
}

function sanitize_multiline(string $value): string {
    $value = trim($value);
    return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
}

if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$name = sanitize_text($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = sanitize_text($_POST['phone'] ?? '');
$business = sanitize_multiline($_POST['business'] ?? '');
$challenge = sanitize_multiline($_POST['challenge'] ?? '');
$outcome = sanitize_multiline($_POST['outcome'] ?? '');

if (
    $name === '' ||
    $email === false ||
    $business === '' ||
    $challenge === '' ||
    $outcome === ''
) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Uzupełnij wszystkie wymagane pola.'
    ]);
    exit;
}

$to = 'info@panirzecznik.pl';
$subject = 'Nowe zgłoszenie konsultacji - pani rzecznik';

$messageLines = [
    'Nowe zgłoszenie z formularza konsultacji:',
    '',
    'Imię: ' . $name,
    'E-mail: ' . $email,
    'Telefon: ' . ($phone !== '' ? $phone : 'Brak'),
    '',
    'Czym się zajmuje:',
    $business,
    '',
    'Największy problem z nagraniami:',
    $challenge,
    '',
    'Co chce osiągnąć:',
    $outcome,
    '',
    'Data: ' . date('Y-m-d H:i:s'),
    'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'nieznane'),
];

$message = implode(PHP_EOL, $messageLines);

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Formularz konsultacji <no-reply@panirzecznik.pl>',
    'Reply-To: ' . $email,
];

$mailSent = @mail($to, $subject, $message, implode("\r\n", $headers));

$csvPath = __DIR__ . '/leads.csv';
$csvRow = [
    date('Y-m-d H:i:s'),
    $name,
    $email,
    $phone,
    $business,
    $challenge,
    $outcome,
    $_SERVER['REMOTE_ADDR'] ?? ''
];

$csvSaved = false;
$fp = @fopen($csvPath, 'ab');
if ($fp) {
    $csvSaved = fputcsv($fp, $csvRow) !== false;
    fclose($fp);
}

if (!$mailSent && !$csvSaved) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Nie udało się zapisać zgłoszenia.'
    ]);
    exit;
}

echo json_encode(['ok' => true]);
