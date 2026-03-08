# 🔐 Authentication & Authorization Findings

---

## [SEC-007] XML-RPC Brute-Force ve DDoS Amplification Vektörü

**[Severity]** Critical  
**[Confidence]** High  
**[Category]** Auth Bypass / Brute-Force / DDoS  

**[Where]** `public/xmlrpc.php` — WordPress çekirdek dosyası, standart ve değiştirilmemiş

**[What is happening]**  
WordPress'in XML-RPC arayüzü tamamen aktif durumdadır. Bu arayüz `system.multicall` metodu ile tek bir HTTP isteğinde yüzlerce farklı kullanıcı adı/parola kombinasyonu denenebilir. Ayrıca `pingback.ping` metodu DDoS amplification saldırısı için kullanılabilir.

**[Why this is risky]**  
- XML-RPC üzerinden brute-force saldırısı, wp-login.php'ye kıyasla çok daha hızlı yapılabilir çünkü `system.multicall` ile tek istekte yüzlerce deneme yapılır.
- Rate limiting mekanizması varsayılan olarak yoktur.
- Kullanıcı adı `root` olduğu için saldırgan hedefi zaten bilmektedir.
- `pingback.ping` ile sunucu, üçüncü taraf sitelere istek göndermeye zorlanabilir (SSRF benzeri).

**[Attack scenario]**  
1. Saldırgan `xmlrpc.php`'ye `system.multicall` isteği gönderir
2. Tek istekte 500 farklı parola dener
3. `root` kullanıcı adı SQL dump'tan biliniyor
4. Başarılı giriş sonrası admin paneline tam erişim sağlanır
5. `DISALLOW_FILE_EDIT = false` olduğu için tema/plugin editörü ile PHP kodu çalıştırılabilir

**[Evidence]**  
- `public/xmlrpc.php` dosyası mevcut ve standart WordPress xmlrpc.php
- `.htaccess` dosyasında xmlrpc.php için erişim engeli yok
- wp-config.php'de xmlrpc'yi devre dışı bırakacak bir ayar yok
- Wordfence/AIOSEC gibi güvenlik plugin'leri inaktif

**[Fix]**  
1. `.htaccess`'e xmlrpc.php erişim engeli:
```apache
<Files xmlrpc.php>
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</Files>
```
2. Alternatif olarak `add_filter('xmlrpc_enabled', '__return_false');` ile functions.php'de devre dışı bırakma
3. Fail2ban veya Wordfence ile rate limiting

**[Priority]** Hemen

---

## [SEC-010] Güvenlik Header'ları Eksik

**[Severity]** High  
**[Confidence]** High  
**[Category]** Missing Security Headers / Clickjacking / MIME Sniffing  

**[Where]** `public/.htaccess` — Satır 1-16 (tüm dosya)

**[What is happening]**  
`.htaccess` dosyası sadece WordPress'in standart mod_rewrite kurallarını içeriyor. Hiçbir güvenlik header'ı tanımlanmamış:
- X-Frame-Options yok → Clickjacking riski
- X-Content-Type-Options yok → MIME sniffing riski
- Content-Security-Policy yok → XSS amplification
- Strict-Transport-Security yok → HTTPS downgrade
- X-XSS-Protection yok
- Referrer-Policy yok
- Permissions-Policy yok

**[Why this is risky]**  
- Admin paneli iframe içine alınarak clickjacking saldırısı yapılabilir
- Yüklenen dosyalarda MIME type spoofing ile XSS çalıştırılabilir
- HTTPS kullanılmadığı için tüm trafik açık metin

**[Evidence]**  
```apache
# public/.htaccess - Sadece rewrite kuralları mevcut:
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

**[Fix]**  
`.htaccess`'e eklenecek güvenlik header'ları:
```apache
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), camera=(), microphone=()"
    # HTTPS aktif olduğunda:
    # Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    # Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'"
</IfModule>
```

**[Priority]** Hemen

---

## [SEC-011] HTTPS Zorlanmıyor — Transport Security Eksik

**[Severity]** High  
**[Confidence]** High  
**[Category]** Transport Security  

**[Where]**  
- `public/wp-config.php` — `FORCE_SSL_ADMIN` ve `FORCE_SSL_LOGIN` tanımlanmamış  
- `sql/local.sql` — Satır 272-273: `siteurl` ve `home` = `http://test.local`

**[What is happening]**  
Site HTTP üzerinden çalışıyor. `FORCE_SSL_ADMIN` ve `FORCE_SSL_LOGIN` sabitleri wp-config.php'de tanımlanmamış. Bu, admin paneline ve login işlemlerine HTTP üzerinden erişilmesine izin veriyor.

**[Why this is risky]**  
- Login credential'ları ağ üzerinde açık metin olarak iletilir
- Session cookie'ler MITM saldırısıyla çalınabilir
- Admin AJAX istekleri şifrelenmez

**[Evidence]**  
- wp-config.php'de `FORCE_SSL_ADMIN` aranmış, bulunamadı
- `siteurl` = `http://test.local` (HTTPS değil)

**[Fix]**  
1. wp-config.php'ye ekle: `define('FORCE_SSL_ADMIN', true);`
2. Siteurl ve home ayarlarını `https://` ile güncelle
3. `.htaccess`'te HTTP→HTTPS yönlendirme ekle

