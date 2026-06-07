<?php
/**
 * Public Transparency: Extensions Paradigm
 *
 * PURPOSE:
 * Explain how PayCal separates core logic from extension layers, how third
 * parties can build custom extensions from this repository, and how
 * canonical paycal.app differentiates through private extension packages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Uzanti Paradigmasi - [PayCal]';
$pageLabel = 'Uzanti Paradigmasi';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Uzanti Paradigmasi</span>
  </nav>

  <header class="doc-article-header">
    <h1>Uzanti Paradigmasi</h1>
    <p class="deck">
      PayCal, uzantı katmanları farklı dağıtımlar ve ürün stratejileri için özellikleri
      uyarlayabilirken temel iş mantığının kararlı kalması için tasarlanmıştır.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-03">2026-04-03</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Cekirdek-Oncelikli Mimari</h2>
      <p>
        <strong>PayCal Core</strong>, kanonik alan ve denetleyici mantığını içerir:
        hesaplamalar, doğrulama, izinler, yaşam döngüsü politikası ve paylaşılan API sözleşmeleri.
      </p>
      <p>
        Core, tasarım gereği uzantıdan bağımsız kalır. Entegrasyon noktaları, köprü sözleşmeleri
        aracılığıyla izole edilir; böylece Core hizmetleri çalışma zamanına özgü paketlerden
        bağımsız olarak test edilebilir.
      </p>
    </section>

    <section class="doc-section">
      <h2>Bu Depoya Dahil Temel Uzantilar</h2>
      <p>
        Bu depo, uzantı noktaları için varsayılan davranış sağlayan <strong>temel uzantı
        uygulamaları</strong> ile birlikte gelir. Bunlar, genel referans paketleri ve
        kendi kendine barındırılan dağıtımlar için güvenli varsayılanlar olarak işlev görür.
      </p>
      <ul class="doc-list">
        <li><strong>billing-provider:</strong> temel faturalama kapasitesi kancaları ve mod seçimi</li>
        <li><strong>earnings-ytd:</strong> temel YTD oluşturma ve kazanç kanca noktaları</li>
        <li><strong>organization-signals:</strong> temel organizasyon sinyal kancaları</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Ucuncu Taraf Uzanti Modeli</h2>
      <p>
        Bu depoyu kullanan üçüncü taraflar kendi uzantı paketlerini oluşturabilir ve
        bakımını yapabilir. Önerilen model şöyledir:
      </p>
      <ol class="doc-list">
        <li>Mümkün olduğunda Core mantığını değiştirmeden koruyun</li>
        <li>Özel davranışı uzantı paketlerinde uygulayın</li>
        <li>Özel paketleri belgelenmiş uzantı önyüklemesi ve kanca noktaları aracılığıyla bağlayın</li>
        <li>Core sözleşmelerini koruyun, böylece yukarı akış yükseltmeleri yönetilebilir kalır</li>
      </ol>
      <p>
        Bu, merkezi alan kodunun uzun vadeli çatallanmalarına zorlamadan rekabetçi ve
        dikeye özgü dağıtımlara olanak tanır.
      </p>
    </section>

    <section class="doc-section">
      <h2>Kanonik paycal.app Platform Farklilastirmasi</h2>
      <p>
        Kanonik <code>https://paycal.app</code> platformu, aynı Core ve temel uzantı
        paradigması üzerinde <strong>özel uzantı varyantları</strong> çalıştırır.
      </p>
      <p>
        Bu özel varyantlar, PayCal tarafından işletilen ortamlar için kasıtlı bir ürün
        farklılaştırma katmanıdır. Aynı temel mimariyle uyumluluğu korurken iş akışlarını,
        kapasite davranışını ve kullanıcı arayüzüne özgü entegrasyonları ayarlayabilirler.
      </p>
      <ul class="doc-list">
        <li>Core mantığı paylaşımlı ve denetlenebilir olarak kalır</li>
        <li>Genel/temel uzantılar depoda kullanılabilir olmaya devam eder</li>
        <li>Özel uzantılar kanonik platform farklılaştırması sağlar</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Seffaflik Taahhutleri</h2>
      <ul class="doc-list">
        <li>Core sözleşmeleri uzantı noktalarında belgelenmiş ve test edilmiştir</li>
        <li>Köprü sınırları, bağlamayı keşfedilebilir kılmak için açıktır</li>
        <li>Uzantı davranışı, Core hizmetlerini istikrarsızlaştırmadan gelişebilir</li>
        <li>Kendi kendine barındıran kullanıcılar alternatif uzantı stratejileri oluşturmakta özgürdür</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
