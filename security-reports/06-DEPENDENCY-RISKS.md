# 📦 Dependency & Supply Chain Risks

---

## [SEC-036] 108+ Plugin — Yönetilemez Bağımlılık Zinciri

**[Severity]** High  
**[Confidence]** High  
**[Category]** Supply Chain Risk / Attack Surface  

**[Where]** `public/wp-content/plugins/` — 108+ plugin dizini

**[What is happening]**  
108'den fazla WordPress plugin'i kurulu. Bu plugin'lerin her biri kendi bağımlılık zincirine sahip ve birçoğu eski sürümlerde kalmış olabilir. Sadece 1 tanesi (biolink-pro) aktif.

**Tüm kurulu plugin listesi (108+):**

| # | Plugin | Kategori | Risk Notu |
|---|--------|----------|-----------|
| 1 | advanced-custom-fields | İçerik | Orta |
| 2 | akismet | Anti-spam | Düşük |
| 3 | all-in-one-seo-pack | SEO | Orta |
| 4 | **all-in-one-wp-migration** | Backup | 🔴 Unauth AJAX |
| 5 | **all-in-one-wp-security-and-firewall** | Güvenlik | 🔴 SQL Injection |
| 6 | antispam-bee | Anti-spam | Düşük |
| 7 | astra-sites | Tema | Orta |
| 8 | autoptimize | Performans | Orta (SSRF riski) |
| 9 | **backuply** | Backup | 🔴 Unauth restore |
| 10 | bdthemes-element-pack | Elementor | Orta |
| 11 | better-search-replace | DB | Yüksek (DB manipulation) |
| 12 | better-wp-security | Güvenlik | Orta |
| 13 | **biolink-pro** | Link | ✅ Aktif — İyi güvenlik |
| 14 | biolink-pro.zip | Arşiv | 🟡 Bilgi sızıntısı |
| 15 | breadcrumb-navxt | Navigasyon | Düşük |
| 16 | classic-editor | Editör | Düşük |
| 17 | classic-widgets | Widget | Düşük |
| 18 | click-to-chat-for-whatsapp | İletişim | Düşük |
| 19 | **code-snippets** | Geliştirme | 🟠 Arbitrary PHP |
| 20 | coming-soon | Bakım | Orta |
| 21 | complianz-gdpr | Uyumluluk | Düşük |
| 22 | contact-form-7 | Form | Orta |
| 23 | cookie-law-info | Uyumluluk | Düşük |
| 24 | cookie-notice | Uyumluluk | Düşük |
| 25 | creame-whatsapp-me | İletişim | Düşük |
| 26 | **custom-css-js** | Geliştirme | 🟠 Code injection |
| 27 | custom-post-type-ui | İçerik | Orta |
| 28 | disable-comments | İçerik | Düşük |
| 29 | duplicate-page | İçerik | Düşük |
| 30 | duplicate-post | İçerik | Düşük |
| 31 | duplicate-wp-page-post | İçerik | Düşük |
| 32 | duplicator | Backup | Orta |
| 33 | duracelltomi-google-tag-manager | Analytics | Düşük |
| 34 | elementor | Page Builder | Orta |
| 35 | **elementor-pro** | Page Builder | 🟠 Unauth form/payment |
| 36 | elementskit-lite | Elementor | Orta |
| 37 | essential-addons-for-elementor-lite | Elementor | Orta |
| 38 | ewww-image-optimizer | Medya | Orta (image processing) |
| 39 | flamingo | Mesaj | Düşük |
| 40 | fluentform | Form | Orta |
| 41 | google-analytics-for-wordpress | Analytics | Düşük |
| 42 | google-listings-and-ads | E-ticaret | Orta |
| 43 | google-site-kit | Analytics | Orta |
| 44 | google-sitemap-generator | SEO | Düşük |
| 45 | gtranslate | Çeviri | Orta |
| 46 | header-footer-elementor | Elementor | Orta |
| 47 | hello-dolly | Demo | Düşük |
| 48 | hostinger | Hosting | Orta |
| 49 | hostinger-reach | Hosting | Orta |
| 50 | image-optimization | Medya | Düşük |
| 51 | imagify | Medya | Düşük |
| 52 | insert-headers-and-footers | Kod | Orta |
| 53 | instagram-feed | Sosyal | Orta |
| 54 | **jetpack** | Çok amaçlı | 🟠 Unauth endpoints |
| 55 | limit-login-attempts-reloaded | Güvenlik | Düşük |
| 56 | litespeed-cache | Performans | Orta |
| 57 | loco-translate | Çeviri | Düşük |
| 58 | loginizer | Güvenlik | Orta (unserialize) |
| 59 | mailchimp-for-wp | E-posta | Orta |
| 60 | maintenance | Bakım | Düşük |
| 61 | **mainwp-child** | Yönetim | 🔴 eval() RCE |
| 62 | metform | Form | Orta |
| 63 | metform-pro | Form | Orta |
| 64 | one-click-demo-import | İçerik | Yüksek |
| 65 | optinmonster | Pazarlama | Orta |
| 66 | polylang | Çeviri | Orta |
| 67 | popup-maker | Pazarlama | Orta |
| 68 | premium-addons-for-elementor | Elementor | Orta |
| 69 | really-simple-ssl | Güvenlik | Orta (unserialize) |
| 70 | redirection | SEO | Düşük |
| 71 | redux-framework | Framework | Orta |
| 72 | regenerate-thumbnails | Medya | Düşük |
| 73 | royal-elementor-addons | Elementor | Orta |
| 74 | safe-svg | Medya | Orta (SVG=XSS riski) |
| 75 | seo-by-rank-math | SEO | Orta |
| 76 | seo-by-rank-math-pro | SEO | Orta |
| 77 | sg-cachepress | Performans | Düşük |
| 78 | sg-security | Güvenlik | Düşük |
| 79 | smart-slider-3 | İçerik | Orta |
| 80 | sucuri-scanner | Güvenlik | Düşük |
| 81 | svg-support | Medya | Orta (SVG=XSS riski) |
| 82 | tablepress | İçerik | Düşük |
| 83 | the-events-calendar | İçerik | Orta |
| 84 | tinymce-advanced | Editör | Düşük |
| 85 | uicore-animate | Animasyon | Düşük |
| 86 | uicore-elements | Elementor | Orta |
| 87 | uicore-framework | Framework | Orta |
| 88 | ultimate-addons-for-gutenberg | Editör | Orta |
| 89 | under-construction-page | Bakım | Düşük |
| 90 | updraftplus | Backup | Orta |
| 91 | user-role-editor | Güvenlik | Yüksek (rol yönetimi) |
| 92 | w3-total-cache | Performans | Orta |
| 93 | **woocommerce** | E-ticaret | 🟠 Ödeme altyapısı |
| 94 | woocommerce-gateway-stripe | Ödeme | 🟠 Stripe entegrasyonu |
| 95 | woocommerce-payments | Ödeme | 🟠 Ödeme işlemleri |
| 96 | woocommerce-paypal-payments | Ödeme | 🟠 PayPal entegrasyonu |
| 97 | wordfence | Güvenlik | Düşük (key dosyaları) |
| 98 | wordpress-importer | İçerik | Orta |
| 99 | wordpress-seo | SEO | Düşük |
| 100 | worker | Yönetim | Orta |
| 101 | **wp-file-manager** | Dosya | 🔴 Unauth REST API |
| 102 | wp-mail-smtp | E-posta | Orta |
| 103 | wp-multibyte-patch | Uyumluluk | Düşük |
| 104 | wp-optimize | Performans | Orta |
| 105 | wp-reviews-plugin-for-google | Değerlendirme | 🟠 unserialize() |
| 106 | wp-smushit | Medya | Düşük |
| 107 | wp-super-cache | Performans | Düşük |
| 108 | wpforms-lite | Form | Orta |
| 109 | wps-hide-login | Güvenlik | Düşük |
| 110 | **wpvivid-backuprestore** | Backup | 🔴 Unauth restore |

