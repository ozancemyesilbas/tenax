<?php
// send-teklif.php — TENAX SMTP (PHPMailer)

date_default_timezone_set('Europe/Istanbul');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* === PHPMailer'ı bu sitedeki phpmailer/src/ klasöründen yükle === */
$base = __DIR__.'/phpmailer/src/';
foreach (['Exception.php','PHPMailer.php','SMTP.php'] as $f) {
  if (!is_file($base.$f)) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
      'ok'=>false,
      'error'=>"PHPMailer dosyası yok: {$base}{$f}",
      'hint'=>'public_html/phpmailer/src altına PHPMailer.php, SMTP.php, Exception.php koyun.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
}
require $base.'Exception.php';
require $base.'PHPMailer.php';
require $base.'SMTP.php';

/* === Yardımcılar === */
function field(array $names, $def=''){ foreach($names as $n){ if(isset($_POST[$n]) && trim($_POST[$n])!=='') return trim($_POST[$n]); } return $def; }
function wants_json(){ $a=strtolower($_SERVER['HTTP_ACCEPT']??''); return strpos($a,'application/json')!==false || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest'; }
function respond($ok,$msg,$extra=[]){
  if(wants_json()){
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge(['ok'=>$ok,'message'=>$msg],$extra), JSON_UNESCAPED_UNICODE); exit;
  }
  header('Content-Type: text/html; charset=UTF-8'); ?>
  <!doctype html><meta charset="utf-8"><title>Talep Durumu</title>
  <div style="max-width:720px;margin:48px auto;font:16px/1.5 -apple-system,Segoe UI,Roboto,Arial">
    <h2 style="color:<?= $ok?'#0a7f2e':'#b00020'?>"><?= $ok?'Gönderim başarılı.':'Gönderim başarısız.'?></h2>
    <p><?= htmlspecialchars($msg,ENT_QUOTES,'UTF-8') ?></p>
    <?php if(!$ok && !empty($extra['debug'])): ?>
      <pre style="background:#f6f8fa;padding:12px;border:1px solid #e5e7eb;border-radius:8px;white-space:pre-wrap"><?= htmlspecialchars($extra['debug'],ENT_QUOTES,'UTF-8') ?></pre>
    <?php endif; ?>
    <p><a href="javascript:history.back()">← Geri dön</a></p>
  </div><?php
  exit;
}

/* === Yalnız POST === */
if(($_SERVER['REQUEST_METHOD']??'')!=='POST') respond(false,'Geçersiz istek.');
/* Honeypot: formda name="website" gizli alanı ekleyebilirsin */
if(!empty($_POST['website']??'')) respond(true,'Teşekkürler.');

/* === Alanlar (iki isim seti de desteklenir) === */
$full_name = field(['full_name','adsoyad','ad']);
$phone     = field(['phone','telefon']);
$vehicle   = field(['vehicle','konu','urun']);
$message   = field(['message','mesaj','aciklama']);
$kaynak    = field(['kaynak'],'');
$kvkk_ok   = isset($_POST['kvkk_okundu']) || isset($_POST['kvkk']);
$iletisim  = (isset($_POST['ticari_iletisim_izni']) || isset($_POST['ticari_izin'])) ? 'Evet' : 'Hayır';

$errs=[];
if($full_name==='') $errs[]='Ad Soyad zorunludur.';
if($phone==='')     $errs[]='Telefon zorunludur.';
if($vehicle==='')   $errs[]='Talep konusu zorunludur.';
if($message==='')   $errs[]='Mesaj zorunludur.';
if(!$kvkk_ok)       $errs[]='KVKK onayı zorunludur.';
if($errs) respond(false, 'Form hatası: '.implode(' | ',$errs));

/* === Gövde === */
$h = fn($s)=>htmlspecialchars($s,ENT_QUOTES,'UTF-8');
$ip=$_SERVER['REMOTE_ADDR']??''; $ua=$_SERVER['HTTP_USER_AGENT']??'';
$subject='TENAX — Yeni Talep Formu ('.date('d.m.Y H:i').')';
$bodyHtml = '<html><body style="font-family:Arial,Helvetica,sans-serif">
<h2 style="margin:0 0 10px">Yeni Talep / Bilgi Formu</h2>
<table cellpadding="6" cellspacing="0" border="0">
<tr><td><b>Ad Soyad</b></td><td>'.$h($full_name).'</td></tr>
<tr><td><b>Telefon</b></td><td>'.$h($phone).'</td></tr>
<tr><td><b>Talep</b></td><td>'.$h($vehicle).'</td></tr>
<tr><td><b>Mesaj</b></td><td>'.nl2br($h($message)).'</td></tr>
<tr><td><b>Kaynak</b></td><td>'.$h($kaynak).'</td></tr>
<tr><td><b>Ticari İleti İzni</b></td><td>'.$iletisim.'</td></tr>
<tr><td><b>IP</b></td><td>'.$h($ip).'</td></tr>
</table>
<p style="color:#666;font-size:12px">Tarayıcı: '.$h($ua).'</p></body></html>';
$bodyText = "Yeni Talep / Bilgi Formu\n\nAd Soyad: $full_name\nTelefon: $phone\nTalep: $vehicle\nMesaj: $message\nKaynak: $kaynak\nTicari İleti İzni: $iletisim\nIP: $ip\n";

/* === SMTP ayarları (doldur) === */
$SMTP_USER = 'teklif@tenaxistanbul.com.tr';
$SMTP_PASS = 'g2JhVn-Zs27G1'; // <-- ŞİFREYİ YAZ

// Olası sunucu kombinasyonlarını sırayla dener
$candidates = [
  ['host'=>'mail.tenaxistanbul.com.tr','port'=>465,'secure'=>PHPMailer::ENCRYPTION_SMTPS],
  ['host'=>'mail.tenaxistanbul.com.tr','port'=>587,'secure'=>PHPMailer::ENCRYPTION_STARTTLS],
  ['host'=>'mail.kurumsaleposta.com','port'=>465,'secure'=>PHPMailer::ENCRYPTION_SMTPS],
  ['host'=>'mail.kurumsaleposta.com','port'=>587,'secure'=>PHPMailer::ENCRYPTION_STARTTLS],
];

$lastErr='';
foreach($candidates as $cfg){
  try{
    $m = new PHPMailer(true);
    $m->CharSet='UTF-8';
    $m->isSMTP();
    $m->SMTPAuth=true;
    $m->Host=$cfg['host'];
    $m->Username=$SMTP_USER;
    $m->Password=$SMTP_PASS;
    $m->Port=$cfg['port'];
    $m->SMTPSecure=$cfg['secure'];
    if($cfg['secure']===PHPMailer::ENCRYPTION_STARTTLS) $m->SMTPAutoTLS=true;

    $m->setFrom($SMTP_USER,'TENAX Web');
    $m->addAddress('teklif@tenaxistanbul.com.tr');
    $m->addReplyTo($SMTP_USER);

    $m->isHTML(true);
    $m->Subject=$subject;
    $m->Body=$bodyHtml;
    $m->AltBody=$bodyText;

    $m->send();
    respond(true,'Talebiniz alındı. En kısa sürede dönüş yapacağız.');
  }catch(Exception $e){
    $lastErr='Host='.$cfg['host'].' Port='.$cfg['port'].' -> '.$e->getMessage();
  }
}
respond(false,'SMTP gönderimi başarısız. Ayarları kontrol edin.',['debug'=>$lastErr]);
