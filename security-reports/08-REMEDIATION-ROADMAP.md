# 🛠️ Remediation Roadmap

---

## Quick Wins (1–3 Gün)

Bu aksiyonlar minimum eforla maksimum güvenlik iyileştirmesi sağlar.

### 1. Debug Modlarını Kapatın
**Dosya:** `public/wp-config.php` — Satır 77-83  
**Aksiyon:**
```php
// Şu anki:
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'SAVEQUERIES', true );
define( 'SCRIPT_DEBUG', true );
define( 'CONCATENATE_SCRIPTS', false );
define( 'DISALLOW_FILE_EDIT', false );

// Olması gereken:
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SAVEQUERIES', false );
define( 'SCRIPT_DEBUG', false );
define( 'CONCATENATE_SCRIPTS', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true );
```
**İlgili bulgular:** SEC-003, SEC-004  
**Etki:** Bilgi sızıntısı ve RCE riskini drastik azaltır

---

### 2. Kullanılmayan Plugin'leri Silin
**Aksiyon:** 107 inaktif plugin dizinini tamamen kaldırın
```bash
cd public/wp-content/plugins/
# Sadece biolink-pro ve index.php bırakın
# Geri kalan her şeyi silin
```
**İlgili bulgular:** SEC-006, SEC-028, SEC-029, SEC-030, SEC-034, SEC-036, SEC-037  
**Etki:** Saldırı yüzeyini ~%99 azaltır, 1.4 GB disk alanı kazanılır

---

### 3. xmlrpc.php Erişimini Engelleyin
**Dosya:** `public/.htaccess`  
**Aksiyon:**
```apache
# xmlrpc.php'yi engelle
<Files xmlrpc.php>
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order Deny,Allow
        Deny from all
    </IfModule>
</Files>
```
**İlgili bulgular:** SEC-007  
**Etki:** Brute-force ve DDoS amplification vektörünü kapatır

---

### 4. Güvenlik Header'larını Ekleyin
**Dosya:** `public/.htaccess`  
**Aksiyon:**
```apache
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), camera=(), microphone=()"
</IfModule>
```
**İlgili bulgular:** SEC-010  
**Etki:** Clickjacking, MIME sniffing, XSS amplification risklerini azaltır

---

### 5. Hassas Dosyalara Erişimi Engelleyin
**Dosya:** `public/.htaccess`  
**Aksiyon:**
```apache
# wp-config.php erişimini engelle
<Files wp-config.php>
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</Files>

# .log dosyalarına erişimi engelle
<FilesMatch "\.(log|sql|zip|json)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>

# Dizin listelemeyi engelle
Options -Indexes
```
**İlgili bulgular:** SEC-015  
**Etki:** Konfigürasyon ve log dosyalarına yetkisiz erişimi engeller

---

### 6. Bilgi Sızıntısı Dosyalarını Silin
**Aksiyon:**
```bash
rm public/wp-content/plugins/biolink-pro.zip
rm public/wp-content/plugins/plugin_inventory.json
rm public/readme.html
rm public/license.txt
```
**İlgili bulgular:** SEC-015  
**Etki:** Plugin ve WordPress sürüm bilgisi sızıntısını önler

---

## Medium (1–2 Hafta)

### 7. wp-config.php'yi Git'ten Çıkarın ve Secret'ları Rotate Edin
**Aksiyon:**
1. `.gitignore`'a `public/wp-config.php` ekleyin
2. `.gitignore`'a `sql/` dizinini ekleyin
3. wp-config.php'yi tracked dosyalardan çıkarın:
```bash
git rm --cached public/wp-config.php
git rm --cached sql/local.sql
```
4. Tüm authentication key/salt'ları yeniden oluşturun:
   - https://api.wordpress.org/secret-key/1.1/salt/
5. Veritabanı şifresini değiştirin
6. Git geçmişini temizleyin (BFG Repo-Cleaner)

**İlgili bulgular:** SEC-001, SEC-002, SEC-005  
**Etki:** Credential ve secret sızıntısını kalıcı olarak düzeltir

---

