# 🧪 Missing Security Tests

Bu bölüm, repoda mevcut olmayan ancak güvenlik açısından kritik olan testleri listeler. Bu testler, güvenlik zafiyetlerinin erken tespiti ve regresyon önleme amacıyla uygulanmalıdır.

---

## 1. BioLink Pro Plugin — Birim Testleri

### 1.1 Yetkilendirme Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_unauthenticated_ajax_rejected` | Giriş yapmamış kullanıcının tüm admin AJAX handler'larına erişiminin reddedildiğini doğrulayın | P0 |
| `test_wrong_user_profile_access_denied` | Kullanıcı A'nın Kullanıcı B'nin profiline erişemediğini doğrulayın | P0 |
| `test_wrong_user_link_access_denied` | Kullanıcı A'nın Kullanıcı B'nin link'ini düzenleyemediğini doğrulayın | P0 |
| `test_admin_can_access_all_profiles` | Admin kullanıcının tüm profillere erişebildiğini doğrulayın | P0 |
| `test_subscriber_cannot_access_biolink` | Subscriber rolünün BioLink işlevlerine erişemediğini doğrulayın | P0 |
| `test_editor_has_manage_biolink_cap` | Editor rolünün manage_biolink capability'sine sahip olduğunu doğrulayın | P1 |
| `test_profile_ownership_verified_on_all_endpoints` | Tüm profil-ilişkili endpoint'lerde ownership kontrolünü doğrulayın | P0 |

### 1.2 CSRF (Nonce) Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_missing_nonce_rejected` | Nonce olmadan gönderilen isteklerin reddedildiğini doğrulayın | P0 |
| `test_invalid_nonce_rejected` | Geçersiz nonce ile gönderilen isteklerin reddedildiğini doğrulayın | P0 |
| `test_expired_nonce_rejected` | Süresi dolmuş nonce'ların reddedildiğini doğrulayın | P1 |

### 1.3 Input Validation Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_xss_in_profile_name_sanitized` | Profil adında `<script>` taglarının temizlendiğini doğrulayın | P0 |
| `test_xss_in_bio_sanitized` | Bio alanında HTML injection'ın engellendiğini doğrulayın | P0 |
| `test_sql_injection_in_username` | Username alanında SQL injection'ın engellendiğini doğrulayın | P0 |
| `test_javascript_url_rejected` | Link URL'lerinde `javascript:` scheme'inin reddedildiğini doğrulayın | P0 |
| `test_data_url_rejected` | Link URL'lerinde `data:` scheme'inin reddedildiğini doğrulayın | P0 |
| `test_invalid_hex_color_rejected` | Geçersiz hex renk kodlarının reddedildiğini doğrulayın | P1 |
| `test_negative_sort_order_handled` | Negatif sort_order değerlerinin düzgün işlendiğini doğrulayın | P1 |
| `test_username_special_chars_rejected` | Username'de özel karakterlerin reddedildiğini doğrulayın | P0 |
| `test_preview_params_sanitized` | Preview GET parametrelerinin doğru sanitize edildiğini doğrulayın | P0 |
| `test_css_injection_in_preview` | Preview'da CSS injection payload'larının engellendiğini doğrulayın | P0 |

### 1.4 Dosya Yükleme Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_php_file_upload_rejected` | PHP dosyası yükleme girişiminin reddedildiğini doğrulayın | P0 |
| `test_double_extension_rejected` | `image.php.jpg` gibi çift uzantılı dosyaların reddedildiğini doğrulayın | P0 |
| `test_mime_spoofing_rejected` | MIME type spoofing'in (php dosya + image MIME) reddedildiğini doğrulayın | P0 |
| `test_oversized_file_rejected` | 2MB üzeri dosyaların reddedildiğini doğrulayın | P1 |
| `test_svg_upload_rejected` | SVG dosyası yükleme girişiminin reddedildiğini doğrulayın (XSS riski) | P0 |

### 1.5 Rate Limiting Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_click_tracking_rate_limited` | 15 istek/60s sınırının çalıştığını doğrulayın | P1 |
| `test_rate_limit_per_ip` | Rate limiting'in IP bazlı olduğunu doğrulayın | P1 |
| `test_rate_limit_bypass_via_headers` | X-Forwarded-For ile rate limit bypass girişiminin engellendiğini doğrulayın | P0 |

### 1.6 Business Logic Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_duplicate_username_rejected` | Aynı username ile ikinci profil oluşturmanın engellendiğini doğrulayın | P0 |
| `test_profile_deactivation_hides_public` | Deaktif profillerin public'ten görünmediğini doğrulayın | P0 |
| `test_deleted_profile_links_cleaned` | Silinen profilin linklerinin de temizlendiğini doğrulayın | P1 |
| `test_analytics_not_counted_for_owner` | Profil sahibinin kendi görüntülemelerinin sayılmadığını doğrulayın | P1 |
| `test_csv_export_formula_injection` | CSV export'ta formül injection'ın engellendiğini doğrulayın | P0 |
| `test_import_mass_assignment_prevented` | Import'ta korumalı alanların güncellenemediğini doğrulayın | P0 |

---