**[Fix]**  
1. **Hemen:** Tüm inaktif plugin'leri silin
2. Sadece biolink-pro ve 1 güvenlik plugin'i bırakın
3. Plugin ekleme politikası oluşturun (güvenlik incelemesi zorunluluğu)

**[Priority]** Hemen

---

## [SEC-037] Duplicate/Redundant Plugin'ler

**[Severity]** Medium  
**[Confidence]** High  
**[Category]** Excessive Functionality  

**[Where]** `public/wp-content/plugins/`

**[What is happening]**  
Aynı işlevi gören birden fazla plugin kurulu:

| İşlev | Plugin'ler | Sayı |
|-------|-----------|------|
| Cache/Performans | w3-total-cache, wp-fastest-cache, litespeed-cache, wp-super-cache, sg-cachepress, autoptimize, wp-optimize | 7 |
| Güvenlik | wordfence, all-in-one-wp-security-and-firewall, sucuri-scanner, better-wp-security, sg-security, loginizer, limit-login-attempts-reloaded | 7 |
| Backup | all-in-one-wp-migration, wpvivid-backuprestore, backuply, updraftplus, duplicator | 5 |
| SEO | all-in-one-seo-pack, wordpress-seo (Yoast), seo-by-rank-math, seo-by-rank-math-pro, google-sitemap-generator | 5 |
| Cookie/GDPR | complianz-gdpr, cookie-law-info, cookie-notice | 3 |
| Duplicate Content | duplicate-page, duplicate-post, duplicate-wp-page-post | 3 |
| Çeviri | gtranslate, polylang, loco-translate | 3 |
| SVG | safe-svg, svg-support | 2 |
| Medya Optimizasyon | ewww-image-optimizer, imagify, wp-smushit, image-optimization | 4 |

