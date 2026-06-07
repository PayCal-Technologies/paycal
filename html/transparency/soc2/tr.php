<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'PayCal SOC 2 Uyumlulugu - [PayCal]';
$pageLabel = 'PayCal SOC 2 Uyumlulugu';

require_once HTML . '/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Şeffaflık Merkezi</a>
    <span class="separator">/</span>
    <span class="current">PayCal'da SOC 2 Uyumluluğu</span>
  </nav>

  <header class="doc-article-header">
    <h1>PayCal SOC 2 Hazırlığı ve Güvenlik Modeli</h1>
    <p class="deck">PayCal'ın SOC 2 kontrollerini zorunlu sistem davranışlarına ve sürekli üretilen kanıtlara nasıl eşlediğine dair teknik bir bakış.</p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-15">2026-04-15</time></p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>1. Genel Bakış</h2>
      <p>PayCal, yalnızca politika beyanlarına değil, doğrulanabilir uygulamaya ve izlenebilir kanıtlara odaklanan SOC 2 uyumlu bir güvenlik programı işletmektedir.</p>
      <ul class="doc-fact-list">
        <li><strong>Kapsam dahilindeki kontroller:</strong> CC1-CC9</li>
        <li><strong>Mevcut bundle'daki artefaktlar:</strong> 37</li>
        <li><strong>Kontrol-artefakt eşlemeleri:</strong> 26</li>
        <li><strong>Kanıt tazelik penceresi:</strong> 35 gün</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>2. Kontrol Kapsamı (CC1-CC9)</h2>
      <p>Kapsamdaki tüm SOC 2 Common Criteria kontrolleri (CC1'den CC9'a kadar) aylık bundle'daki tutulan kanıtlarla eşlenmiştir.</p>
      <p>Bu eşleme, kontrol hedefinden inceleme için kullanılan somut artefaktlara doğrudan izlenebilirliği destekler.</p>
    </section>

    <section class="doc-section">
      <h2>3. Kontroller Nasıl Uygulanır</h2>
      <p>PayCal, uygulamayı bir sistem özelliği olarak ele alır. Kontroller yalnızca belgelenmekle kalmaz, programatik olarak uygulanır.</p>
      <ul class="doc-fact-list">
        <li><strong>Kimlik doğrulama:</strong> Kimlik avına karşı direnci güçlendirmek için passkey destekli kimlik doğrulama akışı.</li>
        <li><strong>Çalışma zamanı bütünlüğü:</strong> Operasyonel durum yönetimiyle çalışma zamanı bütünlüğü izleme.</li>
        <li><strong>Çıktı sertleştirme:</strong> Hassas DOM/çıktı yolları için Guardian sanitizasyon kontrolleri.</li>
        <li><strong>Kalite kapısı:</strong> Bundle kanıtları kabul edilmeden önce otomatik tam suite PHPUnit kapısı.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>4. Değişiklik Yönetimi &amp; Test</h2>
      <p>Değişiklik yönetimi, izlenen değişiklikler, onaylar ve test kanıtlarıyla CC8'e hizalanmıştır.</p>
      <ul class="doc-fact-list">
        <li><strong>Değişiklik kayıtları:</strong> 12</li>
        <li><strong>Onay kayıtları:</strong> 10</li>
        <li><strong>Test sonuçları:</strong> 1528 test, 8351 assertion (geçti)</li>
        <li><strong>Test-kontrol izleme:</strong> 5 suite, 5 geçti, 8 bağlı test dosyası</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>5. Denetim İzi &amp; Kanıt Bütünlüğü</h2>
      <p>Yönetimsel ve güvenlikle ilgili çalışma zamanı olayları, bütünlük denetimleri için değişmez defter doğrulamasıyla dışa aktarılır.</p>
      <p><strong>Mevcut defter bütünlüğü durumu:</strong> BAŞARILI.</p>
    </section>

    <section class="doc-section success">
      <h2>6. Sürekli İzleme &amp; Tazelik</h2>
      <p>Kanıt dışa aktarmaları sürekli çalışır ve deterministik bir tazelik politikasına göre doğrulanır.</p>
      <p><strong>Mevcut tazelik sonucu:</strong> eşlenen tüm artefaktlar 35 günlük denetim penceresindedir.</p>
    </section>

    <section class="doc-section">
      <h2>7. Mevcut Durum</h2>
      <p><strong>Durum:</strong> Sürekli kontrol sertleştirme ve deterministik kanıt güncellemeleriyle SOC 2 hazırlığı devam etmektedir.</p>
      <p>PayCal bu sayfada SOC 2 sertifikası veya denetçi görüşü iddia etmemektedir. Resmi rapora erişim NDA korumasında kalmaktadır.</p>
    </section>

    <section class="doc-section">
      <h2>Yeniden Kullanılabilir Uyumluluk Parçacıkları</h2>
      <p><strong>Footer rozeti:</strong> Hazırlık devam ediyor • Kontroller Eşlenmiş • Sürekli Kanıt İzleme</p>
      <p><strong>Özet bloğu:</strong> CC1-CC9 eşlendi, 37 artefakt, 26 kontrol bağlantısı, defter bütünlüğü geçti ve otomatik tam suite test kanıtı.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Referanslar</h2>
      <ul class="doc-fact-list">
        <li>
          <a class="doc-read-more" href="/security/">Security Trust Hub</a>
          <span class="doc-ref-desc">Temizlenmiş kamuya açık kontrol özeti, deterministik anlatılar ve güvenlik iletişim yolu.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/">PayCal SOC 2 Özeti</a>
          <span class="doc-ref-desc">Bu rapor için durum, metrikler ve NDA erişimi.</span>
        </li>
        <li>
          <a class="doc-read-more" href="/soc2/request/">SOC 2 Raporu Talep Et (NDA)</a>
          <span class="doc-ref-desc">Tedarikçi ve güvenlik durum tespiti incelemeleri için kontrollü erişim.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.aicpa-cima.com/topic/audit-assurance/audit-and-assurance-greater-than-soc-2" target="_blank" rel="noopener noreferrer">AICPA SOC 2 — Resmi Standart</a>
          <span class="doc-ref-desc">SOC 2 kriterlerini tanımlayan yetkili çerçeve.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://en.wikipedia.org/wiki/System_and_Organization_Controls" target="_blank" rel="noopener noreferrer">SOC 2 — Wikipedia</a>
          <span class="doc-ref-desc">Sistem ve Organizasyon Kontrollerinin tarihine ve kapsamına genel bakış.</span>
        </li>
        <li>
          <a class="doc-read-more" href="https://www.reddit.com/r/soc2/" target="_blank" rel="noopener noreferrer">r/soc2 — Reddit Topluluğu</a>
          <span class="doc-ref-desc">SOC 2 denetimleri ve hazırlık üzerine uygulayıcı tartışması.</span>
        </li>
      </ul>
    </section>
  </section>
</article>
<?php
require_once HTML . '/footer.php';
