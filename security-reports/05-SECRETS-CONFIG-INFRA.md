# 🔑 Secrets, Configuration & Infrastructure Findings

---

## [SEC-001] wp-config.php — Hardcoded Database Credentials Git'e Commit Edilmiş

**[Severity]** Critical  
**[Confidence]** High  
**[Category]** Sensitive Data Exposure / Hardcoded Credentials  

**[Where]** `public/wp-config.php` — Satır 24-33

**[What is happening]**  
Veritabanı kimlik bilgileri doğrudan wp-config.php dosyasında açık metin olarak bulunuyor ve Git repository'sine commit edilmiş:

```php
// Satır 24
define( 'DB_NAME', 'local' );

// Satır 27
define( 'DB_USER', 'root' );

// Satır 30
define( 'DB_PASSWORD', 'root' );

// Satır 33
define( 'DB_HOST', 'localhost' );
```

**[Why this is risky]**  
- Repository'ye erişimi olan herkes veritabanına tam erişim sağlar
- `root` kullanıcısı MySQL'de en yüksek yetkili hesaptır
- Parola `root` — en zayıf varsayılan credential'lardan biri
- Git geçmişinde kalıcı olarak bulunur (silinse bile `git log` ile erişilebilir)
- Public repo yapılırsa tüm veriler tehlikeye girer

**[Evidence]**  
- `public/wp-config.php` satır 24-33 doğrudan incelendi
- Git repository'de mevcut (commit: bf2a911e9)

**[Fix]**  
1. **Hemen:** wp-config.php'yi `.gitignore`'a ekleyin
2. **Hemen:** Veritabanı şifresini güçlü ve benzersiz bir değerle değiştirin
3. **Hemen:** Veritabanı kullanıcısını `root` yerine kısıtlı bir kullanıcı yapın
4. **Sprint içinde:** BFG Repo-Cleaner ile Git geçmişinden temizleyin:
```bash
git filter-branch --force --index-filter \
  'git rm --cached --ignore-unmatch public/wp-config.php' \
  --prune-empty --tag-name-filter cat -- --all
```
5. **Kalıcı:** Ortam değişkenleri kullanın:
```php
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
```

**[Priority]** Hemen

---

## [SEC-002] Authentication Key/Salt'lar Git Repository'de Açık

**[Severity]** Critical  
**[Confidence]** High  
**[Category]** Sensitive Data Exposure / Secret Exposure  

**[Where]** `public/wp-config.php` — Satır 52-60

**[What is happening]**  
WordPress'in kimlik doğrulama sistemi için kullanılan tüm güvenlik anahtarları ve salt değerleri Git repository'sinde açık metin olarak bulunuyor:

