# 📥 Input/Data Handling & API Security Findings

---

## [SEC-013] BioLink Pro — Preview Template'inde CSS Injection / Reflected XSS

**[Severity]** Medium  
**[Confidence]** Medium  
**[Category]** XSS (Reflected) / CSS Injection  

**[Where]** `public/wp-content/plugins/biolink-pro/public/template.php` — Satır 85-113

**[What is happening]**  
Preview modu aktifken (`?blp_preview=1`), GET parametreleri üzerinden tema ayarları doğrudan override edilebilir. `d_` prefix'i ile gelen her parametre, karşılık gelen tema anahtarına yazılır:

```php
if (isset($_GET['blp_preview'])) {
    foreach ($_GET as $k => $v) {
        if (strpos($k, 'd_') === 0) {
            $key = substr($k, 2);
            if (array_key_exists($key, $theme)) {
                // Tipe göre sanitizasyon:
                if ($key === 'social_links') {
                    // JSON decode + sanitize_key + esc_url_raw ✓
                } elseif (in_array($key, ['enable_particles', ...], true)) {
                    // filter_var BOOLEAN ✓
                } elseif (in_array($key, ['card_opacity', ...], true)) {
                    // (int) cast ✓
                } else {
                    $theme[$key] = sanitize_text_field($v);  // ⚠️
                }
            }
        }
    }
}
```

**[Why this is risky]**  
`sanitize_text_field()` HTML etiketlerini temizler ve yeni satırları kaldırır, ancak:
1. **CSS context'te** yeterli değildir — CSS property değerleri içinde `url()` veya `expression()` gibi vektörler
2. **JavaScript string context'te** yeterli değildir — Eğer değer bir JS değişkenine atanıyorsa
3. `custom_css` gibi bir alan varsa, CSS injection mümkün olabilir

**Önemli hafifletici faktör:**  
- Preview modu `is_user_logged_in()` + `wp_verify_nonce()` + ownership kontrolü gerektiriyor
- Bu nedenle sadece profile sahibi bu URL'yi kullanabilir
- Ancak, crafted bir URL sosyal mühendislik ile paylaşılırsa, kurbanın kendi profilini "preview" ederken XSS tetiklenebilir (self-XSS / social engineering)

**[Evidence]**  
```
Dosya: public/wp-content/plugins/biolink-pro/public/template.php
Satır 85: if (isset($_GET['blp_preview'])) {
Satır 108-109: $theme[$key] = sanitize_text_field($v);
```

**[Fix]**  
1. CSS değerleri için ayrı sanitizasyon fonksiyonu:
```php
function sanitize_css_value($value) {
    // url(), expression(), javascript:, data: protokollerini kaldır
    $value = preg_replace('/url\s*\(/i', '', $value);
    $value = preg_replace('/expression\s*\(/i', '', $value);
    $value = preg_replace('/javascript:/i', '', $value);
    $value = preg_replace('/data:/i', '', $value);
    return sanitize_text_field($value);
}
```
2. Output sırasında `esc_attr()` kullanımı
3. Custom CSS alanı için `wp_strip_all_tags()` (zaten uygulanmış olabilir)

**[Priority]** Sprint içinde

---

## [SEC-028] İnaktif Plugin'lerde Kimlik Doğrulamasız AJAX Endpoint'ler

**[Severity]** Critical (pluginler aktifleştirilirse)  
**[Confidence]** High  
**[Category]** Auth Bypass / Unauthenticated Access  

**[Where]**  
1. `public/wp-content/plugins/all-in-one-wp-migration/lib/controller/class-ai1wm-main-controller.php` — Satır 1316-1323
2. `public/wp-content/plugins/wp-file-manager/file_folder_manager.php` — Satır 53-66
3. `public/wp-content/plugins/wpvivid-backuprestore/includes/class-wpvivid.php`
4. `public/wp-content/plugins/backuply/main/ajax.php`

**[What is happening]**  
Bu plugin'ler `wp_ajax_nopriv_` hook'ları ile kimlik doğrulama gerektirmeyen AJAX endpoint'ler tanımlar:

**All-in-One WP Migration:**
- `wp_ajax_nopriv_ai1wm_export` — Site export (veritabanı dahil)
- `wp_ajax_nopriv_ai1wm_import` — Site import/restore
- `wp_ajax_nopriv_ai1wm_backup_delete` — Yedek silme
- `wp_ajax_nopriv_ai1wm_backup_list` — Yedek listesi

