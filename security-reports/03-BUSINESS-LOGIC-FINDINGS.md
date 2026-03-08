# 💼 Business Logic Findings

---

## [SEC-014] BioLink Pro — Analytics View Counter Race Condition

**[Severity]** Low  
**[Confidence]** Medium  
**[Category]** Race Condition  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-database.php` — `increment_page_view()` fonksiyonu

**[What is happening]**  
Profil görüntüleme sayacı (`page_views`) atomik bir UPDATE sorgusu ile artırılmaktadır:

```php
public static function increment_page_view($profile_id) {
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}blp_profiles SET page_views = page_views + 1 WHERE id = %d",
        $profile_id
    ));
}
```

Bu SQL ifadesi MySQL InnoDB'de row-level lock ile çalıştığı için atomiktir. Ancak, aşırı yüksek eşzamanlılık altında (DoS benzeri) performans sorunu yaratabilir.

**[Why this is risky]**  
- Teorik olarak yüksek eşzamanlılıkta sayım kaybı minimal düzeyde olabilir
- Gerçek risk: DoS senaryosunda database lock contention
- İş etkisi: Analytics verilerinde küçük tutarsızlıklar

**[Attack scenario]**  
1. Saldırgan otomatik script ile binlerce profil görüntüleme isteği gönderir
2. Analytics sayıları yapay olarak şişirilir
3. Eşzamanlı istekler database üzerinde yük oluşturur

**Ancak:** Mevcut rate limiting (15 req/60s/IP) bu saldırıyı büyük ölçüde engellemektedir.

**[Evidence]**  
- `class-blp-database.php` dosyasında `page_views + 1` atomik SQL ifadesi
- `class-blp-ajax.php`'de `is_rate_limited()` fonksiyonu mevcut (15 req/60s)
- Analitik kayıtlarında IP hash ile tekil ziyaretçi ayrımı var

**[Fix]**  
1. Mevcut rate limiting yeterli (15/60s)
2. Ek koruma: Redis/Memcached ile queue-based counting
3. Veya: Analitik artırımını wp-cron ile batch processing'e taşıma

**[Priority]** İzleme

---

## [SEC-023] BioLink Pro — Profil Slug Tekrar Kullanımı Kontrolü

**[Severity]** Low  
**[Confidence]** Medium  
**[Category]** Business Logic  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-ajax.php` — `blp_save_profile()` handler

**[What is happening]**  
Profil oluşturma/güncelleme sırasında `username` (slug) alanı `sanitize_key()` ile temizlenir. Mevcut slug benzersizlik kontrolü yapılır, ancak kullanıcının kendi slug'ını değiştirip eski slug'ı serbest bırakması durumunda başka bir kullanıcı o slug'ı alabilir. Bu, SEO ve backlink kayıplarına veya sosyal mühendislik saldırılarına yol açabilir.

**[Why this is risky]**  
- Kullanıcı A `/b/john` slug'ını kullanıyor, paylaşımlar yapıyor
- Kullanıcı A slug'ını `/b/john-new` olarak değiştiriyor
- Kullanıcı B `/b/john` slug'ını alarak eski takipçilere kendi içeriğini gösterebilir

**Varsayım:** Bu davranışın gerçekleşip gerçekleşmeyeceği, slug benzersizlik kontrolünün kapsamına bağlıdır.

**[Evidence]**  
- `sanitize_key()` kullanımı doğru
- Profil veritabanı tablosunda `username` alanı bulunmakta

**[Fix]**  
1. Değiştirilen slug'lar için cooldown süresi (ör: 30 gün)
2. Veya: Eski slug'ların redirect listesine eklenmesi
3. Minimum slug uzunluğu ve rezerve slug listesi

**[Priority]** İzleme

---

## [SEC-024] BioLink Pro — CSV Export'ta Injection Riski (Hipotez)

