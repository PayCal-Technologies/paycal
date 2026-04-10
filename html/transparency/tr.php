<?php
/**
 * Public Transparency Hub — Türkçe
 *
 * PURPOSE: High-level philosophy and links to detailed transparency pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/_link.php';

if (!function_exists('transparency_href')) {
  function transparency_href(string $path): string
  {
    return $path;
  }
}

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_HOME',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$readMoreLabel = 'Devamını oku';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Şeffaflık Merkezi - [PayCal]';
$pageLabel = 'Şeffaflık Merkezi';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="/"><?php echo $i18n['HELP_TOC_HOME']; ?></a>
      <span class="separator">/</span>
      <span class="current">Şeffaflık Merkezi</span>
    </nav>

    <header class="doc-article-header">
      <h1>Şeffaflık Merkezi</h1>
      <p class="deck">PayCal'ın nasıl çalıştığını yayımlıyoruz; böylece kullanıcılar yalnızca açıklamalara güvenmek yerine kararları doğrulayabilir.</p>
    </header>

    <section class="doc-article-body">
      <section class="doc-section highlight">
        <h2>Platform Felsefesi</h2>
        <p>PayCal, denetlenebilir operasyonlar üzerine inşa edilmiştir: formüller belgelenmiş, telemetri limitleri açık ve veri saklama varsayılan olarak sınırlıdır.</p>
        <p>Temel ilkemiz şudur: bir sistem bordroyu veya gizliliği etkiliyorsa, kullanıcılar bunun nasıl çalıştığını ve nasıl yönetildiğini anlayabilmelidir.</p>
        <p>Abonelik faturalandırması Stripe üzerinden işlenmektedir. Stripe desteğine <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a> adresinden ulaşabilirsiniz.</p>
        <p>Ürünü şekillendiren son güncellemeler — faturalandırma ve profil yönetişim akışları dahil — framework/backend ve test yönetişimi sayfalarımızda takip edilmektedir.</p>
      </section>

      <div class="doc-panel-grid" aria-label="Şeffaflık detay panelleri">
        <section class="doc-section">
          <h2>Güvenlik Denetimi Durumu</h2>
          <p class="doc-article-meta">Published: <time datetime="2026-03-23">2026-03-23</time></p>
          <p>Bu sayfa mevcut denetim durumunu, kapalı kapsamı, kanıt referanslarını ve güvenlik duruşunu koruyan sürüm engelleme taahhütlerini yayımlar.</p>
          <ul class="doc-fact-list">
            <li>Mevcut döngünün durumu, doğrulama tarihi ve inceleme sıklığıyla birlikte yayımlanır.</li>
            <li>Kapsam; runtime yaşam döngüsü kontrolleri, telemetri izolasyonu, korelasyon yönetişimi ve ayrıcalıklı rol güçlendirmeyi kapsar.</li>
            <li>Doğrulama anlık görüntüsü Playwright, JS, PHPStan seviye 9 ve backend test sonuçlarını içerir.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/security-audit/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Platform Metrikleri ve Gizlilik</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>Metrikler sayfası, güvenilirlik ve kapasite planlaması için toplanan operasyonel telemetriyi açıklar.</p>
          <ul class="doc-fact-list">
            <li>Telemetri anahtarları ve örnekler, iddiaların doğrulanabilir olması için yayımlanır.</li>
            <li>Toplama kapsamı yalnızca toplu verilerden oluşur; anahtarlarda kişisel tanımlayıcı bulunmaz ve katı limitler uygulanır.</li>
            <li>Saklama, tanımlı bir yaşam döngüsünü izler: ham veriler, toplu veriler ve otomatik temizleme.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/metrics/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Erişilebilirlik ve WCAG Uyumu</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Çalışma erişilebilirlik standardımız olarak WCAG 2.1 Seviye AA'yı kullanıyor ve son erişilebilirlik çalışmalarını sade bir dille yayımlıyoruz.</p>
          <ul class="doc-fact-list">
            <li>Ana gezinme; klavye kullanımını, atlama bağlantılarını ve birincil hedefler için belgelenmiş tek tuş kısayollarını destekler.</li>
            <li>Kısayol işleme güvenlik altındadır ve düzenlenebilir alanlara yazılırken veya diyaloglar açıkken tetiklenmez.</li>
            <li>Son regresyon kapsamı başlıkları, yeniden akış/metin aralığını, gezinme yollarını ve erişilebilirlik geri bildirim aktarımını doğrular.</li>
            <li>Temel genel sayfalardaki katı rota düzeyi kontrast engelleyiciler giderildi; tema genelinde kontrast çalışmaları devam etmektedir.</li>
            <li>Kullanıcılar erişilebilirlik sayfasından erişilebilirlik raporu başlatabilir ve güvenli iletişim akışı aracılığıyla devam ettirebilir.</li>
            <li>Erişilebilirlik şeffaflık sayfası artık son doğrulama tarihini, doğrulama kapsamını, bilinen sınırlamaları ve bir sonraki inceleme tarihini yayımlamaktadır.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/accessibility/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Doğrulama ve Yönetişim</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-20">2026-03-20</time></p>
          <p>Bu sayfa, PayCal'ın testler, hook'lar, runtime limitleri ve güvenlik kontrolleri aracılığıyla politikaları nasıl uyguladığını belgeler.</p>
          <ul class="doc-fact-list">
            <li>Pre-commit ve pre-push hook'ları PHPStan Seviye 9'u zorlar ve temel atlamalarını reddeder.</li>
            <li>CI; birim, entegrasyon, sözleşme, rastgele sıra ve kapsam işleri genelinde aşamalı doğrulama çalıştırır.</li>
            <li>Runtime kontrolleri, hassas akışlar için hız limitleri, TTL pencereleri ve kötüye kullanım yanıt blokları uygular.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/verification-governance/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Ağ Yetenekleri</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Bu makale, tarayıcı ve ağ davranışını güvence altına almak için kullanılan aktarım protokollerini ve yanıt başlığı kontrollerini yayımlar.</p>
          <ul class="doc-fact-list">
            <li>HTTPS zorunluluğunu, HSTS ön yüklemesini ve HTTP/3 (QUIC) duyurusunu belgeler.</li>
            <li>CSP, COOP, COEP, CORP ve tarayıcı güçlendirme başlıkları dahil mevcut güvenlik başlığı temelini listeler.</li>
            <li>Modern istemcilerde protokol müzakeresi ve geri dönüş davranışını açıklar.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/network-capabilities/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Test Yönetişimi</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Bu makale, backend, frontend ve erişilebilirlik doğrulamasını nasıl çalıştırdığımızı ve hangi kapıların sürüm engelleyici olarak kabul edildiğini belgeler.</p>
          <ul class="doc-fact-list">
            <li>Aktif PHPUnit suite envanterini ve kategori dağılımını gösterir.</li>
            <li><code>/mis</code> taramalarında kullanılan sürüm engelleyen doğrulama komutlarını belgeler.</li>
            <li>Test kanıtının changelog'lara ve gerçek kaynak notlarına nasıl eşitlendiğini açıklar.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Bağımlılık ve CI/CD Yönetişimi</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-31">2026-03-31</time></p>
          <p>Bu makale, npm bağımlılıklarının nasıl kontrol edildiğini ve CI kapılarının sürümden önce nasıl uygulandığını yayımlar.</p>
          <ul class="doc-fact-list">
            <li>Lockfile-first npm politikasını ve <code>npm ci</code> otomasyon gereksinimlerini belgeler.</li>
            <li>JavaScript kalite kapılarını ve backend pipeline aşamalarını workflow kontrollerine eşler.</li>
            <li>Bilinen belgeleme sınırlamalarını ve planlanan yönetişim iyileştirmelerini listeler.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Framework ve Backend Değişiklik Günlüğü</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Bu sayfa, backend mimarisini ve framework düzeyindeki değişiklikleri neyin değiştiğini ve neden değiştiğini açıklayan halka açık açıklamalarla takip eder.</p>
          <ul class="doc-fact-list">
            <li>Davranışı önemli ölçüde etkileyen servis/denetleyici değişikliklerini özetler.</li>
            <li>Sürüm değişikliklerini güvenlik ve yönetişim kontrollerine eşler.</li>
            <li>Ayrıntılı changelog ve denetim artifaktlarına referanslar içerir.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Ürün Deneyimi ve Faturalandırma Değişiklikleri</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-24">2026-03-24</time></p>
          <p>Önemli hesap, faturalandırma ve profil akışı güncellemeleri, kullanıcıların hem UX hem davranış değişikliklerini denetleyebilmesi için backend ve test yönetişimiyle birlikte açıklanır.</p>
          <ul class="doc-fact-list">
            <li>Faturalandırma durumu işlemesini ve abonelik durumu sözleşme değişikliklerini takip eder.</li>
            <li>Hesap silme onay cümleleri gibi yıkıcı eylem güvencelerini yakalar.</li>
            <li>Ürüne yönelik güncellemeleri doğrulama ve sürüm yönetişimi kanıtıyla ilişkilendirir.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/framework-backend/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Vergi Metodolojisi</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
          <p>Vergi sayfası, tahminler için kullanılan CRA uyumlu formülleri, eşikleri ve örnekleri belgeler.</p>
          <ul class="doc-fact-list">
            <li>CPP, OAS, EI, federal/eyalet vergisi ve net maaş formülleri çözümlü örneklerle belgelenmiştir.</li>
            <li>Mevcut vergi yılı eşikleri ve oranları yayımlanmış ve CRA referanslarına bağlanmıştır.</li>
            <li>Hesaplama kalitesi otomatik test paketiyle ve yıllık oran güncellemeleriyle doğrulanmaktadır.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/taxes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>E-posta Mimarisi</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-21">2026-03-21</time></p>
          <p>E-posta sayfası, PayCal'ın hangi işlemsel e-postaları gönderdiğini, şablonların nasıl oluşturulduğunu ve teslimat güvenilirliğinin nasıl doğrulandığını açıklar.</p>
          <ul class="doc-fact-list">
            <li>Akışa özgü şablon aileleri; doğrulama, kurtarma, e-posta değişikliği ve iletişim destek yolları için belgelenmiştir.</li>
            <li>Teslimat sorumlulukları, EmailGarum orkestrasyonu ve EmailTransport SMTP protokol işleme arasında ayrılmıştır.</li>
            <li>Şablon taramaları ve DKIM/DMARC sağlık doğrulaması için opt-in canlı testler belgelenmiştir.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/email/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Kazanç Yük Testleri</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-03-29">2026-03-29</time></p>
          <p>Bu makale, <code>/earnings/</code> üzerinde eager rendering ile lazy section loading için yeniden üretilebilir A/B benchmark sonuçlarını yayımlar.</p>
          <ul class="doc-fact-list">
            <li>2025/2026 gerçek ve sentetik veri kümeleri için 10 çalıştırma matrisi içerir.</li>
            <li>DOMContentLoaded, bölüm hazır zamanlamasını ve API çağrısı ödünleşimlerini raporlar.</li>
            <li>Test yöntemi ve yorumu halka açık inceleme için belgelenmiştir.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/load-testing/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>

        <section class="doc-section">
          <h2>Süper Kahraman Haritası</h2>

          <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time></p>
          <p>Süper Kahramanlar sayfası, PayCal'ın tematik çapraz kesim bileşenlerini ve her birinin çözdüğü belirli operasyonel sorunu belgeler.</p>
          <ul class="doc-fact-list">
            <li>ShadowTalon, Guardian, Phantom Wing, Lens ve EmailGarum'u içerir.</li>
            <li>Her bileşenin nerede kullanıldığını ve hangi risk sınırını koruduğunu açıklar.</li>
            <li>Uygulama iddialarının doğrudan kod ve testlerde incelenebilmesi için doğrulama çapası sağlar.</li>
          </ul>
          <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>"><?php echo $readMoreLabel; ?></a></p>
        </section>
      </div>
    </section>
  </article>
<?php
require_once HTML.'/footer.php';