### 8. HTTPS Zorlayın
**Aksiyon:**
1. wp-config.php'ye ekleyin:
```php
define( 'FORCE_SSL_ADMIN', true );
define( 'FORCE_SSL_LOGIN', true );
```
2. Siteurl ve home'u güncelleyin: `https://yourdomain.com`
3. .htaccess'te HTTP→HTTPS yönlendirme:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```
4. HSTS header ekleyin:
```apache
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

**İlgili bulgular:** SEC-011  
**Etki:** Tüm trafiği şifreler, MITM saldırılarını engeller

---

### 9. Güvenlik Plugin'i Aktifleştirin
**Aksiyon:**
1. Wordfence veya Sucuri'yi aktifleştirin
2. Temel ayarları yapılandırın:
   - Brute-force koruması
   - Dosya bütünlüğü kontrolü
   - Firewall kuralları
   - İki faktörlü kimlik doğrulama
3. Rate limiting ayarlayın

**İlgili bulgular:** SEC-021  
**Etki:** Aktif güvenlik izleme ve saldırı engelleme

---

### 10. Admin Kullanıcı Güvenliğini Artırın
**Aksiyon:**
1. `root` kullanıcı adını değiştirin
2. Güçlü parola atayın (32+ karakter)
3. MFA aktifleştirin
4. İkinci admin hesabı oluşturun (recovery)
5. Admin e-posta adresini güncelleyin

**İlgili bulgular:** SEC-017  
**Etki:** Account takeover riskini drastik azaltır

---

### 11. BioLink Pro Template XSS Düzeltmesi
**Aksiyon:**  
`public/template.php`'de preview parametreleri için ek escaping:
```php
// CSS değerleri için
$theme[$key] = wp_strip_all_tags($v);  // sanitize_text_field yerine

// Output sırasında
echo esc_attr($theme['bg_color']);  // HTML attribute context
// veya
echo esc_html($theme['display_name']);  // HTML content context
```

**İlgili bulgular:** SEC-013  
**Etki:** Preview mode'da potansiyel XSS riskini ortadan kaldırır

---

## Hard (1+ Ay)

### 12. Ortam Değişkenleri ile Secret Yönetimi
**Aksiyon:**
1. wp-config.php'deki tüm secret'ları `.env` dosyasına taşıyın
2. `vlucas/phpdotenv` veya benzeri bir kütüphane kullanın
3. `.env` dosyasını `.gitignore`'a ekleyin
4. CI/CD'de environment variable injection

```php
// wp-config.php
define('DB_USER', getenv('WP_DB_USER'));
define('DB_PASSWORD', getenv('WP_DB_PASSWORD'));
define('AUTH_KEY', getenv('WP_AUTH_KEY'));
// ...
```

**İlgili bulgular:** SEC-001, SEC-002  
**Etki:** Secret'ların versiyon kontrolünden tamamen ayrılması

---

### 13. WAF (Web Application Firewall) Implementasyonu
**Aksiyon:**
1. Cloudflare WAF veya ModSecurity kurun
2. OWASP ModSecurity CRS kurallarını aktifleştirin
3. Bot koruması ve rate limiting
4. DDoS koruması

**Etki:** Tüm bilinen saldırı vektörlerine karşı ek savunma katmanı

---

### 14. Düzenli Güvenlik Taraması ve Monitoring
**Aksiyon:**
1. WPScan veya benzeri araçlarla haftalık tarama
2. Dosya bütünlüğü kontrolü (Tripwire/AIDE)
3. Log monitoring (ELK Stack/Splunk)
4. Uptime monitoring
5. Vulnerability disclosure policy

**Etki:** Sürekli güvenlik görünürlüğü

---

### 15. Penetrasyon Testi
**Aksiyon:**
1. Profesyonel penetrasyon testi yaptırın
2. OWASP Testing Guide metodolojisi
3. Business logic testleri
4. Social engineering testleri

**Etki:** Otomatik araçların bulamadığı zafiyetlerin tespiti

---

## Özet Önceliklendirme

| Öncelik | Aksiyon | Bağımlılık | Tahmini Süre |
|---------|---------|-----------|-------------|
| P0 | Debug modlarını kapatın | Yok | 5 dakika |
| P0 | Plugin'leri silin | Yok | 30 dakika |
| P0 | xmlrpc'yi engelleyin | Yok | 5 dakika |
| P0 | Güvenlik header'larını ekleyin | Yok | 10 dakika |
| P0 | Hassas dosya erişimini engelleyin | Yok | 10 dakika |
| P0 | Bilgi sızıntısı dosyalarını silin | Yok | 5 dakika |
| P1 | Secret rotation + gitignore | P0 tamamlanmalı | 2 saat |
| P1 | HTTPS zorlama | SSL sertifikası | 1 saat |
| P1 | Güvenlik plugin'i aktifleştirme | P0 tamamlanmalı | 30 dakika |
| P1 | Admin güvenliği | Yok | 30 dakika |
| P1 | Template XSS düzeltmesi | Yok | 2 saat |
| P2 | Env variable yönetimi | P1 tamamlanmalı | 4 saat |
| P2 | WAF implementasyonu | Altyapı kararı | 1 gün |
| P2 | Güvenlik taraması | P1 tamamlanmalı | Sürekli |
| P2 | Penetrasyon testi | P1 tamamlanmalı | 1 hafta |