## 2. WordPress Konfigürasyon Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_debug_mode_disabled_in_production` | Production ortamda WP_DEBUG = false olduğunu doğrulayın | P0 |
| `test_file_editor_disabled` | DISALLOW_FILE_EDIT = true olduğunu doğrulayın | P0 |
| `test_ssl_enforced` | FORCE_SSL_ADMIN = true olduğunu doğrulayın | P0 |
| `test_default_table_prefix_not_used` | Tablo prefix'inin `wp_` olmadığını doğrulayın | P2 |
| `test_xmlrpc_disabled` | xmlrpc.php'nin engellendiğini doğrulayın | P0 |
| `test_security_headers_present` | Gerekli güvenlik header'larının mevcut olduğunu doğrulayın | P0 |
| `test_directory_listing_disabled` | Dizin listelemenin kapalı olduğunu doğrulayın | P1 |
| `test_debug_log_not_accessible` | debug.log dosyasının HTTP ile erişilemediğini doğrulayın | P0 |
| `test_wp_config_not_accessible` | wp-config.php'nin HTTP ile erişilemediğini doğrulayın | P0 |

---

## 3. Infrastructure/Deployment Testleri

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_no_secrets_in_git` | Git repository'de credential/secret olmadığını doğrulayın | P0 |
| `test_no_sql_dumps_in_git` | Git repository'de SQL dump olmadığını doğrulayın | P0 |
| `test_no_zip_in_webroot` | Web root'ta ZIP dosyaları olmadığını doğrulayın | P1 |
| `test_inactive_plugins_removed` | İnaktif plugin dosyalarının kaldırıldığını doğrulayın | P0 |
| `test_file_permissions_secure` | wp-config.php izinlerinin 600 veya daha kısıtlı olduğunu doğrulayın | P1 |
| `test_https_redirect_working` | HTTP → HTTPS yönlendirmesinin çalıştığını doğrulayın | P0 |

---

## 4. Entegrasyon / Sızma Testi Senaryoları

| Test | Açıklama | Öncelik |
|------|----------|---------|
| `test_brute_force_login_blocked` | 10 başarısız login sonrası hesabın geçici olarak kilitlendiğini doğrulayın | P0 |
| `test_xmlrpc_multicall_blocked` | system.multicall ile toplu deneme girişiminin engellendiğini doğrulayın | P0 |
| `test_user_enumeration_prevented` | /wp-json/wp/v2/users ve /?author=1 ile kullanıcı enumeration'ın engellendiğini doğrulayın | P1 |
| `test_clickjacking_prevented` | X-Frame-Options header'ının doğru çalıştığını doğrulayın | P1 |
| `test_wp_cron_not_exploitable` | wp-cron.php'nin DoS için kullanılamadığını doğrulayın | P2 |

---

## Test Altyapısı Önerisi

BioLink Pro için PHPUnit tabanlı test altyapısı:

```php
// tests/bootstrap.php
$_tests_dir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname(__DIR__) . '/biolink-pro.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';

// tests/test-security.php
class BLP_Security_Test extends WP_UnitTestCase {
    
    public function test_unauthenticated_ajax_rejected() {
        // Logout any user
        wp_set_current_user(0);
        
        // Try to call admin-only AJAX handler
        $_POST['action'] = 'blp_save_profile';
        $_POST['nonce'] = 'invalid';
        
        try {
            do_action('wp_ajax_nopriv_blp_save_profile');
        } catch (WPDieException $e) {
            $this->assertTrue(true);
            return;
        }
        
        $this->fail('Unauthenticated request should be rejected');
    }
    
    public function test_wrong_user_cannot_access_profile() {
        // Create two users
        $user_a = $this->factory->user->create(['role' => 'editor']);
        $user_b = $this->factory->user->create(['role' => 'editor']);
        
        // User A creates a profile
        wp_set_current_user($user_a);
        // ... create profile ...
        
        // User B tries to access User A's profile
        wp_set_current_user($user_b);
        // ... attempt access ...
        
        // Assert access denied
    }
}
```

---

## WordPress Configuration Test Script

```bash
#!/bin/bash
# security-check.sh — WordPress güvenlik yapılandırma kontrolü

echo "=== WordPress Security Configuration Check ==="

# 1. Debug mode
if grep -q "WP_DEBUG.*true" public/wp-config.php; then
    echo "❌ FAIL: WP_DEBUG is enabled"
else
    echo "✅ PASS: WP_DEBUG is disabled"
fi

# 2. File editor
if grep -q "DISALLOW_FILE_EDIT.*false\|DISALLOW_FILE_EDIT.*0" public/wp-config.php; then
    echo "❌ FAIL: File editor is enabled"
else
    echo "✅ PASS: File editor is disabled"
fi

# 3. xmlrpc blocked
if grep -q "xmlrpc.php" public/.htaccess; then
    echo "✅ PASS: xmlrpc.php is blocked in .htaccess"
else
    echo "❌ FAIL: xmlrpc.php is not blocked"
fi

# 4. Security headers
if grep -q "X-Frame-Options" public/.htaccess; then
    echo "✅ PASS: X-Frame-Options header found"
else
    echo "❌ FAIL: X-Frame-Options header missing"
fi

# 5. Inactive plugins
PLUGIN_COUNT=$(ls -d public/wp-content/plugins/*/  2>/dev/null | wc -l)
echo "ℹ️  INFO: $PLUGIN_COUNT plugin directories found"

# 6. Sensitive files
for f in public/wp-content/plugins/biolink-pro.zip public/wp-content/plugins/plugin_inventory.json public/readme.html; do
    if [ -f "$f" ]; then
        echo "❌ FAIL: Sensitive file exists: $f"
    else
        echo "✅ PASS: Sensitive file removed: $f"
    fi
done

# 7. Secrets in git
if git log --all --diff-filter=A -- public/wp-config.php | grep -q "commit"; then
    echo "⚠️  WARN: wp-config.php was committed to git history"
fi

echo "=== Check Complete ==="
```
