<?php
// send-teklif.php
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Istanbul');
header('Content-Type: application/json; charset=UTF-8');

function end_with($ok, $msg, $code = 200){
  http_response_code($code);
  echo json_encode(['ok'=>$ok, 'message'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  end_with(false, 'Geçersiz istek.', 405);
}

// ---- Honeypot (bot filtresi) ----
if (!empty($_POST['company'])) {
  end_with(false, 'İstek reddedildi.', 400);
}

// ---- Form verileri ----
$full_name = trim($_POST['full_name'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$vehicle   = trim($_POST['vehicle'] ?? '');
$message   = trim($_POST['message'] ?? '');
$kvkk_ok   = isset($_POST['kvkk_okundu']);
$iletisim  = isset($_POST['ticari_iletisim_izni']) ? 'Evet' : 'Hayır';

// Zorunlu alan kontrol
if ($full_name === '' || $phone === '' || $vehicle === '' || $message === '' || !$kvkk_ok) {
  end_with(false, 'Zorunlu alanları doldurun ve KVKK onayını işaretleyin.', 400);
}

// Basit sınırlar
if (mb_strlen($full_name) > 100 || mb_strlen($phone) > 32 || mb_strlen($vehicle) > 60 || mb_strlen($message) > 5000) {
  end_with(false, 'Alan uzunlukları limitleri aşıyor.', 400);
}

// Header injection korunması (zaten header’larda kullanmıyoruz ama tedbir)
$bad = '/(content-type:|bcc:|cc:|to:)/i';
foreach ([$full_name, $phone, $vehicle] as $v) {
  if (preg_match($bad, $v)) end_with(false, 'Geçersiz içerik.', 400);
}

// ---- Mail ayarları ----
$to      = 'teklif@tenaxistanbul.com.tr';
$from    = 'teklif@tenaxistanbul.com.tr'; // GÖNDEREN: aynı alan adı (SPF/DMARC için)
$subject = 'TENAX — Yeni Talep Formu ('.date('d.m.Y H:i').')';
$ip      = $_SERVER['REMOTE_ADDR'] ?? '';
$ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';

// HTML gövde
$body = '<html><body style="font-family:Arial,Helvetica,sans-serif">
  <h2 style="margin:0 0 10px">Yeni Talep / Bilgi Formu</h2>
  <table cellpadding="6" cellspacing="0" border="0">
    <tr><td><b>Ad Soyad</b></td><td>'.htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8').'</td></tr>
    <tr><td><b>Telefon</b></td><td>'.htmlspecialchars($phone, ENT_QUOTES, 'UTF-8').'</td></tr>
    <tr><td><b>Talep</b></td><td>'.htmlspecialchars($vehicle, ENT_QUOTES, 'UTF-8').'</td></tr>
    <tr><td><b>Mesaj</b></td><td>'.nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')).'</td></tr>
    <tr><td><b>Ticari İleti İzni</b></td><td>'.$iletisim.'</td></tr>
    <tr><td><b>IP</b></td><td>'.htmlspecialchars($ip, ENT_QUOTES, 'UTF-8').'</td></tr>
  </table>
  <p style="color:#666;font-size:12px">Tarayıcı: '.htmlspecialchars($ua, ENT_QUOTES, 'UTF-8').'</p>
</body></html>';

// Başlıklar
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: TENAX Web <{$from}>\r\n";
$headers .= "Reply-To: {$from}\r\n";
$headers .= "X-Mailer: PHP/".phpversion()."\r\n";

// Envelope sender (SPF/DMARC için önemli)
$parameters = "-f {$from}";

// Gönder
$ok = @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $body, $headers, $parameters);

if ($ok) {
  end_with(true, 'Talebiniz alınmıştır. En kısa sürede dönüş yapacağız.');
} else {
  end_with(false, 'E-posta gönderilemedi. Lütfen daha sonra tekrar deneyin.', 500);
}
