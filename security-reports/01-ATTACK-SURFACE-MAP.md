# 🗺️ Attack Surface Map & Trust Boundaries

## 1. Repository Yapısı

```
wordpress/
├── public/                          # WordPress web root
│   ├── wp-config.php               # 🔴 Hardcoded credentials, debug ON
│   ├── .htaccess                   # 🟡 Minimal — güvenlik header'ları yok
│   ├── xmlrpc.php                  # 🔴 Aktif — brute-force/DDoS vektörü
│   ├── wp-login.php                # 🟡 Rate limiting yok (WP varsayılan)
│   ├── wp-admin/                   # Admin paneli
│   ├── wp-includes/                # WordPress çekirdek kütüphaneleri
│   ├── wp-cron.php                 # Zamanlı görevler
│   ├── wp-comments-post.php        # Yorum gönderimi
│   ├── wp-signup.php               # Kayıt (kapalı)
│   ├── wp-trackback.php            # Trackback (eski, riskli)
│   └── wp-content/
│       ├── plugins/                # 🔴 108+ plugin (1.4GB) — sadece 1 aktif
│       │   ├── biolink-pro/        # ✅ Aktif plugin — iyi güvenlik
│       │   ├── biolink-pro.zip     # 🟡 ZIP dosyası web root'ta
│       │   ├── plugin_inventory.json # 🟡 Bilgi sızıntısı
│       │   ├── all-in-one-wp-migration/ # 🔴 Unauth AJAX
│       │   ├── wp-file-manager/    # 🔴 Unauth REST API
│       │   ├── wpvivid-backuprestore/ # 🔴 Unauth restore
│       │   ├── backuply/           # 🔴 Unauth restore
│       │   ├── mainwp-child/       # 🔴 eval() kullanımı
│       │   ├── code-snippets/      # 🟠 Arbitrary PHP execution
│       │   ├── custom-css-js/      # 🟠 Code injection
│       │   ├── elementor-pro/      # 🟠 Unauth form/payment
│       │   ├── jetpack/            # 🟠 Unauth endpoints
│       │   ├── woocommerce*/       # 🟠 Ödeme altyapısı
│       │   └── ... (95+ daha)
│       └── themes/
│           ├── twentytwentyfive/   # ✅ Aktif tema — güvenli
│           ├── twentytwentyfour/   # ✅ Resmi tema
│           └── twentytwentythree/  # ✅ Resmi tema
└── sql/
    └── local.sql                   # 🔴 DB dump — user hash, session, email
```

---

## 2. Trust Boundaries (Güven Sınırları)

```
┌─────────────────────────────────────────────────────────┐
│                    INTERNET (Untrusted)                  │
│  ┌─────────────────────────────────────────────────┐    │
│  │              Web Server (Apache/Nginx)           │    │
│  │  ┌───────────────────────────────────────────┐  │    │
│  │  │           WordPress Application           │  │    │
│  │  │  ┌─────────────┐  ┌──────────────────┐   │  │    │
│  │  │  │  Public      │  │  Authenticated   │   │  │    │
│  │  │  │  Endpoints   │  │  Endpoints       │   │  │    │
│  │  │  │             │  │  ┌────────────┐  │   │  │    │
│  │  │  │  - /b/{user}│  │  │  Admin      │  │   │  │    │
│  │  │  │  - xmlrpc   │  │  │  (wp-admin) │  │   │  │    │
│  │  │  │  - REST API │  │  │  - Plugins  │  │   │  │    │
│  │  │  │  - Comments │  │  │  - Themes   │  │   │  │    │
│  │  │  │  - wp-login │  │  │  - Settings │  │   │  │    │
│  │  │  │  - Feed/RSS │  │  │  - BioLink  │  │   │  │    │
│  │  │  └─────────────┘  │  └────────────┘  │   │  │    │
│  │  │                    └──────────────────┘   │  │    │
│  │  │  ┌───────────────────────────────────┐    │  │    │
│  │  │  │         MySQL Database            │    │  │    │
│  │  │  │  User: root / Pass: root          │    │  │    │
│  │  │  └───────────────────────────────────┘    │  │    │
│  │  └───────────────────────────────────────────┘  │    │
│  └─────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────┐    │
│  │              Git Repository (GitHub)             │    │
│  │  🔴 Contains: credentials, keys, salts, DB dump │    │
│  └─────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────┘
```

---

## 3. Kullanıcı Rolleri ve Yetkileri

| Rol | Yetki | Saldırı Yüzeyi |
|-----|-------|-----------------|
| **Anonim Kullanıcı** | BioLink profil görüntüleme, REST API okuma, login sayfası, xmlrpc, yorum gönderme | Brute-force, xmlrpc abuse, REST API enumeration, inaktif plugin exploit |
| **Subscriber** | Profil düzenleme, yorum yazma | Privilege escalation, profil bilgi sızıntısı |
| **Editor** | İçerik yönetimi + `manage_biolink` capability | BioLink profil/link CRUD, IDOR potansiyeli |
| **Administrator** | Tam yetki | Tema/plugin editörü (açık), ayar değişiklikleri |

