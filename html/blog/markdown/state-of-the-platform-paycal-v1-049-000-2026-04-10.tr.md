---
title: Platformun Durumu: PayCal Sürüm 1.049.000
date: 2026-04-10
author: PayCal Ekibi
tags: release, accessibility, privacy, security, premium
---

## Genel Bakış

PayCal Sürüm 1.049.000, önemli bir mimari kilometre taşıdır. Platform artık profesyonel emek takibi için deny-safe bir ortam olarak çalışıyor; gizlilik egemenliği ve radikal erişilebilirlik ürünün çekirdek davranışına gömülü durumda.

Matematiksel olarak doğrulanmış 945 dosyalık bir kod tabanıyla bu sürüm, hızlı özellik genişlemesinden kalıcı platform kararlılığına geçişi temsil ediyor.

## Erişilebilirlik artık doğrulanabilir

10 Nisan 2026 itibarıyla WCAG Theme Contrast Matrix, tüm görsel sistem genelinde tam başarı oranını doğruluyor.

◆ 2.040 kontrol noktasında 68 tema tarandı
◆ Tüm tema tokenlarında minimum 4,75:1 kontrast eşiği uygulanıyor
◆ Matrix (15,56:1) ve Akira (14,02:1) dahil tüm seçilebilir tasarımlar kapsanıyor

Sonuç, tema tercihi ne olursa olsun tutarlı okunabilirliktir.

## Gizlilik egemenliği: üç güvenlik sütunu

### 1) Sadece Passkey kimlik doğrulama (Workstream G)

PayCal, browser-credential bridge kaldırma sürecini tamamladı ve artık yalnızca passkey ile çalışıyor.

◆ Parola veritabanı maruziyeti riski yok
◆ WebAuthn + HKDF yerelde bir Key Encryption Key (KEK) türetiyor
◆ Sunucu yalnızca sarılmış anahtar materyali alıyor

### 2) Otomatik Veri Temizleme (Workstream D)

Hassas durum verisi bilinçli olarak kısa ömürlü tutulur.

◆ Sekme gizleme ve sayfadan ayrılma DOM Sensitivity Scrub tetikler
◆ Güvenlik anahtarları ve hassas çalışma durumu bellekten temizlenir
◆ Veri saklama, katı gereklilik sınırlarına göre yürütülür

### 3) Privacy Guard telemetrisi (Workstream B)

Operasyonel gözlemlenebilirlik, kimlik sızıntısı olmadan korunur.

◆ Telemetri anonimleştirilmiştir
◆ Rastgele jitter ile toplu iletim yapılır
◆ Loglar, oturum veya kazanç olaylarıyla korelasyonu önleyecek şekilde tasarlanır

## Profesyonel araç seti öne çıkanlar

### AriaEcho Narration

Erişilebilirlik odaklı anlatım, ham zaman ve ücret kayıtlarını yardımcı iş akışları için doğal ve profesyonel dile dönüştürür.

### Private Math (yerel vergi motoru)

Vergi hesaplamaları tamamen tarayıcı içinde yürütülür; hassas gelir hesapları uzak sunuculara gitmez.

### Profesyonel dışa aktarma

PDF, CSV ve metin dışa aktarmaları tek tıkla alınabilir. Export Identity Inversion, rapor başlıklarında temizlenmiş geçici kimlik kullanır ve indirme sonrası hemen siler.

### Safety Net Recovery

Orphaned Work Recovery, site silinmeleri sonrası bağlantısı kopan kayıtları tespit eder ve tarihsel sürekliliği korumak için yeniden bağlantıyı destekler.

## Premium: ödünsüz iş birliği

Premium organizasyon özellikleri, bireysel gizlilik sınırlarını korurken daha güçlü operasyonel kontrol sunar.

◆ İşveren ve ekip akışları için Organization Hub
◆ Granüler izinler içeren geliştirilmiş rol kapsam modeli
◆ Yönetim gözetimi için devredilmiş takvim görünümleri
◆ Sayfa ziyareti anında şifreleme hazırlığı için DEK Auto-Bootstrap

## Kapanış

PayCal v1.049.000 yalnızca bir sürüm artışı değildir. Erişilebilir tasarım, gizlilik egemenliği ve kullanıcı kontrollü veri işleme için platform düzeyinde bir taahhüttür.

Güvenli. Erişilebilir. Senin. Bu, PayCal.
