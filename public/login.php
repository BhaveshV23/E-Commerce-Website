<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/recaptcha.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security_logger.php';

startSecureSession();

$pageTitle = 'Sign In | UniShop';
$pageDescription = 'Sign in securely to your UniShop account.';
$assetBasePath = '../assets';
$extraScripts = ['https://www.google.com/recaptcha/api.js'];

$errors = [];
$email = '';
$successMessage = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security check failed. Please refresh the page and try again.';
        writeSecurityLog('failed_login_attempt', [
            'reason' => 'invalid_csrf_token',
            'email' => 'unvalidated',
        ]);
    }

    if (!verifyRecaptcha($_POST['g-recaptcha-response'] ?? null)) {
        $errors[] = 'Please complete the reCAPTCHA check.';
        writeSecurityLog('failed_login_attempt', [
            'reason' => 'recaptcha_failed',
            'email' => 'unvalidated',
        ]);
    }

    $email = strtolower(cleanText((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (validateEmailAddress($email) !== null || $password === '') {
        $errors[] = 'Invalid email or password.';
        writeSecurityLog('failed_login_attempt', [
            'reason' => 'invalid_form_input',
            'email' => sanitizeLogValue($email, 180),
        ]);
    }

    if ($errors === []) {
        $userStmt = $pdo->prepare(
            'SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $userStmt->execute([':email' => $email]);
        $user = $userStmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Keep the message generic so attackers cannot enumerate registered emails.
            $errors[] = 'Invalid email or password.';
            writeSecurityLog('failed_login_attempt', [
                'reason' => 'invalid_credentials',
                'email' => sanitizeLogValue($email, 180),
            ]);
        } else {
            loginUser($user);
            header('Location: account.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="auth-section" aria-labelledby="login-title">
        <div class="auth-panel">
            <p class="eyebrow">Secure login</p>
            <h1 id="login-title">Sign in to UniShop.</h1>
            <p class="auth-intro">
                Login verifies the submitted password against the stored hash and refreshes the session after success.
            </p>

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success" role="status">
                    <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="alert alert-error" role="alert">
                    <p>Please fix the following:</p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="login.php" novalidate>
                <?php echo csrfField(); ?>

                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="180"
                    required
                    autocomplete="email"
                >

                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                >

                <div class="recaptcha-box">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <?php if (RECAPTCHA_SITE_KEY === 'YOUR_RECAPTCHA_V2_SITE_KEY'): ?>
                        <p class="form-help">Development mode: add real reCAPTCHA keys in config/security.php.</p>
                    <?php endif; ?>
                </div>

                <button class="button button-primary" type="submit">Sign in</button>
            </form>

            <p class="auth-switch">New to UniShop? <a href="register.php">Create an account</a>.</p>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