**WP File Manager:**
- REST API: `permission_callback => '__return_true'` ile backup indirme
- `wp-json/v1/fm/backup/{id}/{type}/{key}` — Kimlik doğrulamasız yedek indirme

**WPvivid:**
- `wp_ajax_nopriv_wpvivid_restore` — Kimlik doğrulamasız veritabanı restore
- `wp_ajax_nopriv_wpvivid_get_restore_progress` — Restore progress izleme

**Backuply:**
- `wp_ajax_nopriv_backuply_restore_response`
- `wp_ajax_nopriv_backuply_update_serialization` — Serialized object handling
- `wp_ajax_nopriv_backuply_creating_session`
- `wp_ajax_nopriv_backuply_restore_status_log`

**[Why this is risky]**  
Bu plugin'ler şu anda **inaktif** oldukları için endpoint'ler çalışmaz. Ancak:
1. Yanlışlıkla aktifleştirildiğinde, anonim kullanıcılar tüm siteyi export/import edebilir
2. Supply chain saldırısıyla veya plugin auto-update ile aktifleşebilir
3. Plugin dosyaları web root'ta mevcut — bilinen CVE'ler direct file access ile exploit edilebilir

**[Evidence]**  
- Plugin dizinleri mevcut ve dosyaları erişilebilir
- `wp_ajax_nopriv_` hook pattern'i güvenlik açığının kesin kanıtı
- Plugin'ler SQL dump'taki `active_plugins` listesinde yok (sadece biolink-pro aktif)

**[Fix]**  
1. **Hemen:** Kullanılmayan tüm plugin'leri tamamen silin (dosyaları da dahil)
2. Plugin sayısını 108'den gerekli minimuma indirin
3. Aktifleştirilecek plugin'ler için güvenlik değerlendirmesi yapın

**[Priority]** Hemen

---

## [SEC-029] İnaktif Plugin'lerde SQL Injection

**[Severity]** High (pluginler aktifleştirilirse)  
**[Confidence]** High  
**[Category]** SQL Injection  

**[Where]** `public/wp-content/plugins/all-in-one-wp-security-and-firewall/admin/wp-security-list-404.php`

**[What is happening]**  
AIOSEC plugin'inde 404 log listesi silme işleminde prepare() kullanılmadan doğrudan SQL sorgusu:

```php
$wpdb->query("DELETE FROM ... WHERE id IN " . $id_list);
```

`$id_list` değişkeni kullanıcı girdisinden oluşturuluyor ve parametrized query kullanılmıyor.

**[Why this is risky]**  
Plugin aktifleştirildiğinde admin kullanıcı SQL injection saldırısına maruz kalabilir. Bu, admin-level SQL injection olsa da, CSRF ile combine edilebilir.

**[Evidence]**  
- `wp-security-list-404.php` dosyasında unprepared query pattern'i tespit edildi

**[Fix]**  
1. Bu plugin'i kullanmayın veya güncel sürüme yükseltin
2. Silin: `rm -rf public/wp-content/plugins/all-in-one-wp-security-and-firewall/`

**[Priority]** Hemen (silme ile)

---

## [SEC-030] İnaktif Plugin'lerde unserialize() Kullanımı — Object Injection

**[Severity]** High (pluginler aktifleştirilirse)  
**[Confidence]** Medium  
**[Category]** Unsafe Deserialization  

**[Where]**  
1. `public/wp-content/plugins/wp-reviews-plugin-for-google/trustindex-plugin.class.php` — `unserialize(wp_remote_retrieve_body($wpResponse))`
2. `public/wp-content/plugins/all-in-one-wp-security-and-firewall/` — Birden fazla `unserialize()` çağrısı
3. `public/wp-content/plugins/really-simple-ssl/` — Decrypt edilmiş verinin unserialize edilmesi
4. `public/wp-content/plugins/loginizer/` — Session verisinin unserialize edilmesi

**[What is happening]**  
Bu plugin'ler `unserialize()` fonksiyonunu kullanıcı kontrollü veya harici kaynaklardan gelen veriler üzerinde çağırıyor. PHP Object Injection saldırılarına zemin hazırlar.

**[Why this is risky]**  
- unserialize() ile saldırgan, mevcut PHP sınıflarının `__wakeup()` ve `__destruct()` magic method'larını tetikleyebilir
- POP (Property-Oriented Programming) zinciri ile Remote Code Execution (RCE) mümkün
- WordPress ekosisteminde yeterli gadget class'ları mevcut