---

## 4. Kritik Akışlar

### A. Login / Authentication
```
wp-login.php → WordPress auth → Session cookie
```
- **Risk:** Rate limiting yok (WordPress varsayılan), xmlrpc üzerinden brute-force mümkün
- **MFA:** Kurulu değil

### B. BioLink Profil Yönetimi
```
wp-admin → AJAX (blp_save_profile) → Nonce + Capability check → DB write
```
- **Risk:** Düşük — nonce + ownership doğrulama mevcut

### C. BioLink Public Profil Görüntüleme
```
/b/{username} → Frontend template → DB read → HTML render
```
- **Risk:** Orta — Preview mode'da CSS injection olasılığı

### D. BioLink Analytics
```
Public click → AJAX (blp_track_click) → Rate limit check → DB write
```
- **Risk:** Düşük — Rate limiting ve nonce mevcut

### E. Dosya Yükleme (BioLink Avatar)
```
AJAX (blp_upload_avatar) → Nonce + Auth → MIME check → WordPress media handler
```
- **Risk:** Düşük — MIME whitelist, boyut sınırı, WordPress API kullanımı

---

## 5. Dış Bağımlılıklar ve Entegrasyonlar

| Bağımlılık | Risk |
|------------|------|
| WordPress Core | Güncel tutulmalı |
| 108+ Plugin | 🔴 Devasa saldırı yüzeyi (inaktif olsalar bile dosyaları erişilebilir) |
| MySQL (root/root) | 🔴 Zayıf credentials |
| Apache (.htaccess) | 🟡 Minimal konfigürasyon |
| PHP | Sürüm kontrol edilmeli |

---

## 6. Bilgi Sızıntısı Noktaları

| Nokta | Detay | Severity |
|-------|-------|----------|
| Git Repository | DB credentials, auth keys/salts, password hash'ler, session token'lar | 🔴 KRİTİK |
| wp-config.php | Tüm secret'lar açık metin | 🔴 KRİTİK |
| sql/local.sql | Kullanıcı verisi, ayarlar, session bilgisi | 🔴 KRİTİK |
| xmlrpc.php | Kullanıcı enumeration, brute-force | 🟠 YÜKSEK |
| REST API (/wp-json/) | Kullanıcı listesi, post bilgileri | 🟡 ORTA |
| plugin_inventory.json | Kurulu plugin listesi | 🟡 ORTA |
| biolink-pro.zip | Plugin kaynak kodu | 🟡 ORTA |
| WP_DEBUG_DISPLAY | Hata mesajları kullanıcıya gösterilir | 🟠 YÜKSEK |
| WP_DEBUG_LOG | Debug log dosyası yazılır | 🟠 YÜKSEK |
| SAVEQUERIES | SQL sorgu logları | 🟠 YÜKSEK |
| readme.html | WordPress sürüm bilgisi | 🟢 DÜŞÜK |

---

## 7. Endpoint Haritası

### Public (Kimlik Doğrulama Gerektirmeyen)

| Endpoint | Fonksiyon | Risk |
|----------|-----------|------|
| `/` | Ana sayfa | Düşük |
| `/b/{username}` | BioLink profil sayfası | Orta (XSS) |
| `/wp-login.php` | Login formu | Yüksek (brute-force) |
| `/xmlrpc.php` | XML-RPC API | Kritik (brute-force, DDoS) |
| `/wp-json/` | REST API root | Orta (enumeration) |
| `/wp-json/wp/v2/users` | Kullanıcı listesi | Orta (enumeration) |
| `/wp-cron.php` | Zamanlı görevler | Düşük |
| `/wp-comments-post.php` | Yorum gönderimi | Düşük (spam) |
| `/wp-trackback.php` | Trackback | Düşük (spam) |
| `/readme.html` | WP sürüm bilgisi | Düşük |
| `/license.txt` | Lisans | Info |
| `/wp-content/plugins/plugin_inventory.json` | Plugin envanteri | Orta |

### Authenticated (Kimlik Doğrulama Gerekli)

| Endpoint | Fonksiyon | Yetki |
|----------|-----------|-------|
| `/wp-admin/` | Admin paneli | manage_options |
| `/wp-admin/admin-ajax.php` | AJAX handler | varies |
| AJAX: `blp_save_profile` | Profil kaydet | manage_biolink |
| AJAX: `blp_add_link` | Link ekle | manage_biolink |
| AJAX: `blp_upload_avatar` | Avatar yükle | manage_biolink |
| AJAX: `blp_get_analytics` | Analitik görüntüle | manage_biolink |
| AJAX: `blp_track_click` | Tıklama izle | Herkese açık (nonce gerekli) |
