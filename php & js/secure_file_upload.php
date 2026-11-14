<?php
declare(strict_types=1);


const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB


const ALLOWED_TYPES = [
    'jpg' => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png' => ['image/png'],
    'pdf' => ['application/pdf'],
];

$storageDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'private_uploads';
$message = '';
$messageType = '';


if (!is_dir($storageDirectory)) {
    if (!mkdir($storageDirectory, 0700, true) && !is_dir($storageDirectory)) {
        $message = 'تعذر تجهيز مجلد التخزين. يرجى مراجعة صلاحيات الخادم.';
        $messageType = 'error';
    }
}

if (is_dir($storageDirectory)) {
    @chmod($storageDirectory, 0700);


    $htaccessPath = $storageDirectory . DIRECTORY_SEPARATOR . '.htaccess';
    if (!file_exists($htaccessPath)) {
        $rules = "Options -Indexes\n<FilesMatch \"\\.(php[0-9]?|phtml|phar|cgi|pl|py|sh)$\">\nRequire all denied\n</FilesMatch>\n";
        @file_put_contents($htaccessPath, $rules, LOCK_EX);
    }


    $indexPath = $storageDirectory . DIRECTORY_SEPARATOR . 'index.php';
    if (!file_exists($indexPath)) {
        @file_put_contents($indexPath, "<?php http_response_code(404); exit;\n", LOCK_EX);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message === '') {
    if (!isset($_FILES['uploaded_file']) || !is_array($_FILES['uploaded_file'])) {
        $message = 'لم يتم اختيار ملف للرفع.';
        $messageType = 'error';
    } else {
        $file = $_FILES['uploaded_file'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => 'حجم الملف يتجاوز الحد الذي يسمح به الخادم.',
                UPLOAD_ERR_FORM_SIZE  => 'حجم الملف يتجاوز الحد المحدد في النموذج.',
                UPLOAD_ERR_PARTIAL    => 'لم يكتمل رفع الملف.',
                UPLOAD_ERR_NO_FILE    => 'يرجى اختيار ملف أولاً.',
                UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة غير متوفر على الخادم.',
                UPLOAD_ERR_CANT_WRITE => 'تعذر حفظ الملف المؤقت على الخادم.',
                UPLOAD_ERR_EXTENSION  => 'أوقف أحد امتدادات PHP عملية رفع الملف.',
            ];
            $message = $uploadErrors[$file['error']] ?? 'حدث خطأ أثناء رفع الملف.';
            $messageType = 'error';
        } elseif (
            !isset($file['tmp_name'], $file['name'], $file['size']) ||
            !is_string($file['tmp_name']) ||
            !is_string($file['name']) ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            $message = 'الملف المرسل غير صالح.';
            $messageType = 'error';
        } else {
            $originalName = $file['name'];
            $fileSize = (int) $file['size'];
            $temporaryPath = $file['tmp_name'];


            if ($fileSize <= 0 || $fileSize > MAX_FILE_SIZE) {
                $message = 'تم رفض الملف: الحجم يجب أن يكون أكبر من صفر وألا يتجاوز 5 MB.';
                $messageType = 'error';
            } else {

                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if (!array_key_exists($extension, ALLOWED_TYPES)) {
                    $message = 'تم رفض الملف: امتداده غير مسموح. الامتدادات المسموحة: JPG وPNG وPDF.';
                    $messageType = 'error';
                } elseif (!class_exists('finfo')) {
                    $message = 'ميزة Fileinfo غير مفعلة على الخادم.';
                    $messageType = 'error';
                } else {

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $detectedMime = $finfo->file($temporaryPath);

                    if (!is_string($detectedMime) || !in_array($detectedMime, ALLOWED_TYPES[$extension], true)) {
                        $message = 'تم رفض الملف: محتواه الفعلي لا يتطابق مع امتداده.';
                        $messageType = 'error';
                    } else {

                        $contentIsValid = true;

                        if (in_array($extension, ['jpg', 'jpeg'], true)) {
                            $imageInfo = @getimagesize($temporaryPath);
                            $contentIsValid = is_array($imageInfo) && ($imageInfo['mime'] ?? '') === 'image/jpeg';
                        } elseif ($extension === 'png') {
                            $imageInfo = @getimagesize($temporaryPath);
                            $contentIsValid = is_array($imageInfo) && ($imageInfo['mime'] ?? '') === 'image/png';
                        } elseif ($extension === 'pdf') {
                            $handle = @fopen($temporaryPath, 'rb');
                            $header = $handle ? fread($handle, 5) : false;
                            if (is_resource($handle)) {
                                fclose($handle);
                            }

                            $contentIsValid = ($header === '%PDF-');

                            $pdfContents = @file_get_contents($temporaryPath);
                            if (is_string($pdfContents) &&
                                (stripos($pdfContents, '<?php') !== false ||
                                 stripos($pdfContents, '<?=') !== false ||
                                 stripos($pdfContents, '<script') !== false)) {
                                $contentIsValid = false;
                            }
                        }

                        if (!$contentIsValid) {
                            $message = 'تم رفض الملف: فشل التحقق الإضافي من بنية المحتوى.';
                            $messageType = 'error';
                        } elseif (!is_dir($storageDirectory) || !is_writable($storageDirectory)) {
                            $message = 'مجلد التخزين غير متاح للكتابة.';
                            $messageType = 'error';
                        } else {

                            try {
                                $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
                                $destination = $storageDirectory . DIRECTORY_SEPARATOR . $safeName;

                                if (move_uploaded_file($temporaryPath, $destination)) {
                                    @chmod($destination, 0600);
                                    $message = 'تم قبول الملف ورفعه بنجاح. الاسم الآمن: ' . $safeName;
                                    $messageType = 'success';
                                } else {
                                    $message = 'تعذر نقل الملف إلى مجلد التخزين.';
                                    $messageType = 'error';
                                }
                            } catch (Throwable $exception) {
                                // لا نعرض تفاصيل الاستثناء أو مسارات الخادم للمستخدم.
                                error_log('Secure upload error: ' . $exception->getMessage());
                                $message = 'حدث خطأ داخلي أثناء معالجة الملف.';
                                $messageType = 'error';
                            }
                        }
                    }
                }
            }
        }
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نظام رفع الملفات الآمن</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 30px 16px; min-height: 100vh;
            font-family: Tahoma, Arial, sans-serif; background: #f3f6fb;
            color: #1f2937; display: flex; justify-content: center; align-items: center;
        }
        .container {
            width: 100%; max-width: 620px; background: #fff; padding: 30px;
            border-radius: 16px; box-shadow: 0 10px 35px rgba(0,0,0,.08);
        }
        h1 { margin-top: 0; color: #173b68; font-size: 25px; }
        .description { color: #5b6574; line-height: 1.8; }
        .upload-box {
            margin: 22px 0; padding: 22px; border: 2px dashed #9bb4d2;
            border-radius: 12px; background: #f8fbff;
        }
        input[type=file] { width: 100%; margin: 12px 0; }
        button {
            border: 0; border-radius: 9px; padding: 12px 22px;
            background: #1764b0; color: white; font-size: 16px;
            cursor: pointer; width: 100%;
        }
        button:hover { background: #104d8b; }
        .message { padding: 14px; border-radius: 9px; margin: 18px 0; line-height: 1.7; overflow-wrap: anywhere; }
        .success { background: #e8f7ed; color: #176534; border: 1px solid #b8e3c5; }
        .error { background: #fff0f0; color: #9b2424; border: 1px solid #f0c1c1; }
        .rules { background: #f5f7fa; border-radius: 10px; padding: 16px 20px; line-height: 2; }
        .rules h2 { font-size: 17px; margin: 0 0 8px; color: #173b68; }
        .note { color: #687386; font-size: 13px; line-height: 1.7; margin-top: 18px; }
    </style>
</head>
<body>
<main class="container">
    <h1>نظام رفع الملفات الآمن</h1>
    <p class="description">
        يطبق هذا النظام استراتيجية الدفاع في العمق للتحقق من الملفات قبل تخزينها،
        ويمنع الاعتماد على اسم الملف أو امتداده وحده.
    </p>

    <?php if ($message !== ''): ?>
        <div class="message <?= $messageType === 'success' ? 'success' : 'error' ?>" role="status">
            <?= escapeHtml($message) ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="upload-box">
            <label for="uploaded_file"><strong>اختر الملف المراد رفعه:</strong></label>
            <input
                type="file"
                id="uploaded_file"
                name="uploaded_file"
                accept=".jpg,.jpeg,.png,.pdf"
                required
            >
            <button type="submit">فحص الملف ورفعه بأمان</button>
        </div>
    </form>

    <section class="rules">
        <h2>سياسة رفع الملفات</h2>
        <ul>
            <li>الامتدادات المسموحة: JPG وJPEG وPNG وPDF.</li>
            <li>الحد الأقصى لحجم الملف: 5 MB.</li>
            <li>فحص MIME Type باستخدام مكتبة Fileinfo.</li>
            <li>التحقق الإضافي من بنية الصور وملفات PDF.</li>
            <li>توليد اسم عشوائي باستخدام random_bytes(16).</li>
            <li>تخزين الملفات المقبولة في مجلد مخصص بصلاحيات مقيدة.</li>
        </ul>
    </section>

    <p class="note">
        ملاحظة تشغيل: يتطلب المشروع PHP 8 أو أحدث مع تفعيل امتداد Fileinfo.
        يجب ضبط upload_max_filesize وpost_max_size في إعدادات PHP بما يسمح برفع
        ملف حجمه 5 MB. وللنشر الفعلي، يفضّل وضع مجلد private_uploads خارج مجلد
        الويب العام، مع الإبقاء على صلاحيات الوصول مقيدة.
    </p>
</main>
</body>
</html>
