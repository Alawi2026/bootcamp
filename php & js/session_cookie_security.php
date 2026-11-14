<?php
declare(strict_types=1);



$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
);


session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_name('SECURESESSID');
session_start();


if (!isset($_COOKIE['protected_demo'])) {
    setcookie('protected_demo', 'protected-value', [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}


if (!isset($_COOKIE['unprotected_demo'])) {
    setcookie('unprotected_demo', 'unprotected-value', [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => false,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

$message = '';
$messageType = '';
$demoUsername = 'student';
$demoPassword = 'Cyber123!';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');


        if (hash_equals($demoUsername, $username) && hash_equals($demoPassword, $password)) {
            // منع Session Fixation بتجديد معرف الجلسة بعد نجاح التوثيق.
            if (session_regenerate_id(true)) {
                $_SESSION['authenticated'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['login_time'] = date('Y-m-d H:i:s');
                $message = 'تم تسجيل الدخول بنجاح وتجديد معرف الجلسة.';
                $messageType = 'success';
            } else {
                $message = 'تعذر تجديد معرف الجلسة.';
                $messageType = 'error';
            }
        } else {
            $message = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
            $messageType = 'error';
        }
    } elseif ($action === 'logout') {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Strict',
            ]);
        }

        session_destroy();
        $message = 'تم تسجيل الخروج وإنهاء الجلسة.';
        $messageType = 'success';
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$isAuthenticated = !empty($_SESSION['authenticated']);
$sessionCookieParams = session_get_cookie_params();
$sessionIdForDisplay = session_id();


$secureLabel = $isHttps ? 'مفعّل (HTTPS)' : 'غير مفعّل (التشغيل الحالي ليس HTTPS)';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الجلسات والكوكيز الآمنة</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 28px 15px; min-height: 100vh;
            background: #f2f5fa; color: #202938;
            font-family: Tahoma, Arial, sans-serif;
        }
        .container {
            max-width: 850px; margin: auto; background: #fff; padding: 28px;
            border-radius: 15px; box-shadow: 0 8px 30px rgba(0,0,0,.07);
        }
        h1 { margin-top: 0; color: #173e6c; }
        h2 { font-size: 19px; color: #173e6c; }
        p { line-height: 1.8; }
        .card { border: 1px solid #e0e6ee; border-radius: 11px; padding: 18px; margin: 17px 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; }
        label { display: block; margin: 10px 0 6px; font-weight: bold; }
        input {
            width: 100%; padding: 11px; border: 1px solid #cbd5e1;
            border-radius: 8px; font-size: 15px;
        }
        button {
            border: 0; border-radius: 8px; padding: 11px 18px; margin-top: 12px;
            background: #1764ad; color: #fff; cursor: pointer; font-size: 15px;
        }
        button.secondary { background: #596579; }
        .message { padding: 13px; border-radius: 8px; margin: 15px 0; }
        .success { background: #e8f7ed; color: #176534; }
        .error { background: #fff0f0; color: #992b2b; }
        .status { background: #f5f8fc; padding: 12px; border-radius: 8px; overflow-wrap: anywhere; }
        .good { color: #14743a; font-weight: bold; }
        .bad { color: #a52d2d; font-weight: bold; }
        code { direction: ltr; display: inline-block; }
        .hint { color: #667085; font-size: 13px; }
        ul { line-height: 2; }
    </style>
</head>
<body>
<main class="container">
    <h1>أداة إدارة الجلسات والكوكيز الآمنة</h1>
    <p>
        تطبيق تعليمي يوضح إعداد خصائص أمان الجلسات والكوكيز في PHP،
        وتجديد معرف الجلسة بعد تسجيل الدخول، والفرق بين الكوكي المحمية وغير المحمية.
    </p>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType === 'success' ? 'success' : 'error' ?>" role="status">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>1. تسجيل الدخول التجريبي</h2>
        <?php if ($isAuthenticated): ?>
            <p class="good">تم تسجيل الدخول باسم: <?= e((string) $_SESSION['username']) ?></p>
            <p>وقت تسجيل الدخول: <?= e((string) ($_SESSION['login_time'] ?? '')) ?></p>
            <form method="post">
                <input type="hidden" name="action" value="logout">
                <button class="secondary" type="submit">تسجيل الخروج</button>
            </form>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <label for="username">اسم المستخدم</label>
                <input id="username" name="username" autocomplete="username" required>
                <label for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button type="submit">تسجيل الدخول</button>
            </form>
            <p class="hint">بيانات الدخول التجريبية: student / Cyber123!</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>2. حالة إعدادات كوكي الجلسة</h2>
        <div class="grid">
            <div class="status">
                <strong>HttpOnly:</strong>
                <span class="good"><?= $sessionCookieParams['httponly'] ? 'مفعّل' : 'غير مفعّل' ?></span>
            </div>
            <div class="status">
                <strong>Secure:</strong>
                <span class="<?= $sessionCookieParams['secure'] ? 'good' : 'bad' ?>"><?= e($secureLabel) ?></span>
            </div>
            <div class="status">
                <strong>SameSite:</strong>
                <span class="good"><?= e($sessionCookieParams['samesite'] ?? 'غير محدد') ?></span>
            </div>
            <div class="status">
                <strong>Path:</strong> <?= e($sessionCookieParams['path']) ?>
            </div>
        </div>
        <p class="hint">
            تم ضبط هذه الخصائص قبل استدعاء session_start().
            يتم تفعيل Secure تلقائيًا عند تشغيل التطبيق عبر HTTPS.
        </p>
    </section>

    <section class="card">
        <h2>3. اختبار قراءة الكوكيز باستخدام JavaScript</h2>
        <p>
            تحاول الشيفرة التالية قراءة الكوكي التجريبية المحمية وغير المحمية
            من خلال document.cookie.
        </p>
        <div class="grid">
            <div class="status">
                <strong>الكوكي المحمية (HttpOnly):</strong>
                <p id="protected-result" class="hint">جارٍ الفحص...</p>
            </div>
            <div class="status">
                <strong>الكوكي غير المحمية:</strong>
                <p id="unprotected-result" class="hint">جارٍ الفحص...</p>
            </div>
        </div>
        <button type="button" onclick="checkCookies()">إعادة فحص الكوكيز</button>
    </section>

    <section class="card">
        <h2>4. معلومات الجلسة</h2>
        <p><strong>اسم الجلسة:</strong> <?= e(session_name()) ?></p>
        <p><strong>معرف الجلسة الحالي:</strong> <code><?= e($sessionIdForDisplay) ?></code></p>
        <p class="hint">
            عند نجاح تسجيل الدخول ينفذ التطبيق session_regenerate_id(true)،
            فيتغير معرف الجلسة لمنع Session Fixation.
        </p>
    </section>

    <section class="card">
        <h2>5. خصائص الحماية المطبقة</h2>
        <ul>
            <li><code>HttpOnly = true</code> لمنع JavaScript من قراءة كوكي الجلسة.</li>
            <li><code>SameSite = Strict</code> للحد من إرسال الكوكي في الطلبات العابرة للمواقع.</li>
            <li><code>Secure</code> عند استخدام HTTPS لحماية نقل الكوكي.</li>
            <li><code>session_regenerate_id(true)</code> بعد نجاح تسجيل الدخول.</li>
        </ul>
    </section>
</main>

<script>
function getCookie(name) {
    const prefix = name + '=';
    const cookies = document.cookie ? document.cookie.split('; ') : [];

    for (const cookie of cookies) {
        if (cookie.startsWith(prefix)) {
            return decodeURIComponent(cookie.substring(prefix.length));
        }
    }
    return null;
}

function checkCookies() {
    const protectedCookie = getCookie('protected_demo');
    const unprotectedCookie = getCookie('unprotected_demo');

    const protectedResult = document.getElementById('protected-result');
    const unprotectedResult = document.getElementById('unprotected-result');

    protectedResult.textContent = protectedCookie === null
        ? 'غير قابلة للقراءة بواسطة JavaScript (HttpOnly يعمل).'
        : 'تمت قراءتها: ' + protectedCookie;

    protectedResult.className = protectedCookie === null ? 'good' : 'bad';

    unprotectedResult.textContent = unprotectedCookie === null
        ? 'لم يتم العثور عليها. أعد تحميل الصفحة بعد استلام الكوكي.'
        : 'تمت قراءتها: ' + unprotectedCookie;

    unprotectedResult.className = unprotectedCookie !== null ? 'good' : 'bad';
}

document.addEventListener('DOMContentLoaded', checkCookies);
</script>
</body>
</html>
