# 🔍 Hypotheses Requiring Manual Verification

Bu bölüm, kod analizi sırasında tespit edilen ancak kesin olarak doğrulanamayan bulguları içerir. Bu hipotezlerin her biri manuel test ve doğrulama gerektirir.

---

## HYP-001: BioLink Pro — Custom CSS Alanı XSS Vektörü

**[Confidence]** Low  
**[Category]** XSS / CSS Injection

**Hipotez:**  
BioLink Pro profil ayarlarında `custom_css` alanı bulunuyor olabilir. Bu alan `sanitize_text_field()` ile temizlense de, CSS context'inde `expression()`, `url()`, `import` gibi direktiflerle JavaScript çalıştırılabilir.

**Doğrulama adımları:**
1. `class-blp-ajax.php`'de `custom_css` alanının nasıl işlendiğini inceleyin
2. `template.php`'de bu alanın `<style>` bloğunda nasıl render edildiğini kontrol edin
3. Preview URL'de `d_custom_css` parametresi ile CSS injection deneyin
4. `wp_strip_all_tags()` kullanılıyor mu kontrol edin

**False positive ihtimali:** Yüksek — BioLink Pro muhtemelen CSS alanı için ek sanitizasyon uygulamaktadır.

---

## HYP-002: BioLink Pro — CSV Export'ta Formula Injection

**[Confidence]** Low  
**[Category]** CSV Injection

**Hipotez:**  
Analytics CSV export fonksiyonu, ziyaretçi kontrollü alanları (referrer URL, user agent) doğrudan CSV satırlarına yazıyor olabilir. `=`, `+`, `-`, `@` ile başlayan değerler Excel'de formül olarak yorumlanır.

**Doğrulama adımları:**
1. `blp_export_analytics_csv` handler'ın tam implementasyonunu inceleyin
2. CSV satırlarının nasıl oluşturulduğunu kontrol edin
3. Referrer URL veya user-agent alanlarının sanitize edilip edilmediğini doğrulayın
4. Test: Zararlı referrer URL ile ziyaret yapın, CSV export edin, Excel'de açın

**False positive ihtimali:** Orta — Birçok WordPress plugin'i CSV sanitizasyonu uygulamaz.

---

## HYP-003: BioLink Pro — Import Fonksiyonunda Mass Assignment

**[Confidence]** Low  
**[Category]** Mass Assignment

**Hipotez:**  
Profil import fonksiyonu (`blp_import_profile`), dışarıdan gelen JSON verisindeki tüm alanları kabul ediyor olabilir. `user_id`, `id`, `is_active`, `page_views` gibi korumalı alanlar da import sırasında güncellenebilir.

**Doğrulama adımları:**
1. `blp_import_profile` handler'ın tam implementasyonunu inceleyin
2. JSON parse sonrası hangi alanların whitelist/blacklist kontrolünden geçtiğini kontrol edin
3. Test: `user_id` veya `page_views` alanlarını manipüle edilmiş JSON ile import deneyin

**False positive ihtimali:** Orta — İyi yazılmış plugin'ler whitelist kullanır.

---

## HYP-004: WordPress REST API User Enumeration

**[Confidence]** Medium  
**[Category]** Information Disclosure

**Hipotez:**  
`/wp-json/wp/v2/users` endpoint'i kimlik doğrulamadan kullanıcı bilgilerini expose ediyor olabilir. Ancak bazı WordPress sürümleri ve güvenlik yapılandırmaları bunu engeller.

**Doğrulama adımları:**
1. Site çalışır durumdayken `/wp-json/wp/v2/users` endpoint'ine GET isteği gönderin
2. Response'da kullanıcı adı, slug, avatar URL gibi bilgilerin döndüğünü kontrol edin
3. Alternatif: `/?author=1` ile kullanıcı enumeration deneyin

**False positive ihtimali:** Düşük — WordPress varsayılan olarak bu endpoint'i açar.

---

## HYP-005: İnaktif Plugin Direct File Access Exploit

**[Confidence]** Medium  
**[Category]** Remote Code Execution

**Hipotez:**  
İnaktif plugin'lerin PHP dosyalarına doğrudan HTTP isteği göndererek, plugin aktif olmasa bile kodun çalıştırılması mümkün olabilir. Özellikle standalone PHP dosyaları (admin-ajax.php harici) bu riski taşır.