**[Why this is risky]**  
- Çakışan plugin'ler beklenmedik davranışlara yol açabilir
- Her biri ayrı güncelleme ve güvenlik yönetimi gerektirir
- Saldırı yüzeyini gereksiz yere genişletir

**[Fix]**  
Her kategoride yalnızca 1 plugin bırakın ve diğerlerini silin.

**[Priority]** Sprint içinde

---

## [SEC-038] PEM/Certificate Dosyaları Repository'de

**[Severity]** Low  
**[Confidence]** High  
**[Category]** Information Disclosure  

**[Where]**  
- `public/wp-content/plugins/gtranslate/url_addon/cacert.pem`
- `public/wp-content/plugins/wpvivid-backuprestore/vendor/guzzle/guzzle/src/Guzzle/Http/Resources/cacert.pem`
- `public/wp-content/plugins/smart-slider-3/Nextend/Framework/Misc/cacert.pem`
- `public/wp-content/plugins/w3-total-cache/lib/SNS/lib/requestcore/cacert.pem`
- `public/wp-content/plugins/duplicator/vendor/requests/library/Requests/Transport/cacert.pem`
- `public/wp-content/plugins/wordfence/vendor/wordfence/wf-waf/src/cacert.pem`
- `public/wp-content/plugins/metform-pro/core/integrations/payment/ipn/cert/cacert.pem`
- `public/wp-content/plugins/updraftplus/includes/cacert.pem`

**[What is happening]**  
Çeşitli plugin'ler CA certificate bundle dosyaları içeriyor. Bunlar genellikle HTTPS bağlantıları için kullanılan güvenilir CA listelerini içerir.

**[Why this is risky]**  
- cacert.pem dosyaları genellikle risk oluşturmaz (public CA listesi)
- Ancak eski/güncelliğini yitirmiş CA listeleri, revoke edilmiş sertifikalara güvenmeye devam edebilir
- Bazı eski Guzzle sürümleri güvenlik açıkları içerebilir

**[Fix]**  
1. Kullanılmayan plugin'leri silmek bu dosyaları da kaldıracaktır
2. Kullanılan plugin'leri güncel tutun

**[Priority]** İzleme

---

## [SEC-039] error_reporting(0) Kullanımı — Hata Gizleme

**[Severity]** Low  
**[Confidence]** High  
**[Category]** Insecure Error Handling  

**[Where]**  
- `public/wp-content/plugins/gtranslate/url_addon/gtranslate.php` — Satır 2
- `public/wp-content/plugins/mainwp-child/class/class-mainwp-child.php`
- `public/wp-content/plugins/mainwp-child/class/class-mainwp-security.php`

**[What is happening]**  
Bu plugin'ler `error_reporting(0)` çağrısıyla hata raporlamayı tamamen kapatıyor. Bu, güvenlik açıklarını gizleyebilir ve hata ayıklamayı zorlaştırır.

**[Why this is risky]**  
- Güvenlik hataları sessizce yutulur
- Saldırı girişimleri loglanmaz
- Undefined behavior fark edilmez

**[Fix]**  
Plugin'leri silin veya hata yönetimini WordPress'in yerleşik mekanizmasına bırakın.

**[Priority]** İzleme
