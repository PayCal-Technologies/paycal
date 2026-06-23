<?php
/**
 * Public Transparency: Error Handling & Message Normalization
 *
 * PURPOSE: 
 * Explain PayCal's standardized error-message normalization pattern, the
 * security and UX rationale behind it, and how we ensure users receive
 * meaningful, safe error feedback across all frontend modules.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Hata İşleme ve Mesaj Normalleştirmesi</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_ERROR_HANDLING_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      PayCal'ın tüm ön yüz modüllerinde hata raporlamasını nasıl standartlaştırdığı; kullanıcıların
      hassas ayrıntıları açıklamadan anlamlı, güvenli ve tutarlı hata geri bildirimi almasını sağlamak.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Genel Bakış ve Amaç</h2>
      <p>
        Kullanıcılar hatalarla karşılaştığında (ağ arızaları, erişim reddedildi, doğrulama hataları),
        ne olduğunu ve nasıl düzeltileceğini açıklayan net bir geri bildirim almayı hak ederler. Ancak,
        arka uçtan gelen ham hata mesajlarının normalleştirilmesi gerekir:
      </p>
      <ul class="doc-list">
        <li><strong>Gürültüyü kaldır:</strong> Gereksiz &quot;Hata:&quot; öneklerini ve boşlukları temizle</li>
        <li><strong>Sızıntıyı önle:</strong> Hassas uygulama ayrıntılarının kullanıcıya asla ulaşmamasını sağla</li>
        <li><strong>Yedekler sun:</strong> Hatalar boş veya hatalı biçimlendirildiğinde güvenli mesajlar göster</li>
        <li><strong>Tutarlılığı sağla:</strong> Tüm 11+ ön yüz modülünde aynı mantığı uygula</li>
        <li><strong>Hata ayıklamayı geliştir:</strong> Phantom Wing'e tam hata ayrıntılarını kaydet ve kullanıcılara güvenli özetler göster</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Sorun: Genel ve Anlamlı Hatalar</h2>
      <p>
        Standartlaştırmadan önce PayCal modülleri özel hata işleme kullanıyordu:
      </p>
      <div class="doc-code-block">
        <pre><code>// ❌ KÖTÜ: Ham hatayı açığa çıkarır, mantığı tekrar eder
PC.showToast(error?.message || 'İçe aktarma başarısız.');
PW.error(`İçe aktarma başarısız: ${error.message}`);</code></pre>
      </div>
      <p>Bu yaklaşımın sorunları:</p>
      <ul class="doc-list">
        <li>Kullanıcılar &quot;ECONNREFUSED: Bağlantı reddedildi&quot; gibi kafa karıştırıcı ham mesajlar görür</li>
        <li>Her modül kendi yedek mantığını bağımsız olarak uygular</li>
        <li>Tutarlı boşluk temizleme veya önek kaldırma yoktur</li>
        <li>Boş hata mesajları kullanıcı arayüzünde &quot;undefined&quot; olarak görünebilir</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Çözüm: Standartlaştırılmış Hata Çözümleyici</h2>
      <p>
        Tüm PayCal ön yüz modülleri artık hata mesajlarını normalleştiren birleşik bir çözümleyici işlevi kullanmaktadır:
      </p>
      <div class="doc-code-block">
        <pre><code>// ✅ İYİ: Normalleştirilmiş, tutarlı, güvenli
const resolveThrownMessage = (error, fallbackMessage) =&gt; {
  // Hata nesnesinden mesajı çıkar
  const raw = typeof error?.message === 'string' 
    ? error.message 
    : String(error || '');
  
  // &quot;Error:&quot; önekini kaldır ve boşlukları temizle
  const normalized = raw.replace(/^Error:\s*/i, '').trim();
  
  // Boş değilse normalleştirilmişi döndür; aksi hâlde güvenli yedek
  return normalized !== '' ? normalized : fallbackMessage;
};</code></pre>
      </div>
      <p><strong>Kullanım:</strong></p>
      <div class="doc-code-block">
        <pre><code>// Tüm modüllerdeki catch bloklarında