**[Fix]**  
1. Bu plugin'leri silin
2. `unserialize()` yerine `json_decode()` kullanın
3. PHP 7.0+ için `unserialize($data, ['allowed_classes' => false])` kullanın

**[Priority]** Hemen (silme ile)

---

## [SEC-031] BioLink Pro — Dosya Yükleme Güvenliği (Pozitif Bulgu)

**[Severity]** Info  
**[Confidence]** High  
**[Category]** File Upload — Doğru Uygulama  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-ajax.php` — Avatar upload handler

**[What is happening]**  
BioLink Pro'nun avatar yükleme mekanizması doğru güvenlik kontrolleri uygulamaktadır:

```php
// 1. Authentication + Nonce
$this->verify_nonce();
$this->require_admin_capability();

// 2. Boyut sınırı: 2MB
$max_size = 2 * 1024 * 1024;
if ($_FILES['avatar']['size'] > $max_size) { ... }

// 3. MIME whitelist
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_info = wp_check_filetype_and_ext($_FILES['avatar']['tmp_name'], $_FILES['avatar']['name']);
$detected_type = $file_info['type'] ?: mime_content_type($_FILES['avatar']['tmp_name']);
if (!in_array($detected_type, $allowed_types, true)) { ... }

// 4. WordPress Media API kullanımı
$attachment_id = media_handle_upload('avatar', 0);
```

**[Evidence]**  
- Kimlik doğrulama + CSRF koruması ✓
- Dosya boyut sınırı ✓
- MIME type whitelist (strict mode) ✓
- WordPress native upload handler kullanımı ✓
- Double extension kontrolü (wp_check_filetype_and_ext) ✓

**[Fix]** Aksiyon gerekmiyor — doğru implementasyon.

**[Priority]** İzleme

---

## [SEC-032] BioLink Pro — SQL Injection Koruması (Pozitif Bulgu)

**[Severity]** Info  
**[Confidence]** High  
**[Category]** SQL Injection — Doğru Koruma  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-database.php` — Tüm veritabanı sorguları

**[What is happening]**  
Tüm veritabanı sorguları `$wpdb->prepare()` ile parametrize edilmiştir:

```php
$wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}blp_profiles WHERE username = %s AND is_active = 1 LIMIT 1",
    $username
));

$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}blp_links WHERE profile_id = %d ORDER BY sort_order ASC",
    $profile_id
));
```

**[Fix]** Aksiyon gerekmiyor — doğru implementasyon.

**[Priority]** İzleme

---

## [SEC-033] BioLink Pro — CORS / CSRF Koruması (Pozitif Bulgu)

**[Severity]** Info  
**[Confidence]** High  
**[Category]** CSRF — Doğru Koruma  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-ajax.php` — `verify_nonce()` fonksiyonu

**[What is happening]**  
Tüm state-changing AJAX operasyonlarında WordPress nonce sistemi kullanılmaktadır:

```php
private function verify_nonce() {
    check_ajax_referer('blp_nonce', 'nonce');
    // wp_die() on failure
}
```

Admin tarafında nonce oluşturma:
```php
'nonce' => wp_create_nonce('blp_nonce'),
```

**[Fix]** Aksiyon gerekmiyor — doğru implementasyon.

**[Priority]** İzleme

---

## [SEC-034] MainWP Child Plugin — eval() ile Remote Code Execution

**[Severity]** High (plugin aktifleştirilirse)  
**[Confidence]** High  
**[Category]** Remote Code Execution (RCE)  

**[Where]** `public/wp-content/plugins/mainwp-child/class/class-mainwp-utility.php` — `execute_snippet()` fonksiyonu

**[What is happening]**  
MainWP Child plugin'i, veritabanından alınan PHP kodunu `eval()` ile çalıştırır. Bu, MainWP Dashboard'dan uzaktan kod yönetimi için tasarlanmıştır, ancak veritabanı ele geçirilirse RCE'ye dönüşür.

**[Why this is risky]**  
- Database compromise → eval() → RCE zinciri
- SQL Injection + eval() = tam sunucu kontrolü
- İç tehdit: Admin kullanıcı zararlı snippet ekleyebilir

**[Evidence]**  
- `class-mainwp-utility.php` dosyasında `execute_snippet()` fonksiyonu
- `eval()` kullanımı tespit edildi

**[Fix]**  
1. Bu plugin'i silin (kullanılmıyorsa)
2. Kullanılacaksa, snippet execution özelliğini devre dışı bırakın

**[Priority]** Hemen (silme ile)
