<?php
/**
 * Public Transparency: Business Connections and Role Philosophy
 *
 * PURPOSE:
 * Explain how PayCal separates business connections, active membership,
 * role changes, consent, and explicit access grants.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE',
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">İş Bağlantıları ve Rol Felsefesi</span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo htmlspecialchars($i18n['TRANSPARENCY_BUSINESS_MEMBERSHIP_PAGE_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="deck">
      Bu sayfa, gevşek bağlı ekip semantiğinden açık Connections modeline geçişi açıklar.
      Bir connection kimin kime bağlı olduğunu söyler. Üyelik, rol, onay ve korumalı veri
      erişimi ayrı politika kararları olarak kalır.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time> &middot; Last updated: <time datetime="2026-06-19">2026-06-19</time> &middot; <a href="<?php echo transparency_href('/transparency/business-membership-2026-06-19-pre-connections/'); ?>">Previous version</a></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Bu Model Neden Var</h2>
      <p>
        Bordro işbirliğinin gerçek bir güvenlik etkisi vardır. Okunması, test edilmesi ve denetlenmesi
        kolay bir rol modeli, dağınık tek seferlik kontrollerden oluşturulmuş bir modelden daha güvenlidir.
      </p>
      <p>
        Business <strong>&lt;-&gt;</strong> Üye connection'ı her aktöre bir işletmeyle açık identity link
        verir. Aktif üyelik, rol yetkisi, korumalı veri onayı ve gelecekteki kişi-kişi grants
        bu linkten ayrı kalır.
      </p>
    </section>

    <section class="doc-section">
      <h2>Business <strong>&lt;-&gt;</strong> Üye Connection Değişiklikleri</h2>
      <ul class="doc-list">
        <li>Connections, UI durumundan çıkarılmak yerine açıkça temsil edilir.</li>
        <li>Erişim isteği, davet, onay, etkinleştirme ve iptal yaşam döngüsü durumları backend politikası tarafından uygulanır.</li>
        <li>Business panelleri ve bildirimler artık connection geçişlerini ve rol sonuçlarını daha tutarlı biçimde yansıtır.</li>
        <li>Paylaşılan Business davranışı, ayrıcalıklı işlemler işlenmeden önce aktif üyelik ve rol politikası tarafından yönetilir.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Connection, üyelik, onay ve grants</h2>
      <p>
        PayCal artık bu kavramları ayrı ele alır:
      </p>
      <ul class="doc-list">
        <li><strong>Connection:</strong> bir kişi ile işletme arasında veya iki kişi arasında identity link.</li>
        <li><strong>Üyelik:</strong> workspace işbirliği için kullanılan aktif Business katılım durumu.</li>
        <li><strong>Onay:</strong> üyenin korumalı iş verilerini paylaşma izni.</li>
        <li><strong>Grant:</strong> delege takvim görünümü veya gelecekteki trusted recovery gibi açık izin.</li>
      </ul>
      <p>
        Connection tek başına korumalı raporlar, exportlar, payroll görünürlüğü,
        recovery yetkisi veya başka biri adına hareket etme yeteneği vermez.
      </p>
    </section>

    <section class="doc-section">
      <h2>Rol Değişiklikleri ve Mevcut Rol Felsefesi</h2>
      <p>
        Roller, işlem başına kapsam kısıtlamaları uygulanan kapasite odaklıdır. Mevcut temel:
      </p>
      <ul class="doc-list">
        <li><strong>sahip:</strong> sahiplik devri ve yüksek güven yönetimi eylemleri dahil olmak üzere egemen kontrol.</li>
        <li><strong>yönetici:</strong> sahiplik devir yetkisi olmaksızın günlük operasyonel kontrol.</li>
        <li><strong>katkıcı:</strong> atanan kapsamla sınırlı yazma yetkisine sahip güvenilir operatör.</li>
        <li><strong>üye:</strong> kısıtlı mutasyon haklarıyla sınırlı self-servis katılımı.</li>
        <li><strong>izleyici:</strong> yazma izinleri olmaksızın salt okunur görünürlük.</li>
      </ul>
      <p>
        Aşırı yüklenmiş rol bayraklarına karşı açık kapasite ve kapsam bileşimini tercih ederiz. Bu, rol sonuçlarını test etmeyi ve anlamayı kolaylaştırır.
      </p>
    </section>

    <section class="doc-section">
      <h2>Güvenlik ve Şifreleme Felsefesi</h2>
      <p>
        Business işbirliği, şifreleme ve onay kontrolleriyle kesişir. Aktif üyelik, rol kontrolleri
        ve onay durumu, hassas işlemlerin politikaya bağlı kalması için paylaşılan Business envelope davranışını yönetir.
      </p>
      <ul class="doc-list">
        <li>Üyelik ve onay durumu, paylaşılan güvenli işlemler devam etmeden önce doğrulanır.</li>
        <li>Rol değişiklikleri ve üyelik geçişleri yalnızca UX olayları değil, güvenlikle ilgili olaylar olarak değerlendirilir.</li>
        <li>Erişim reddi yolları, politika uyumsuzluğu altında beklenen davranıştır ve denetlenebilirlik için açığa çıkarılır.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>İleriye Yönelik Operasyonel Felsefe</h2>
      <ul class="doc-list">
        <li><strong>Tek politika kaynağı:</strong> rol ve kapsam kararları paylaşılan backend politika haritalarından kaynaklanmalıdır.</li>
        <li><strong>UI projeksiyon olarak:</strong> arayüzler yetkilendirme mantığını çoğaltmak yerine politika sonuçlarını görüntülemelidir.</li>
        <li><strong>İzlenebilir geçişler:</strong> onaylar, rol değişiklikleri ve iptal işlemleri gözlemlenebilir ve incelenebilir kalmalıdır.</li>
        <li><strong>Sürüm şeffaflığı:</strong> üyelik ve rollerdeki davranış değişiklikleri changelog'larda ve şeffaflık sayfalarında belgelenir.</li>
      </ul>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
