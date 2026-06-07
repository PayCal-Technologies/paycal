<?php
/**
 * Public Transparency: Organization Membership and Role Philosophy
 *
 * PURPOSE:
 * Explain why PayCal uses an Organization <-> Member relationship model,
 * how role changes are governed, and what architectural philosophy guides
 * capability, scope, and security decisions.
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
$pageTitle = 'Organizasyon Uyeligi ve Rol Felsefesi - [PayCal]';
$pageLabel = 'Organizasyon Uyeligi ve Rol Felsefesi';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Organizasyon Üyeliği ve Rol Felsefesi</span>
  </nav>

  <header class="doc-article-header">
    <h1>Organizasyon Üyeliği ve Rol Felsefesi</h1>
    <p class="deck">
      Bu sayfa, gevşek bağlı ekip semantiğinden açık bir Organizasyon <strong>&lt;-&gt;</strong> Üye
      ilişki modeline geçişi, mevcut rol politikasını ve izinleri denetlenebilir ve güvenli tutmak
      için kullandığımız ilkeleri açıklamaktadır.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-09">2026-04-09</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Bu Model Neden Var</h2>
      <p>
        Bordro işbirliğinin gerçek bir güvenlik etkisi vardır. Okunması, test edilmesi ve denetlenmesi
        kolay bir rol modeli, dağınık tek seferlik kontrollerden oluşturulmuş bir modelden daha güvenlidir.
      </p>
      <p>
        Organizasyon <strong>&lt;-&gt;</strong> Üye yapısı, her aktöre politika destekli durum, rol ve
        kapsam davranışıyla bir organizasyonla açık bir ilişki verir.
      </p>
    </section>

    <section class="doc-section">
      <h2>Organizasyon <strong>&lt;-&gt;</strong> Üye İlişkisindeki Değişiklikler</h2>
      <ul class="doc-list">
        <li>Üyelik, örtük bir UI durumu yerine açık bir ilişki olarak temsil edilir.</li>
        <li>Erişim isteği, davet, onay, etkinleştirme ve iptal yaşam döngüsü durumları backend politikası tarafından uygulanır.</li>
        <li>Organizasyon panelleri ve bildirimler artık ilişki geçişlerini ve rol sonuçlarını daha tutarlı biçimde yansıtmaktadır.</li>
        <li>Paylaşılan organizasyon davranışı, ayrıcalıklı işlemler işlenmeden önce üyelik durumu tarafından yönetilir.</li>
      </ul>
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
        Organizasyon işbirliği, şifreleme ve onay kontrolleriyle kesişir. Üyelik ve rol kontrolleri,
        hassas işlemlerin politikaya bağlı kalması için paylaşılan organizasyon zarfı davranışını yönetir.
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
