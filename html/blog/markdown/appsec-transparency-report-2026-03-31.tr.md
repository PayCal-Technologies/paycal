---
title: Uygulama Güvenliği Şeffaflık Raporu
date: 2026-03-31
author: PayCal Güvenlik
tags: security, appsec, billing_hardening
---

## Rapor Meta Verileri

◆ Tarih: 2026-03-31
◆ Kapsam: İstek yönetimi, yönlendirmeler, API korumaları ve güven sınırları
◆ Referans: İç güvenlik denetimi (2026-03-31)

## Genel Bakış

Yakın zamanda modern web uygulamalarını etkileyen gerçek dünya saldırı vektörlerine odaklanan bir uygulama güvenliği incelemesini tamamladık. Bu çalışma, normal ürün davranışını bozmadan **pratik risk azaltımına** öncelik verdi.

Bu belge, neyin tespit edildiğini, neyin değiştirildiğini ve süregelen güvenliği nasıl ele aldığımızı açıklar.

### Tetikleyici Olay ve Dış Raporlar

Bugün npm Axios paketinin ele geçirildiğine dair onaylı raporlar tarafından uyarıldık. Bu uyarı, bu tam denetim ve dahili sistem tarama döngüsünü doğrudan tetikledi.

Dış teknik referanslar:
◆ BleepingComputer: [Bilgisayar korsanları çapraz platform kötü amaçlı yazılım yaymak için Axios npm paketini ele geçirdi](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [Axios tedarik zinciri saldırısı, ele geçirilen npm hesabı aracılığıyla çapraz platform RAT yayıyor](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Tedarik zinciri patlaması: RAT yüklemek için arka kapılı popüler npm paketi](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Temel Bulgular

Üç önemli güvenlik riskini tespit edip giderdik:

◆ Yönlendirme yönetimi: açık yönlendirme vektörü (düzeltildi)
◆ Başlık güveni: Host/başlık zehirlenmesi (düzeltildi)
◆ API koruması: eksik CSRF kontrolleri (düzeltildi)

## Neleri Düzelttik

### 1) Yönlendirme Güvenliği (dil değiştirme)

**Sorun**
Yönlendirmeler, eksik veya manipüle edilebilir olan `HTTP_REFERER` üzerine dayanıyordu. Bu, güvenilir alanlar kullanılarak potansiyel kimlik avı zincirlerini oluşturuyordu.

**Çözüm**
◆ Katı ana bilgisayar doğrulaması uygulandı
◆ Yalnızca dahili veya aynı kaynaklı yönlendirmelere izin verilir
◆ Doğrulama başarısız olduğunda varsayılan olarak `/` adresine geri dönme

**Sonuç**
Yönlendirmeler artık **açıkça güvenilen kaynaklarla sınırlı**.

### 2) Başlık Güven Sınırları (faturalama akışları)

**Sorun**
İletilen başlıklar (örn. host/proto), istek kaynağını doğrulamadan kaynak mantığını etkiliyordu. Yanlış yapılandırma, ana bilgisayar manipülasyonuna izin verebilirdi.

**Çözüm**
◆ **Güvenilir proxy kontrolü** tanıtıldı
◆ İletilen başlıklar yalnızca bilinen altyapıdan kabul edilir
◆ Diğer tüm durumlar uygulamanın kanonik kaynağına geri döner

**Sonuç**
Kaynak yönetimi artık **deterministik ve başlık sahteciliğine dirençli**.

### 3) CSRF Koruması (faturalama eylemleri)

**Sorun**
Kimliği doğrulanmış faturalama uç noktaları CSRF doğrulamasından yoksundu. Bu, mutasyon uç noktalarını geçerli oturumlar altında siteler arası istek sahteciliğine maruz bırakıyordu.

**Çözüm**
◆ Tüm faturalama mutasyonlarına CSRF doğrulaması uygulandı
◆ Merkezi token doğrulama mantığı
◆ Ön uç tutarlı bir şekilde token gönderiyor

**Sonuç**
Durum değiştiren tüm faturalama işlemleri artık **açıkça kullanıcı tarafından başlatılmış istekler** gerektirmektedir.

## Ek İnceleme

### Komut Yürütme Yüzeyleri

Yürütme temelleri (örn. shell/exec) içeren kod yollarını inceledik.

**Mevcut Durum**
◆ Denetleyiciler veya genel rotalar aracılığıyla etkin tehlike yok
◆ İstek yollarında çalışma zamanı çağrısı kanıtı yok

**Konum**
◆ **Yalnızca genel olmayan dahili araçlar** olarak değerlendirin
◆ Gelecekteki kaldırma veya yalıtım için aday

## Doğrulama

Tüm değişiklikler şunlar aracılığıyla doğrulandı:

◆ Değiştirilen dosyalarda PHP lint
◆ Statik editör tanılamaları
◆ İstek akışlarının manuel incelemesi

Herhangi bir sözdizimi veya çalışma zamanı sorunu oluşturulmadı.

## Uygulanan Güvenlik İlkeleri

Bu sağlamlaştırma bazı temel ilkeleri yeniden doğrulamaktadır:

◆ **Varsayılan olarak reddetme** örtük güvene karşı
◆ **Açık güven sınırları** (örn. proxy'ler, kaynaklar)
◆ **Her harici giriş noktasında doğrulama**
◆ **Merkezi güvenlik kontrolleri** dağınık kontrollere karşı

## Bu Kullanıcılar İçin Ne Anlama Geliyor

◆ Yönlendirme kötüye kullanımı yoluyla kimlik avı riski azaldı
◆ Faturalama eylemleri etrafında daha güçlü garantiler
◆ İstek yönetimi ve kaynak doğrulamada geliştirilmiş bütünlük

Kullanıcıların herhangi bir işlem yapması gerekmemektedir.

## Devam Eden Çalışma

Güvenliği süregelen bir süreç olarak ele alıyoruz. Sonraki adımlar şunları içerir:

◆ Entegrasyon testleri: yönlendirme doğrulama davranışı
◆ Entegrasyon testleri: uç noktalarda CSRF uygulaması
◆ Entegrasyon testleri: proxy güven sınırı yönetimi
◆ Periyodik taramalar: yönlendirme çukurları
◆ Periyodik taramalar: başlık güven gerilemeleri
◆ Yüksek riskli rotaların dahili triyajı

## Güncellenen Dosyalar

◆ `html/lang/index.php`
◆ `html/src/Controllers/BillingController.php`
◆ `html/js/core/billing.js`

## Kapanış Notu

Bu çalışma, teorik uç durumlar değil, **gerçekçi istismar yollarını** ortadan kaldırmaya odaklanmıştır. Ürün güvenilirliğini korurken güvenliği önemli ölçüde iyileştiren değişikliklere öncelik vermeye devam edeceğiz.