**Doğrulama adımları:**
1. İnaktif plugin'lerde `wp-load.php` include etmeyen bağımsız PHP dosyalarını bulun
2. Bu dosyalara doğrudan HTTP isteği gönderin
3. Response'da hata yerine çalışma belirtisi olup olmadığını kontrol edin
4. Özellikle wp-file-manager'ın elFinder arayüzünü kontrol edin

**False positive ihtimali:** Orta — Çoğu modern plugin WordPress bootstrap'ını kontrol eder.

---

## HYP-006: WP-Cron Abuse — DDoS Amplification

**[Confidence]** Medium  
**[Category]** Denial of Service

**Hipotez:**  
`wp-cron.php` dışarıdan tetiklenebilir. Yoğun cron görevleri (backup, scan, cleanup) sunucu kaynaklarını tüketebilir.

**Doğrulama adımları:**
1. `wp-cron.php`'ye doğrudan GET isteği gönderin
2. Response süresi ve sunucu yükünü ölçün
3. `DISABLE_WP_CRON` sabiti tanımlı mı kontrol edin

**False positive ihtimali:** Düşük — wp-cron.php varsayılan olarak public'tir.

---

## HYP-007: wp-content/debug.log Erişilebilirliği

**[Confidence]** Medium  
**[Category]** Information Disclosure

**Hipotez:**  
`WP_DEBUG_LOG = true` olduğu için `wp-content/debug.log` dosyası oluşturulur. Bu dosya .htaccess ile korunmuyor olabilir ve doğrudan HTTP ile erişilebilir.

**Doğrulama adımları:**
1. Site çalışır durumdayken `http://site/wp-content/debug.log` URL'sine erişin
2. Dosyanın içeriğini kontrol edin
3. `.htaccess` veya web sunucu yapılandırmasında .log dosyaları engelleniyor mu kontrol edin

**False positive ihtimali:** Düşük — .gitignore'da `debug.log` var ama web sunucu koruması olmayabilir.

---

## HYP-008: BioLink Pro — Profil Slug Hijacking

**[Confidence]** Low  
**[Category]** Business Logic

**Hipotez:**  
Bir kullanıcı profilinin slug'ını değiştirdiğinde, eski slug serbest kalır ve başka bir kullanıcı tarafından alınabilir. Bu, sosyal mühendislik veya marka taklidi için kullanılabilir.

**Doğrulama adımları:**
1. Profil slug değişikliği yapın
2. Eski slug'ın başka bir kullanıcı tarafından alınabilir olup olmadığını test edin
3. Slug geçmişi veya cooldown mekanizması var mı kontrol edin

**False positive ihtimali:** Orta — Birçok platform bu davranışa izin verir (tasarım kararı olabilir).

---

## HYP-009: BioLink Pro — Link Yönlendirme Open Redirect

**[Confidence]** Low  
**[Category]** Open Redirect

**Hipotez:**  
BioLink profil linklerine tıklandığında, kullanıcı harici URL'ye yönlendirilir. Eğer yönlendirme doğrudan `Location` header'ı ile yapılıyorsa ve URL validasyonu yetersizse, open redirect zafiyeti oluşabilir.

**Doğrulama adımları:**
1. Link URL'lerine `javascript:alert(1)`, `//evil.com`, `data:text/html,...` gibi payloadlar ekleyin
2. Tıklama sonrası yönlendirme davranışını kontrol edin
3. `esc_url_raw()` kullanılıyor mu ve hangi scheme'ler izin veriliyor kontrol edin

**False positive ihtimali:** Yüksek — BioLink Pro `esc_url_raw()` ile sadece http/https scheme'lerine izin veriyor.

---

## Doğrulama Öncelik Sırası

| # | Hipotez | Öncelik | Tahmini Süre |
|---|---------|---------|-------------|
| 1 | HYP-005 | Yüksek | 2 saat |
| 2 | HYP-007 | Yüksek | 15 dakika |
| 3 | HYP-004 | Yüksek | 15 dakika |
| 4 | HYP-002 | Orta | 1 saat |
| 5 | HYP-003 | Orta | 1 saat |
| 6 | HYP-001 | Orta | 1 saat |
| 7 | HYP-006 | Düşük | 30 dakika |
| 8 | HYP-008 | Düşük | 30 dakika |
| 9 | HYP-009 | Düşük | 30 dakika |
