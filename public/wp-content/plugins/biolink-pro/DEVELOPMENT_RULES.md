# BioLink Pro — Geliştirme Kuralları ve Kısıtlamalar

Bu dosya, BioLink Pro WordPress eklentisinin geliştirme sürecinde uyulması zorunlu kuralları tanımlar.
**Bu kuralların dışına çıkılmaz.**

---

## 1. Kapsam

- Tüm değişiklikler yalnızca `wp-content/plugins/biolink-pro/` dizini içinde yapılır.
- WordPress çekirdeği, tema dosyaları veya başka eklentiler **asla** değiştirilmez.
- Plugin, zip yapılıp başka bir WordPress kurulumuna yüklendiğinde **sıfır kurulum gerektirmeden** çalışmalıdır.

---

## 2. WordPress Standartları

- Her PHP dosyasının en üstünde `defined('ABSPATH') || exit;` bulunmalıdır.
- Tüm veritabanı sorguları `$wpdb->prepare()` ile hazırlanmalıdır. Ham SQL yasaktır.
- `wpdb->insert()`, `wpdb->update()`, `wpdb->delete()` ile sadece WordPress API kullanılır.
- Tablo adları daima `$wpdb->prefix` öneki ile kullanılır.
- Tüm AJAX işleyicileri `check_ajax_referer()` veya `wp_verify_nonce()` ile doğrulanmalıdır.
- Kullanıcı girdileri her zaman sanitize edilir; çıktılar her zaman escape edilir:
  - DB'ye yazmadan önce: `sanitize_text_field()`, `sanitize_textarea_field()`, `esc_url_raw()`
  - HTML çıktısı: `esc_html()`, `esc_attr()`, `esc_url()`
  - JS çıktısı: `esc_js()`

---

## 3. Güvenlik Zorunlulukları

- Her yazma AJAX isteğinde nonce doğrulaması zorunludur.
- Link/profil işlemlerinde sahiplik doğrulaması (ownership check) zorunludur — bir kullanıcı başka kullanıcının verilerine erişemez.
- Admin menü yetki seviyesi: `manage_options` (veya özel `manage_biolink` capability).
- URL'ler `esc_url_raw()` ile sanitize edilmeli, `javascript:` ve `data:` scheme'leri reddedilmelidir.
- `sanitize_url()` kullanılmaz (deprecated + güvensiz).
- XSS önleme: tüm kullanıcı çıktıları escape edilir, `wp_kses()` veya `esc_html()` zorunludur.
- SQL injection önleme: `$wpdb->prepare()` dışında SQL çalıştırılamaz.
- Rate limiting: public tracking endpoint'leri IP bazlı throttle uygulamalıdır.
- Preview modu yalnızca oturum açmış kullanıcılara açık olmalıdır.

---

## 4. Dosya Yapısı Kuralları

```
biolink-pro/
├── biolink-pro.php          (ana plugin dosyası)
├── DEVELOPMENT_RULES.md     (bu dosya)
├── index.php                (silence is golden)
├── includes/                (PHP sınıfları)
├── admin/
│   ├── css/                 (admin stilleri)
│   ├── js/                  (admin JavaScript)
│   └── views/               (admin PHP görünümleri)
└── public/
    ├── css/                 (frontend stilleri)
    ├── js/                  (frontend JavaScript)
    └── template.php         (public BioLink sayfası)
```

- Her dizinde `index.php` (silence) bulunmalıdır.
- Harici CDN script'leri yalnızca admin tarafında ve `wp_enqueue_script()` ile yüklenir.
- Üçüncü parti kütüphaneler mümkünse `vendor/` altında veya CDN'den versiyonlu yüklenir.

---

## 5. Kütüphane Kullanım Politikası

- **SortableJS**: Drag-drop sıralama için — CDN, versiyonlu
- **Chart.js**: Analitik grafikleri için — CDN, versiyonlu
- **Google Fonts**: Yazı tipleri için — CDN
- **Tailwind CSS**: Admin arayüzünde kullanılabilir (CDN, sadece admin sayfasında)
- Yeni kütüphane eklemeden önce önce mevcut WordPress API'leri değerlendirilir.

---

## 6. Aktivasyon / Deaktivasyon

- `register_activation_hook`: DB tabloları oluşturulur, rewrite rules flush edilir.
- `register_deactivation_hook`: Rewrite rules flush edilir.
- `register_uninstall_hook`: Tüm tablolar ve options silinir.
- Aktivasyon hook'u sırasında `init` hook'u tetiklenmez; rewrite rule'lar `add_rewrite_rule()` + `flush_rewrite_rules()` çiftiyle yönetilir.

---

## 7. Sürüm Yönetimi

- Her önemli değişiklikte `BLP_VERSION` sabiti güncellenir.
- DB şema değişikliklerinde `blp_db_version` option'ı kontrol edilerek migration çalıştırılır.
- Enqueue edilen asset'ler `BLP_VERSION` ile versiyonlanır (cache busting).

---

## 8. Kod Stili

- PSR-4 sınıf isimlendirme: `BLP_ClassName`
- Method isimlendirme: `snake_case`
- Sabitler: `SCREAMING_SNAKE_CASE`
- Her PHP dosyası `defined('ABSPATH') || exit;` ile başlar.
- Hiçbir dosyada `error_reporting(0)` veya `@` hata bastırma kullanılmaz.
