<?php


declare(strict_types=1);


function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$users = [];
$errorMessage = '';
$queryDescription = '';
$submitted = isset($_GET['submit']);
$username = isset($_GET['username']) && is_string($_GET['username'])
    ? trim($_GET['username'])
    : '';
$mode = isset($_GET['mode']) && is_string($_GET['mode'])
    ? $_GET['mode']
    : 'safe';

if (!in_array($mode, ['safe', 'unsafe'], true)) {
    $mode = 'safe';
}

try {
    $pdo = new PDO('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(
        'CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL
        )'
    );

    $insert = $pdo->prepare(
        'INSERT INTO users (username, email) VALUES (:username, :email)'
    );

    $sampleUsers = [
        ['username' => 'ali',   'email' => 'ali@example.test'],
        ['username' => 'sara',  'email' => 'sara@example.test'],
        ['username' => 'ahmed', 'email' => 'ahmed@example.test'],
        ['username' => 'noor',  'email' => 'noor@example.test'],
    ];

    foreach ($sampleUsers as $sampleUser) {
        $insert->execute($sampleUser);
    }

    if ($submitted) {
        if ($mode === 'unsafe') {
            $sql = "SELECT id, username, email FROM users WHERE username = '$username'";
            $queryDescription = $sql;
            $statement = $pdo->query($sql);
            $users = $statement->fetchAll();
        } else {
            $sql = 'SELECT id, username, email FROM users WHERE username = :username';
            $queryDescription = $sql . '  |  قيمة username تُرسل كمعامل منفصل.';
            $statement = $pdo->prepare($sql);
            $statement->execute(['username' => $username]);
            $users = $statement->fetchAll();
        }
    }
} catch (Throwable $exception) {
    error_log('SQL Injection Defense demo error: ' . $exception->getMessage());
    $errorMessage = 'تعذر تنفيذ العملية. تحقق من إعداد PHP وامتداد PDO_SQLITE ثم حاول مرة أخرى.';
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التكليف الحادي عشر | الحماية من SQL Injection</title>
    <style>
        :root {
            color-scheme: light;
            --background: #f3f6fb;
            --surface: #ffffff;
            --text: #172033;
            --muted: #64748b;
            --border: #dbe3ef;
            --primary: #2457a7;
            --danger: #a83232;
            --success: #187044;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 28px 16px;
            background: var(--background);
            color: var(--text);
            font-family: Tahoma, Arial, sans-serif;
            line-height: 1.8;
        }
        .container { width: min(900px, 100%); margin: 0 auto; }
        .header, .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 18px;
        }
        h1 { margin: 0 0 8px; font-size: 25px; }
        h2 { margin: 0 0 14px; font-size: 19px; }
        p { margin: 8px 0; }
        .muted { color: var(--muted); }
        .warning {
            padding: 12px 14px;
            border-right: 4px solid var(--danger);
            background: #fff2f2;
            border-radius: 8px;
            color: #762727;
        }
        form { display: grid; gap: 14px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #fff;
            color: var(--text);
            font: inherit;
        }
        button {
            border: 0;
            border-radius: 8px;
            padding: 11px 18px;
            background: var(--primary);
            color: #fff;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover { filter: brightness(.93); }
        code, pre {
            direction: ltr;
            text-align: left;
            font-family: Consolas, monospace;
            overflow-wrap: anywhere;
        }
        .code-box {
            padding: 12px;
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow-wrap: anywhere;
        }
        .status-safe { color: var(--success); font-weight: bold; }
        .status-unsafe { color: var(--danger); font-weight: bold; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 420px; }
        th, td { padding: 10px; border-bottom: 1px solid var(--border); text-align: right; }
        th { background: #f8fafc; }
        .empty { padding: 14px; background: #f8fafc; border-radius: 8px; }
        .footer { text-align: center; color: var(--muted); font-size: 13px; }
        @media (max-width: 600px) {
            body { padding: 14px 10px; }
            .header, .panel { padding: 16px; }
            h1 { font-size: 21px; }
        }
    </style>
</head>
<body>
<main class="container">
    <header class="header">
        <h1>أداة الحماية من ثغرات SQL Injection</h1>
        <p class="muted">التكليف الحادي عشر — مقارنة عملية بين الاستعلام غير الآمن والاستعلام الآمن باستخدام PDO.</p>
        <p class="warning">
            تنبيه تعليمي: وضع الاستعلام غير الآمن مخصص لتجربة محلية على بيانات وهمية فقط،
            ولا يجوز استخدامه في التطبيقات الحقيقية.
        </p>
    </header>

    <section class="panel">
        <h2>اختبار البحث عن مستخدم</h2>
        <form method="get" action="">
            <div>
                <label for="username">اسم المستخدم أو مدخل الاختبار</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= e($username) ?>"
                    placeholder="مثال: ali"
                    autocomplete="off"
                >
            </div>
            <div>
                <label for="mode">طريقة تنفيذ الاستعلام</label>
                <select id="mode" name="mode">
                    <option value="safe" <?= $mode === 'safe' ? 'selected' : '' ?>>الوضع الآمن — Prepared Statements</option>
                    <option value="unsafe" <?= $mode === 'unsafe' ? 'selected' : '' ?>>الوضع غير الآمن — دمج النصوص مباشرة (للتجربة)</option>
                </select>
            </div>
            <button type="submit" name="submit" value="1">تنفيذ البحث</button>
        </form>
        <p class="muted">بيانات الاختبار: ali، sara، ahmed، noor.</p>
    </section>

    <?php if ($errorMessage !== ''): ?>
        <section class="panel" role="alert">
            <p><?= e($errorMessage) ?></p>
        </section>
    <?php elseif ($submitted): ?>
        <section class="panel">
            <h2>نتيجة الاختبار</h2>
            <?php if ($mode === 'safe'): ?>
                <p class="status-safe">الوضع الآمن: تم تمرير المدخل كقيمة مستقلة عن أمر SQL.</p>
            <?php else: ?>
                <p class="status-unsafe">الوضع غير الآمن: تم دمج المدخل مباشرة داخل أمر SQL، وهذا يسمح بحقن SQL.</p>
            <?php endif; ?>

            <p><strong>الاستعلام المستخدم:</strong></p>
            <div class="code-box"><code><?= e($queryDescription) ?></code></div>

            <p><strong>عدد السجلات المسترجعة:</strong> <?= count($users) ?></p>
            <?php if (count($users) > 0): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>رقم المستخدم</th>
                                <th>اسم المستخدم</th>
                                <th>البريد الإلكتروني</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= e((string) $user['id']) ?></td>
                                <td><?= e((string) $user['username']) ?></td>
                                <td><?= e((string) $user['email']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty">لم يتم العثور على أي مستخدم يطابق المدخل.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>طريقة اختبار SQL Injection</h2>
        <p>انسخ المدخل التالي وضعه في حقل اسم المستخدم، ثم جرّبه مرة في الوضع غير الآمن ومرة في الوضع الآمن:</p>
        <div class="code-box"><code>' OR '1'='1</code></div>
        <p><strong>النتيجة المتوقعة:</strong></p>
        <ul>
            <li>في الوضع غير الآمن: يصبح شرط SQL صحيحًا دائمًا، فتظهر سجلات المستخدمين الأربعة.</li>
            <li>في الوضع الآمن: يُعامل المدخل كنص عادي، فلا يظهر أي مستخدم بهذا الاسم.</li>
        </ul>
    </section>

    <p class="footer">قاعدة البيانات SQLite داخل الذاكرة — تُنشأ بيانات الاختبار من جديد عند كل طلب.</p>
</main>
</body>
</html>