**[Priority]** Sprint içinde (production'a geçişte zorunlu)

---

## [SEC-017] Tek Admin Kullanıcı — Single Point of Failure

**[Severity]** Medium  
**[Confidence]** High  
**[Category]** Account Security  

**[Where]** `sql/local.sql` — Satır 269 (wp_users INSERT)

**[What is happening]**  
Sistemde yalnızca bir kullanıcı var: `root` (ID: 1, administrator). Kullanıcı adı çok tahmin edilebilir ve sosyal mühendislik veya brute-force için kolay hedef.

**[Evidence]**  
```sql
INSERT INTO `wp_users` VALUES (1,'root','$2y$10$hEqlH...','root','dev-email@wpengine.local','http://test.local','2026-03-04 15:48:41','',0,'root');
```
- Kullanıcı adı: `root`
- Rol: `administrator` (wp_user_level: 10)
- MFA: Kurulu değil

**[Why this is risky]**  
- `root` kullanıcı adı ilk denenen isimlerden biri
- Account takeover = tam site kontrolü
- MFA olmadan sadece parola ile korunuyor
- Kurtarma mekanizması: tek e-posta adresi

**[Fix]**  
1. Kullanıcı adını tahmin edilemez bir şeye değiştirin
2. MFA plugin'i etkinleştirin (örn: Two Factor Authentication)
3. İkinci bir admin hesabı oluşturun (recovery için)
4. Admin hesabı için güçlü ve benzersiz parola kullanın

**[Priority]** Sprint içinde

---

## [SEC-020] WordPress REST API Kullanıcı Enumeration

**[Severity]** Medium  
**[Confidence]** High  
**[Category]** Information Disclosure / User Enumeration  

**[Where]** `/wp-json/wp/v2/users` — WordPress REST API varsayılan endpoint

**[What is happening]**  
WordPress REST API varsayılan olarak `/wp-json/wp/v2/users` endpoint'ini public yapar. Bu endpoint kimlik doğrulama gerektirmeden kullanıcı bilgilerini döner (kullanıcı adı, slug, avatar URL).

**[Why this is risky]**  
- Saldırgan tüm kullanıcı adlarını listeleyebilir
- Brute-force saldırıları için kullanıcı adı bilgisi elde edilir
- `root` kullanıcısının varlığı doğrulanır

**[Evidence]**  
- WordPress çekirdek REST API varsayılan olarak kullanıcı endpoint'ini expose eder
- Engelleyecek herhangi bir güvenlik plugin'i aktif değil

**[Fix]**  
1. REST API kullanıcı endpoint'ini kısıtlayın:
```php
add_filter('rest_endpoints', function($endpoints) {
    if (isset($endpoints['/wp/v2/users'])) {
        unset($endpoints['/wp/v2/users']);
    }
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }
    return $endpoints;
});
```
2. Veya Wordfence gibi bir güvenlik plugin'i ile kısıtlayın

**[Priority]** Sprint içinde

---

## [SEC-021] Rate Limiting Eksikliği — wp-login.php

**[Severity]** Medium  
**[Confidence]** High  
**[Category]** Brute-Force  

**[Where]** `public/wp-login.php` — WordPress çekirdek login sayfası

**[What is happening]**  
WordPress'in varsayılan login mekanizmasında rate limiting yoktur. `limit-login-attempts-reloaded` plugin'i kurulu ama inaktif.

**[Why this is risky]**  
- Sınırsız login denemesi yapılabilir
- Credential stuffing saldırılarına açık
- Kullanıcı adı `root` olduğu için hedef biliniyor

**[Evidence]**  
- `limit-login-attempts-reloaded` plugin dizini mevcut ama aktif plugin listesinde yok
- wp-config.php veya .htaccess'te rate limiting yok

**[Fix]**  
1. `limit-login-attempts-reloaded` veya `loginizer` plugin'ini aktifleştirin
2. Veya Fail2ban kuralı ekleyin
3. Cloudflare/WAF rate limiting

**[Priority]** Hemen

---

## [SEC-022] BioLink Pro — Profil Erişim Kontrolü (Pozitif Bulgu)

**[Severity]** Info  
**[Confidence]** High  
**[Category]** Authorization — Doğru Uygulama  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-ajax.php`

**[What is happening]**  
BioLink Pro plugin'i profil erişimi için doğru bir yetkilendirme modeli uygulamaktadır:

```php
private function can_access_profile($profile) {
    if (!$profile) return false;
    if (current_user_can('manage_options')) return true;  // Admin her şeye erişir
    return (int) $profile->user_id === get_current_user_id();  // Kullanıcı sadece kendine
}
```

**Her AJAX handler'da:**
1. `verify_nonce()` — CSRF koruması
2. `require_admin_capability()` — manage_biolink capability kontrolü
3. `get_profile_id()` → `can_access_profile()` — Ownership doğrulama

**[Evidence]**  
- 42 AJAX handler'ın tamamında nonce doğrulama mevcut
- Profil ve link işlemlerinde IDOR koruması var
- Preview modu login + nonce + ownership gerektiriyor

**[Fix]** Aksiyon gerekmiyor — doğru implementasyon.

**[Priority]** İzleme