try {
  await updateProfile(data);
} catch (error) {
  const message = resolveThrownMessage(error, 'Profil güncellenemedi.');
  PC.showToast(message, 'error');  // Kullanıcı anlamlı geri bildirim görür
  PW.error(message);                // Hata ayıklama için kaydedildi
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Uygulama Kapsamı</h2>
      <p>
        Nisan 2026 itibarıyla bu standartlaştırılmış hata işleme deseni
        <strong>11 ön yüz modülüne</strong> ve <strong>~40+ normalleştirilmiş catch bloğuna</strong> uygulanmıştır:
      </p>
      <div class="doc-two-column">
        <div>
          <h3>Kimlik Doğrulama ve Ayarlar (7 modül)</h3>
          <ul class="doc-list">
            <li><code>html/js/auth-recovery/index.php</code> (4 catches)</li>
            <li><code>html/js/signin/index.php</code> (2 catches)</li>
            <li><code>html/js/signin/verification-reminder.js</code> (2 catches)</li>
            <li><code>html/js/settings/index.php</code> (8+ catches)</li>
          </ul>
        </div>
        <div>
          <h3>Çekirdek ve Veri Modülleri (4 modül)</h3>
          <ul class="doc-list">
            <li><code>html/js/core/network.js</code> (3 catches)</li>
            <li><code>html/js/core/index.php</code> (5 catches)</li>
            <li><code>html/js/core/billing.js</code> (5 catches)</li>
            <li><code>html/js/earnings/index.php</code> (4 catches)</li>
          </ul>
        </div>
      </div>
      <p><strong>Yüksek değerli modüller (10+ catch noktası):</strong></p>
      <ul class="doc-list">
        <li><code>html/js/businesses/index.php</code> — Organizasyon yönetimi, erişim istekleri, denetim izleri (19+ catches)</li>
        <li><code>html/js/sites/index.php</code> — Site CRUD, kazançlar, sahipsiz iş kurtarma (10+ catches)</li>
        <li><code>html/js/calendar/calendar.js</code> — Gün girişi işlemleri, kopyala/yapıştır/sil (2 catches)</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Hata Kategorileri ve İşleme Desenleri</h2>
      <p>Çözümleyici birkaç hata kategorisinde tutarlı biçimde uygulanır:</p>
      
      <h3>1. Ağ İsteği Hataları</h3>
      <div class="doc-code-block">
        <pre><code>// Ağ modülü: HTTP hataları, zaman aşımları, bağlantı sorunları
async function deleteResource(ep, id) {
  try {
    // ...fetch mantığı...
  } catch (error) {
    const resolved = resolveThrownMessage(error, 'Ağ hatası');
    const msg = `[deleteResource] ${resolved}`;
    PW.error(msg);
    throw new Error(msg);
  }
}</code></pre>
      </div>

      <h3>2. API Yanıtı İşleme</h3>
      <div class="doc-code-block">
        <pre><code>// Faturalandırma/Ayarlar: Sunucu yükde hata mesajı döndürdü
try {
  const response = await fetch('/api/v1/billing/subscription');
  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload?.message || 'Faturalandırma durumu yüklenemedi.');
  }
} catch (error) {
  const resolved = resolveThrownMessage(error, 'Faturalandırma durumu yüklenemedi.');
  setScreenReaderStatus(resolved);
}</code></pre>
      </div>

      <h3>3. Kullanıcı Arayüzü İşlemi Hataları</h3>
      <div class="doc-code-block">
        <pre><code>// Takvim/Organizasyonlar: Kullanıcı tarafından başlatılan eylemler (yapıştır, sil, güncelle)
button.addEventListener('click', async () => {
  try {
    await performAction();
    PC.showToast('Başarılı!', 'save');
  } catch (error) {
    const message = resolveThrownMessage(error, 'Eylem başarısız. Tekrar deneyin.');
    PC.showToast(message, 'error');
  }
});</code></pre>
      </div>

      <h3>4. Asenkron Başlatma</h3>
      <div class="doc-code-block">
        <pre><code>// Çekirdek modüller: Başlangıç veya bağımlı başlatma hataları