```php
// Satır 52
define( 'AUTH_KEY',          '&q-p_V9-?W=zj6eN.{;=GXk`ooCI1LDM=sE~!@IZ;{_6%1)M}R=K<#7(x_A]`i2V' );
// Satır 53
define( 'SECURE_AUTH_KEY',   '2<=Mhz?y)8bNq)%vjPu-Bu5,!a7!KIhJ;Jg_=0ydLc&q1$YxzI!Tq_B]]k{vHRD&' );
// Satır 54
define( 'LOGGED_IN_KEY',     '5I@M$B+Shuk;9eYvtxsyMEtkBesV*.iV)E,2r3O&7HvWlPwbI3M>ybjD{G{D{RqN' );
// Satır 55
define( 'NONCE_KEY',         '7u*j+ZP)pG06R;Y;u03KkM]Yq9/Rz)&hJ[cSC!Y%BJ9*<h=5LD4f_YclqF#iRoN%' );
// Satır 56
define( 'AUTH_SALT',         'E~/]35| G~<wI1[ =J@FjHm9^K;wTBi5}H`Kb^,Dm3AZ=zT1T]9|My?BiUDW>I*g' );
// Satır 57
define( 'SECURE_AUTH_SALT',  'bO(W3L: B/#DXe<gDFqN3N6bvM1?tdh%O~WM8b itGJaIS9 A2]u#*$4^:na1$)Q' );
// Satır 58
define( 'LOGGED_IN_SALT',    'e2>Zd/l4A_Bf=m!l~5]8MZg=zP_9#&+}Fk~PzM}#zRa+__taj4`KLV,:<_8f6::i' );
// Satır 59
define( 'NONCE_SALT',        '?NV@$nl0~C%Uh{#)pJ]u}rz!eUyEpWbexD&&*!$dtEZ/HfJdP&cKg@1Tsu8E[zIK' );
// Satır 60
define( 'WP_CACHE_KEY_SALT', 'MsMb-@nwp)M%VRV5J(P@RTe >V<UE9EOYzG%x>,~N]bh1% IrbI!UD}E*uH1,Iy{' );
```

**[Why this is risky]**  
- Bu anahtarlar WordPress session cookie'lerini imzalamak için kullanılır
- Anahtarları bilen saldırgan, geçerli oturum cookie'leri üretebilir
- Admin dahil herhangi bir kullanıcının oturumunu taklit edebilir
- Password hash'lerini kırmak için kullanılabilir

**[Evidence]**  
- `public/wp-config.php` satır 52-60 doğrudan incelendi

**[Fix]**  
1. **Hemen:** Tüm key ve salt değerlerini yeniden oluşturun: https://api.wordpress.org/secret-key/1.1/salt/
2. **Hemen:** wp-config.php'yi `.gitignore`'a ekleyin
3. **Sprint içinde:** Git geçmişini temizleyin

**[Priority]** Hemen

---

## [SEC-003] Debug Modları Production'da Açık

**[Severity]** Critical  
**[Confidence]** High  
**[Category]** Insecure Configuration / Information Disclosure  

**[Where]** `public/wp-config.php` — Satır 77-83

**[What is happening]**  
Tüm WordPress debug sabitleri `true` olarak ayarlanmış:

```php
// Satır 77
define( 'WP_DEBUG', true );
// Satır 78
define( 'WP_DEBUG_LOG', true );
// Satır 79
define( 'WP_DEBUG_DISPLAY', true );
// Satır 80
define( 'SAVEQUERIES', true );          // Log all SQL queries
// Satır 81
define( 'SCRIPT_DEBUG', true );         // Load unminified JS/CSS
// Satır 82
define( 'CONCATENATE_SCRIPTS', false ); // Individual script paths visible
```

**[Why this is risky]**  
| Ayar | Risk |
|------|------|
| `WP_DEBUG = true` | PHP hata mesajları üretilir |
| `WP_DEBUG_DISPLAY = true` | Hata mesajları **tarayıcıda** gösterilir — dosya yolları, veritabanı bilgileri sızdırılır |
| `WP_DEBUG_LOG = true` | `wp-content/debug.log` dosyasına yazılır — public erişilebilir olabilir |
| `SAVEQUERIES = true` | Tüm SQL sorguları bellekte loglanır — bellek tükenmesi + bilgi sızıntısı |
| `SCRIPT_DEBUG = true` | Unminified JS/CSS yüklenir — kaynak kod yapısı açığa çıkar |
| `CONCATENATE_SCRIPTS = false` | Bireysel script dosya yolları görünür |

**[Evidence]**  
- `public/wp-config.php` satır 77-83 doğrudan incelendi
- `WP_ENVIRONMENT_TYPE` = `local` (satır 101) — geliştirme ortamı

**[Fix]**  
Production ortamında:
```php
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SAVEQUERIES', false );
define( 'SCRIPT_DEBUG', false );
define( 'CONCATENATE_SCRIPTS', true );
```

**[Priority]** Hemen

---

## [SEC-004] Dosya Editörü Açık — Arbitrary Code Execution

**[Severity]** Critical  
**[Confidence]** High  
**[Category]** Insecure Configuration / Code Execution  

**[Where]** `public/wp-config.php` — Satır 83

**[What is happening]**  
```php
define( 'DISALLOW_FILE_EDIT', false );  // Allow file editor for testing
```

WordPress'in yerleşik tema ve plugin dosya editörü aktif. Admin panelinden doğrudan PHP kodu düzenlenebilir.

**[Why this is risky]**  
- Admin hesabı ele geçirildiğinde, saldırgan tema/plugin dosyalarına PHP shell ekleyebilir
- XSS veya CSRF ile admin oturumunu kullanan saldırgan kod çalıştırabilir
- İç tehdit: Admin kullanıcı kasıtlı olarak zararlı kod ekleyebilir

**[Attack scenario]**  
1. Saldırgan admin parolasını brute-force ile ele geçirir (xmlrpc + rate limiting yok)
2. wp-admin → Appearance → Theme Editor'a gider
3. `functions.php`'ye reverse shell ekler
4. Sunucuda komut çalıştırır

**[Evidence]**  
- `public/wp-config.php` satır 83: `DISALLOW_FILE_EDIT = false`

**[Fix]**  
```php
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true ); // Plugin/tema kurulumunu da engeller
```

**[Priority]** Hemen

---

## [SEC-005] SQL Dump İçinde Hassas Veriler — Git'e Commit Edilmiş

**[Severity]** Critical  
**[Confidence]** High  
**[Category]** Sensitive Data Exposure  

**[Where]** `sql/local.sql` — 727 satır, 919 KB

**[What is happening]**  
Veritabanı export dosyası hassas bilgiler içeriyor ve Git repository'sine commit edilmiş:

**Kullanıcı bilgileri:**
```sql
INSERT INTO `wp_users` VALUES (1,'root','$2y$10$hEqlH.hMJqiXK6XnboA56.fUcBPebrgP8GFkNVdgs8RRn7qodoyzW','root','dev-email@wpengine.local','http://test.local','2026-03-04 15:48:41','',0,'root');
```

**İçerdiği hassas veriler:**
| Veri Türü | Detay | Risk |
|-----------|-------|------|
| Kullanıcı adı | `root` | Brute-force hedefi |
| Password hash | `$2y$10$hEqlH...` (bcrypt) | Offline brute-force |
| E-posta | `dev-email@wpengine.local` | Phishing, account recovery |
| Session token | `fa6d999e...` hash | Session hijacking |
| IP adresi | `127.0.0.1` (localhost) | Düşük risk |
| User agent | `Mozilla/5.0...Firefox/148.0` | Fingerprinting |
| Admin capabilities | `a:1:{s:13:"administrator";b:1;}` | Rol bilgisi |

**[Evidence]**  
- `sql/local.sql` doğrudan incelendi
- wp_users, wp_usermeta, wp_options tablolarında hassas veriler mevcut

**[Fix]**  
1. **Hemen:** SQL dump'ı `.gitignore`'a ekleyin
2. **Sprint içinde:** Git geçmişinden BFG ile temizleyin
3. **Kalıcı:** SQL dump'ları repository'de saklamayın, secure backup sistemi kullanın

**[Priority]** Hemen

---

## [SEC-006] 108+ Plugin Kurulu — Devasa Saldırı Yüzeyi

**[Severity]** Critical  
**[Confidence]** High  
**[Category]** Attack Surface / Excessive Functionality  

**[Where]** `public/wp-content/plugins/` — 1.4 GB, 108+ plugin dizini

**[What is happening]**  
108'den fazla WordPress plugin'i kurulu ancak yalnızca 1 tanesi (biolink-pro) aktif. İnaktif plugin dosyaları web sunucusu tarafından serve edilmeye devam ediyor.

**Yüksek riskli inaktif plugin'ler:**

| Plugin | Risk | Detay |
|--------|------|-------|
| all-in-one-wp-migration | 🔴 Kritik | 7 unauth AJAX endpoint |
| wp-file-manager | 🔴 Kritik | Unauth REST API backup |
| wpvivid-backuprestore | 🔴 Kritik | Unauth restore |
| backuply | 🔴 Kritik | Unauth restore + unserialize |
| mainwp-child | 🔴 Yüksek | eval() ile RCE |
| code-snippets | 🟠 Yüksek | Arbitrary PHP execution |
| custom-css-js | 🟠 Yüksek | Code injection |
| elementor-pro | 🟠 Yüksek | Unauth form/payment |
| woocommerce | 🟠 Yüksek | Ödeme altyapısı |

**[Why this is risky]**  
- İnaktif plugin'ler bile bilinen CVE'ler üzerinden exploit edilebilir (direct file access)
- Her plugin ek saldırı yüzeyi oluşturur
- 1.4 GB plugin kodu = potansiyel binlerce zafiyet
- Güncelleme yönetimi pratik olarak imkansız

**[Evidence]**  
- `ls public/wp-content/plugins/ | wc -l` = 108+ dizin
- `du -sh public/wp-content/plugins/` = 1.4 GB
- `active_plugins` option'ında sadece `biolink-pro` var

**[Fix]**  
1. **Hemen:** Kullanılmayan tüm plugin dizinlerini silin
2. Sadece gerekli plugin'leri bırakın (biolink-pro + seçilen güvenlik plugin'i)
3. Plugin kurulum sayısını minimize edin

**[Priority]** Hemen

---

## [SEC-012] Varsayılan Tablo Prefix'i `wp_`

**[Severity]** Low  
**[Confidence]** High  
**[Category]** Information Disclosure  

**[Where]** `public/wp-config.php` — Satır 71

**[What is happening]**  
```php
$table_prefix = 'wp_';
```
WordPress'in varsayılan tablo prefix'i kullanılıyor. Bu, SQL injection saldırılarında tablo isimlerinin tahminini kolaylaştırır.

**[Fix]**  
Yeni kurulumda özel prefix kullanın (ör: `wp_a7x_`)

**[Priority]** İzleme

---

## [SEC-015] biolink-pro.zip ve plugin_inventory.json Web Root'ta Erişilebilir

**[Severity]** Medium  
**[Confidence]** High  
**[Category]** Information Disclosure  

**[Where]**  
- `public/wp-content/plugins/biolink-pro.zip` — 643 KB
- `public/wp-content/plugins/plugin_inventory.json` — 38 KB

**[What is happening]**  
Plugin'in ZIP arşivi ve tüm kurulu plugin'lerin envanterini içeren JSON dosyası web root'ta açıkça erişilebilir.

**[Why this is risky]**  
- ZIP dosyası: Plugin kaynak kodu analiz edilebilir, zafiyet araştırması yapılabilir
- JSON dosyası: Tüm kurulu plugin'ler ve sürümleri listelenir, bilinen CVE'ler hedeflenir

**[Fix]**  
1. Her iki dosyayı silin
2. `.htaccess`'te ZIP ve JSON dosyalarına erişimi engelleyin:
```apache
<FilesMatch "\.(zip|json)$">
    Require all denied
