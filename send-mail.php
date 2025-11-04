<?php
// send-mail.php
// UTF-8 ile güvenli basit form-Post → e-posta

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Honeypot: bot doldurduysa sessizce iptal
if (!empty($_POST['website'])) {
    http_response_code(200);
    header('Location: /?sent=ok'); // bot ise de "başarılı" dönüp susalım
    exit;
}

// Verileri al & temizle
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Basit doğrulamalar
if ($name === '' || $subject === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /?sent=err'); // isterseniz ayrı bir teşekkür sayfası kullanın
    exit;
}

// ALICI — hedef adresiniz
$to = 'info@tenaxistanbul.com.tr';

// GÖNDEREN — DMARC/SPF için kendi domaininizden bir adres kullanın
$fromEmail = 'webform@tenaxistanbul.com.tr'; // Hostinger’da bir mailbox olarak oluşturmanız tavsiye edilir
$fromName  = 'Tenax İstanbul İletişim Formu';

// Konu başlığını UTF-8 olarak sarmalayalım
$subjectEncoded = '=?UTF-8?B?'.base64_encode('İletişim: '.$subject).'?=';

// Mesaj gövdesi (düz metin)
$ip   = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$time = date('Y-m-d H:i:s');

$body = 
"Yeni iletişim formu mesajı:\n\n".
"Ad Soyad : {$name}\n".
"E-posta  : {$email}\n".
"IP       : {$ip}\n".
"Tarih    : {$time}\n\n".
"--- Mesaj ---\n{$message}\n";

// Headerlar
$headers  = "From: {$fromName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/".phpversion()."\r\n";

// Gönder
$ok = @mail($to, $subjectEncoded, $body, $headers);

// Sonuç yönlendirmesi
if ($ok) {
    header('Location: /?sent=ok');
} else {
    header('Location: /?sent=err');
}
exit;
