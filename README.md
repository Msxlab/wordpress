# WordPress App Repository

Bu klasör, Local ortamında çalışan WordPress uygulamasının tam sistem dosyalarını içerir.

## Repoda Yer Alanlar

- `public/`: WordPress web root ve çekirdek dosyaları
- `public/wp-content/plugins/`: kurulu eklentiler
- `public/wp-content/themes/`: temalar
- `sql/`: mevcut veritabanı dışa aktarma dosyaları

## GitHub İçin Hariç Tutulanlar

Bu repoda yalnızca geçici veya makineye özel dosyalar `.gitignore` ile dışarıda bırakılmıştır:

- `public/local-xdebuginfo.php`
- `public/wp-content/debug.log`
- cache / backup / upgrade klasörleri
- editor klasörleri ve geçici log dosyaları

## Kurulum

1. Repoyu klonlayın.
2. Web sunucunuzun kökünü `public/` klasörüne yönlendirin.
3. Gerekirse `public/wp-config.php` içindeki ortam ayarlarını kendi sunucunuza göre güncelleyin.
4. Gerekirse `sql/` içindeki veritabanı export'unu içe aktarın.
5. Eklenti ve tema durumlarını WordPress panelinden kontrol edin.

## GitHub'a Gönderme

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin <REPO_URL>
git push -u origin main
```

## Notlar

- Bu klasör tam bir WordPress kurulumu içerdiği için repo boyutu büyük olabilir.
- Bu repoda WordPress çekirdeği, eklentiler, temalar ve mevcut sistem dosyaları birlikte tutulur.
- `wp-config.php` ve veritabanı export dosyaları bulunabileceği için bu repoyu private tutmanız önerilir.