</FilesMatch>
```

**[Priority]** Sprint içinde

---

## [SEC-016] DB Charset `utf8` — `utf8mb4` Olması Gerekir

**[Severity]** Low  
**[Confidence]** High  
**[Category]** Data Integrity  

**[Where]** `public/wp-config.php` — Satır 36

**[What is happening]**  
```php
define( 'DB_CHARSET', 'utf8' );
```
`utf8` charset kullanılıyor. WordPress 4.2+ sürümünde `utf8mb4` önerilir.

**[Why this is risky]**  
- 4-byte Unicode karakterler (emoji, bazı Çince/Japonca karakterler) düzgün depolanamaz
- Truncation saldırılarına zemin hazırlayabilir
- WordPress varsayılan kurulumu `utf8mb4` kullanır

**[Fix]**  
```php
define( 'DB_CHARSET', 'utf8mb4' );
```

**[Priority]** İzleme

---

## [SEC-035] Wordfence Key Dosyaları Repository'de

**[Severity]** Medium  
**[Confidence]** High  
**[Category]** Sensitive Data Exposure  

**[Where]**  
- `public/wp-content/plugins/wordfence/lib/noc1.key`
- `public/wp-content/plugins/wordfence/vendor/wordfence/wf-waf/src/rules.key`
- `public/wp-content/plugins/wordfence/vendor/wordfence/wf-waf/src/falsepositive.key`

**[What is happening]**  
Wordfence güvenlik plugin'inin anahtar dosyaları Git repository'sinde. Bu anahtarlar Wordfence'in güvenlik duvarı kurallarını doğrulamak için kullanılır.

**[Why this is risky]**  
- Anahtarların sızması, güvenlik duvarı kurallarının manipüle edilmesine zemin hazırlar
- WAF bypass için kullanılabilir

**[Fix]**  
1. Wordfence kullanılmıyorsa plugin'i silin
2. Kullanılıyorsa key dosyalarını `.gitignore`'a ekleyin

**[Priority]** Sprint içinde
