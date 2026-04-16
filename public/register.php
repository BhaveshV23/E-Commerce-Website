<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/recaptcha.php';

startSecureSession();

$pageTitle = 'Create Account | UniShop';
$pageDescription = 'Create a secure UniShop customer account.';
$assetBasePath = '../assets';
$extraScripts = ['https://www.google.com/recaptcha/api.js'];

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security check failed. Please refresh the page and try again.';
    }

    if (!verifyRecaptcha($_POST['g-recaptcha-response'] ?? null)) {
        $errors[] = 'Please complete the reCAPTCHA check.';
    }

    $formData['name'] = cleanText((string) ($_POST['name'] ?? ''));
    $formData['email'] = strtolower(cleanText((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    foreach ([validateName($formData['name']), validateEmailAddress($formData['email']), validatePasswordStrength($password)] as $validationError) {
        if ($validationError !== null) {
            $errors[] = $validationError;
        }
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $emailCheck->execute([':email' => $formData['email']]);

        if ($emailCheck->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insertUser = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role)
                 VALUES (:name, :email, :password_hash, :role)'
            );

            $insertUser->execute([
                ':name' => $formData['name'],
                ':email' => $formData['email'],
                ':password_hash' => $passwordHash,
                ':role' => 'customer',
            ]);

            $_SESSION['flash_success'] = 'Account created successfully. Please sign in.';
            header('Location: login.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="auth-section" aria-labelledby="register-title">
        <div class="auth-panel">
            <p class="eyebrow">Secure registration</p>
            <h1 id="register-title">Create your UniShop account.</h1>
            <p class="auth-intro">
                Passwords are hashed with PHP's password API before storage. The original password is never saved.
            </p>

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

            <form class="auth-form" method="post" action="register.php" novalidate>
                <?php echo csrfField(); ?>

                <label for="name">Full name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?php echo htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8'); ?>"
                    minlength="2"
                    maxlength="120"
                    required
                    autocomplete="name"
                >

                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>"
                    maxlength="180"
                    required
                    autocomplete="email"
                >

                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >

                <label for="confirm_password">Confirm password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    required
                    autocomplete="new-password"
                >

                <div class="recaptcha-box">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <?php if (RECAPTCHA_SITE_KEY === 'YOUR_RECAPTCHA_V2_SITE_KEY'): ?>
                        <p class="form-help">Development mode: add real reCAPTCHA keys in config/security.php.</p>
                    <?php endif; ?>
                </div>

                <button class="button button-primary" type="submit">Create account</button>
            </form>

            <p class="auth-switch">Already registered? <a href="login.php">Sign in</a>.</p>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