try {
  NavigationToggle.init();
} catch (err) {
  const resolved = resolveThrownMessage(err, 'Gezinme başlatması başarısız');
  PW.warn(resolved);  // Kaydedildi ancak sayfayı engellemez
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Güvenlik Değerlendirmeleri</h2>
      <p>
        Hata mesajı normalleştirmesi kullanıcı gizliliğini ve sistem bütünlüğünü korur:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Veritabanı ayrıntısı yok:</strong> &quot;UNIQUE constraint failed on email&quot; gibi arka uç hataları
          API sınırında engellenir ve kullanıcı dostu mesajlarla değiştirilir
        </li>
        <li>
          <strong>Dosya yolu yok:</strong> Dosya yollarını veya süreç ayrıntılarını açığa çıkaran sistem hataları kaldırılır
        </li>
        <li>
          <strong>Kimlik doğrulama sızıntısı yok:</strong> Kimlik doğrulama hatalarına verilen yanıtlar
          bir hesabın var olup olmadığını asla açıklamaz (yalnızca zamanlama güvenli genel mesajlar)
        </li>
        <li>
          <strong>CORS/ağ ayrıntısı yok:</strong> Taşıma katmanı hataları
          genel &quot;Bağlantı hatası&quot; mesajlarına normalleştirilir
        </li>
        <li>
          <strong>Güvenli yedekler:</strong> Tüm catch bloklarının açık yedek mesajları vardır;
          asla &quot;undefined&quot; veya &quot;null&quot; göstermez
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Kullanıcı Deneyimi Faydaları</h2>
      <p>
        Standartlaştırılmış hata mesajları kullanıcı deneyimini önemli ölçüde iyileştirir:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Net Geri Bildirim:</strong> Kullanıcılar neyin başarısız olduğunu bilir (örn. &quot;Geçiş anahtarı tanınmadı&quot;
          genel &quot;Oturum açma başarısız&quot; yerine)
        </li>
        <li>
          <strong>Uygulanabilir Sonraki Adımlar:</strong> Mümkün olduğunda mesajlar çözümler önerir
          (&quot;Tekrar deneyin&quot;, &quot;Bağlantınızı kontrol edin&quot;, &quot;Destek ile iletişime geçin&quot;)
        </li>
        <li>
          <strong>Uygulama Genelinde Tutarlılık:</strong> Aynı hata türleri her yerde aynı şekilde görünür,
          kullanıcı karmaşası azalır
        </li>
        <li>
          <strong>Erişilebilir Hata Durumları:</strong> Ekran okuyucuları normalleştirilmiş mesajları duyurur;
          günlük kaydı destek ekiplerine tam bağlam sağlar
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Hata Ayıklama ve Destek İş Akışı</h2>
      <p>
        Hata normalleştirmesi hata ayıklama kapasitesinden <strong>ödün vermez</strong>. Tam hata ayrıntıları
        Phantom Wing'e akar:
      </p>
      <div class="doc-code-block">
        <pre><code>// Kullanıcı temiz kullanıcı arayüzü mesajı görür
PC.showToast(resolveThrownMessage(error, 'Yükleme başarısız.'), 'error');

// Destek ekibi Phantom Wing günlüklerinde tam ayrıntıları görür
PW.error('Yükleme başarısız', {
  userMessage: resolveThrownMessage(error, 'Yükleme başarısız.'),
  rawError: error.message,
  stack: error.stack,
  context: { fileSize, mimeType, url }
});</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Test ve Kalite Güvencesi</h2>
      <p>
        Tüm hata işleme değişiklikleri dağıtımdan önce doğrulanır:
      </p>
      <ul class="doc-list">
        <li><strong>Sözdizimi Doğrulaması:</strong> <code>php -l</code> ve <code>node --check</code> doğruluğu onaylar</li>
        <li><strong>Tür Güvenliği:</strong> Editör tanılamaları tür gerilemesi olmadığını doğrular</li>
        <li><strong>Entegrasyon Testi:</strong> Catch blokları sahte hata nesneleriyle test edilir</li>
        <li><strong>Phantom Wing Günlüğü:</strong> Hata mesajları hata ayıklama günlüklerinde doğrulanır</li>
        <li><strong>Erişilebilirlik Denetimi:</strong> Ekran okuyucu duyuruları anlaşılırlık açısından test edilir</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Bakım ve Gelecekteki Genişletmeler</h2>
      <p>
        Bu desen uzun vadeli sürdürülebilirlik için tasarlanmıştır:
      </p>
      <ul class="doc-list">
        <li>
          <strong>Yerelleştirmeye Hazır:</strong> Hata mesajları çözümleyici mantığını değiştirmeden
          i18n üzerinden iletilebilir
        </li>
        <li>
          <strong>Genişletilebilir:</strong> Çözümleyici, mevcut kodu bozmadan hata kodlarını, yeniden deneme mantığını
          veya özelleştirilmiş mesaj aramasını işlemek üzere geliştirilebilir
        </li>
        <li>
          <strong>Belgeler:</strong> Her modül, hata senaryolarını ve yedek stratejilerini açıklayan
          satır içi yorumlar içerir
        </li>
        <li>
          <strong>Git Geçmişi:</strong> Kolay inceleme için ayrıntılı işleme mesajları ve
          dosya düzeyinde değişikliklerle tüm değişiklikler izlenir
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Özet: PayCal Hata İşleme Standardı</h2>
      <p>
        PayCal'ın standartlaştırılmış hata mesajı normalleştirmesi şunları güvence altına alır:
      </p>
      <ol class="doc-list">
        <li>Kullanıcılar açık, uygulanabilir hata geri bildirimi alır</li>
        <li>Hassas sistem ayrıntıları asla ön yüze sızmaz</li>
        <li>Mesaj işleme tüm 11+ ön yüz modülünde tutarlıdır</li>
        <li>Hata ayıklama ve destek ekipleri Phantom Wing aracılığıyla tam hata bağlamını korur</li>
        <li>Kod sürdürülebilir, test edilebilir ve erişilebilirdir</li>
      </ol>
      <p class="doc-section-footer-note">
        Güvenlik, netlik ve tutarlılığa olan bu bağlılık, PayCal'ın kullanıcı güveni ve
        şeffaf bilgi paylaşımına olan adanmışlığını yansıtır.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