**[Severity]** Medium  
**[Confidence]** Low  
**[Category]** CSV Injection  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-ajax.php` — `blp_export_analytics_csv()` handler

**[What is happening]**  
Analitik verileri CSV formatında export edilebilir. Eğer export edilen veriler (ör: referrer URL, user agent) içinde `=`, `+`, `-`, `@` gibi karakterlerle başlayan değerler varsa, Excel/LibreOffice gibi uygulamalar bunları formül olarak yorumlayabilir.

**[Why this is risky]**  
- CSV dosyasını açan admin kullanıcının bilgisayarında formül çalıştırılabilir
- `=cmd|'/C calc'!A1` gibi payload'lar komut çalıştırabilir
- Referrer URL veya user-agent alanları saldırgan tarafından kontrol edilebilir

**[Evidence]**  
- AJAX handler `blp_export_analytics_csv` mevcut (handler adından çıkarım)
- Analitik tablosunda `referrer`, `user_agent` alanları var
- Bu alanlar ziyaretçi tarafından kontrol edilebilir

**Not:** Bu bulgu hipotez seviyesindedir. Export fonksiyonunun tam implementasyonu incelenerek doğrulanmalıdır.

**[Fix]**  
CSV export sırasında her hücreye prefix ekleme:
```php
function sanitize_csv_value($value) {
    $first_char = substr($value, 0, 1);
    if (in_array($first_char, ['=', '+', '-', '@', "\t", "\r"])) {
        $value = "'" . $value;  // Tab prefix ile formül engelleme
    }
    return $value;
}
```

**[Priority]** Sprint içinde (doğrulama sonrası)

---

## [SEC-025] BioLink Pro — Profil Import/Export Güvenliği (Hipotez)

**[Severity]** Medium  
**[Confidence]** Low  
**[Category]** Business Logic / Mass Assignment  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-ajax.php` — `blp_import_profile()` ve `blp_export_profile()` handlers

**[What is happening]**  
Profil import/export işlevselliği bulunmaktadır. Import sırasında, dışarıdan gelen JSON/veri yapısının hangi alanları güncelleyebileceği kontrol edilmelidir.

**[Why this is risky]**  
- Import edilen veri `user_id`, `is_active`, `page_views` gibi korumalı alanları içerebilir
- Mass assignment ile başka bir kullanıcının profil ID'si atanabilir
- Export edilen veri hassas bilgi (ör: iç ID'ler) içerebilir

**Not:** Bu, handler'ın detaylı implementasyonu incelenmeden hipotez seviyesindedir.

**[Fix]**  
1. Import sırasında beyaz liste (whitelist) ile sadece izin verilen alanları kabul edin
2. `user_id`, `id`, `is_active`, `page_views` gibi alanları import'ta yok sayın
3. Export'ta hassas alanları filtreleyin

**[Priority]** Sprint içinde (doğrulama sonrası)

---

## [SEC-026] BioLink Pro — Link Tıklama Replay Saldırısı

**[Severity]** Low  
**[Confidence]** Medium  
**[Category]** Replay Attack / Analytics Manipulation  

**[Where]** `public/wp-content/plugins/biolink-pro/includes/class-blp-ajax.php` — `blp_track_click()` handler

**[What is happening]**  
Link tıklama analitik kaydı, AJAX üzerinden gönderilen `link_id` ve `profile_id` değerleriyle yapılır. Her tıklama veritabanına kaydedilir.

**[Why this is risky]**  
- Bir kullanıcı veya bot, aynı link için tekrar tekrar tıklama isteği gönderebilir
- Analytics verileri yapay olarak şişirilir
- Rate limiting (15/60s) mevcut ama IP rotasyonu ile aşılabilir

**[Evidence]**  
- `blp_track_click` AJAX handler mevcut
- Rate limiting: `is_rate_limited('track_click', 15, 60)` uygulanmış
- Nonce doğrulama mevcut

**[Fix]**  
1. Mevcut rate limiting temel koruma sağlıyor
2. Ek: Cookie/fingerprint bazlı tekil sayım
3. Ek: IP + User-Agent + Link ID bazlı deduplikasyon (kısa pencere içinde)
4. Anomali tespiti: Normal trafik desenlerinden sapma alarmı

**[Priority]** İzleme

---

## [SEC-027] WooCommerce Ödeme Altyapısı (İnaktif — Potansiyel Risk)

**[Severity]** Info  
**[Confidence]** Low  
**[Category]** Business Logic / Payment Security  

**[Where]**  
- `public/wp-content/plugins/woocommerce/`
- `public/wp-content/plugins/woocommerce-payments/`
- `public/wp-content/plugins/woocommerce-gateway-stripe/`
- `public/wp-content/plugins/woocommerce-paypal-payments/`

**[What is happening]**  
WooCommerce ve ilgili ödeme plugin'leri kurulu ama inaktif. Dosyaları web root'ta erişilebilir durumda.

**[Why this is risky]**  
- İnaktif plugin dosyaları hâlâ web sunucusu tarafından serve edilir
- Bilinen CVE'ler içeren eski sürümler direct file access ile exploit edilebilir
- Plugin'ler aktifleştirildiğinde ödeme güvenliği test edilmemiş olabilir

**[Fix]**  
1. Kullanılmıyor ise bu plugin'leri tamamen silin
2. Kullanılacaksa en güncel sürümlere güncelleyin
3. PCI DSS uyumluluğunu kontrol edin

**[Priority]** Sprint içinde
