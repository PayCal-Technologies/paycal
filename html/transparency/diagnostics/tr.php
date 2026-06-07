<?php
/**
 * Public Transparency: Opt-in Diagnostics & Phantom Wing
 *
 * PURPOSE:
 * Explain how PayCal's optional diagnostics system works, what data it collects
 * (and what it never collects), who controls it, and how it helps troubleshoot
 * problems without compromising privacy.
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
$pageTitle = 'Opt-in Tanılama ve Phantom Wing - [PayCal]';
$pageLabel = 'Opt-in Tanılama ve Phantom Wing';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Opt-in Tanılama &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1>Opt-in Tanılama &amp; Phantom Wing</h1>
    <p class="deck">
      PayCal, sizin yönettiğiniz isteğe bağlı bir tanılama katmanı içerir. İşte tam olarak
      neyi topladığı, cihazınızda ne kaldığı ve nasıl kullanıldığı.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Genel Bakış</h2>
      <p>
        PayCal, <strong>Phantom Wing</strong> adlı yerleşik bir tanılama katmanıyla birlikte
        gelir. Varsayılan olarak neredeyse tamamen sessizdir — yalnızca ciddi, işlenmeyen
        hataları yakalar ve açık onayınız olmadan hiçbir şey göndermez.
      </p>
      <p>
        Bir sorunla karşılaşırsanız ve destekle daha fazla bağlam paylaşmak isterseniz,
        <a href="/settings/">Ayarlar → Hata Ayıklama (İsteğe Bağlı)</a> bölümünde
        ek tanılamayı etkinleştirebilirsiniz.
        Her ayar bağımsızdır; yalnızca ilgili olanı açabilirsiniz.
        Üçü de varsayılan olarak <strong>Kapalı</strong>'dır.
      </p>
    </section>

    <section class="doc-section">
      <h2>Üç opt-in denetimi</h2>
      <p>
        Her denetim, Ayarlar sayfanızın alt kısmındaki <strong>Hata Ayıklama (İsteğe Bağlı)</strong>
        panelinde bulunur. Yalnızca sorun giderme amacıyla tasarlanmıştır — bunları etkinleştirmek,
        tarayıcıda ek işlem yapıldığı için sayfa etkileşimlerini hafifçe yavaşlatabilir.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Ayar</th>
            <th>Ne etkinleştirir</th>
            <th>Kim görür</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Konsol Mesajları</strong></td>
            <td>
              Tarayıcınızın geliştirici konsoluna uyarılar, bilgi günlükleri ve performans
              işaretçileri yayar. Kendi başınıza teşhis için kullanışlıdır — DevTools'u açın
              ve <code>[PayCal]</code> ön ekiyle veya emoji işaretçileriyle başlayan mesajları
              arayın.
            </td>
            <td>Yalnızca siz (tarayıcı konsolunuz, hiçbir zaman iletilmez)</td>
          </tr>
          <tr>
            <td><strong>Ayrıntılı Tanılama</strong></td>
            <td>
              Adım adım dahili olay günlüğünü etkinleştirir. Phantom Wing, işlemlerin tam
              yaşam döngüsünü (takvim yüklemeleri, form gönderimler, oturum olayları) paylaşmayı
              seçtiğiniz herhangi bir destek raporuna dahil edilen bir bellek içi günlüğe
              yakalar.
            </td>
            <td>Yalnızca siz, bir destek raporu paylaşmadıkça</td>
          </tr>
          <tr>
            <td><strong>Ağ Öngörüleri</strong></td>
            <td>
              API istek zamanlamasını günlüğe kaydeder — her sunucu gidiş-dönüşünün ne kadar
              sürdüğünü, yanıt boyutlarını ve toplu işleme veya önbelleğe almanın uygulanıp
              uygulanmadığını. Belirli işlemlerdeki yavaşlığı teşhis etmeye yardımcı olur.
            </td>
            <td>Yalnızca siz (tarayıcı konsolunuz, hiçbir zaman iletilmez)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing varsayılan olarak ne yapar</h2>
      <p>
        Üç denetim de kapalı olsa bile, Phantom Wing yalnızca ciddi arızaları yakalayan
        hafif bir temel monitör çalıştırır:
      </p>
      <ul class="doc-list">
        <li>Yakalanmamış JavaScript istisnaları (<code>window.onerror</code>)</li>
        <li>İşlenmeyen promise redleri</li>
        <li>Ağ hatasıyla başarısız olan Fetch çağrıları (HTTP hataları değil — bunlar özellik başına işlenir)</li>
      </ul>
      <p>
        Bu temel veriler tamamen bellekte kalır ve hiçbir yere iletilmez. Sayfa yüklenirken
        tarayıcı konsolunda bir saniyelik özet olarak görüntülenir, böylece bir şeyin yanlış
        gittiğini hızla görebilirsiniz, ardından silinir.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Baseline output when all clear (console, diagnostics off):
[PHANTOM WING] All clear - no errors or warnings detected.

// Baseline output when issues exist:
[PHANTOM WING] Error Summary
Total issues: 2 across 2 grouped location(s).
WARN 1: FormSubmit timed out after 8000ms
ERROR 1: Uncaught TypeError in calendar renderer</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing &amp; Telemetri</h2>
      <p>
        Phantom Wing, özellik güvenilirliğini toplu olarak ölçmek için kullanılan hafif bir
        telemetri kanalına sahiptir — örneğin, belirli bir işlemin platform genelinde olağandışı
        bir oranda başarısız olup olmadığını tespit etmek.
      </p>
      <h3>Telemetri ne gönderir</h3>
      <ul class="doc-list">
        <li>Saatlik gruplara ayrılmış anonimleştirilmiş olay sayıları (örn. <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Hata kategorisi ve türü — hiçbir zaman tam hata mesajı veya yığın izleme değil</li>
        <li>Kullanıcı tanımlayıcısı yok, oturum belirteci yok, IP adresi yok</li>
      </ul>
      <h3>Telemetri hiçbir zaman ne göndermez</h3>
      <ul class="doc-list">
        <li>Adınız, e-postanız veya hesap bilgileriniz</li>
        <li>Kazanç, ödeme dönemi veya finansal veriler</li>
        <li>Tam hata mesajları veya yığın izlemeler</li>
        <li>URL yolları veya sorgu dizeleri</li>
        <li>Tuş vuruşları veya form alanı değerleri</li>
      </ul>
      <h3>Hız sınırlama &amp; geri çekilme</h3>
      <p>
        Telemetri gönderimleri sunucu tarafında kullanıcı başına dakika başına hız sınırlamasına
        tabidir. İstemciniz eşiği aşarsa, sunucu sessizce onaylar ve fazlalığı atar — hiçbir
        şey depolanmaz. İstemci ayrıca üstel geri çekilme uygular: iki ardışık sunucu tarafı
        hatasından sonra telemetri gönderimini on dakika boyunca otomatik olarak devre dışı
        bırakır.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Telemetry payload shape (no personal data):
{
  "type": "pw.performance.metrics",
  "fields": {
    "count": 1,
    "bucket_hour": 2026030914,
    "flush_reason": "timer"
  }
}</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Veri Düzeltme</h2>
      <p>
        Herhangi bir değer bellekte depolanmadan veya telemetri aracılığıyla iletilmeden önce,
        Phantom Wing otomatik bir düzeltme geçişi uygular. Bilinen hassas kalıplarla eşleşen
        değerler <code>[REDACTED]</code> ile değiştirilir:
      </p>
      <ul class="doc-list">
        <li>E-posta adresleri</li>
        <li>Bearer belirteçleri ve yetkilendirme başlığı değerleri</li>
        <li>CSRF belirteçleri</li>
        <li>Minimum uzunluğun üzerindeki kriptografik anahtarlar veya base64 kodlu blob'lar gibi görünen dizeler</li>
      </ul>
      <p>
        Düzeltme, kuyruğa almadan önce kesilen konsol yöntemlerine iletilen tüm bağımsız
        değişkenler ve tüm telemetri alanı değerleri üzerinde çalışır. Tanılama ayarları
        etkinleştirilerek atlatılamaz.
      </p>
    </section>

    <section class="doc-section">
      <h2>Kapsam Koruyucuları: Tanılamanın bastırıldığı sayfalar</h2>
      <p>
        Telemetri gönderimi, kimlik doğrulama sayfalarında (<code>/auth/</code>) tamamen
        bastırılır. Bu, Ağ Öngörüleri açık olsa bile, oturum açma, kaydolma veya kurtarma
        akışlarındayken hiçbir telemetri iletilmediği anlamına gelir. Bu, kimlik bilgilerine
        yakın verilerin tanılama kanallarında görünme olasılığını ortadan kaldırmak için alınan
        derinlemesine savunma önlemidir.
      </p>
    </section>

    <section class="doc-section">
      <h2>Sizin Kontrolünüz</h2>
      <p>
        Üç tanılama ayarı da tarayıcı tanımlama bilgileri olarak değil, hesap tercihleri olarak
        depolanır. Tüm cihazlar ve oturumlarda hesabınızı takip ederler ve yeni hesaplar dahil
        her hesap için varsayılan olarak <strong>Kapalı</strong>'dır.
        Bunları istediğiniz zaman <a href="/settings/">Ayarlar → Hata Ayıklama (İsteğe Bağlı)</a>
        bölümünden değiştirebilirsiniz.
      </p>
      <p>
        Bir ayarı kapatmak, bir sonraki sayfa yüklemesinde hemen geçerli olur. Oturumlar arasında
        tanılama verisi tutulmaz: Phantom Wing'in bellek içi günlüğü, başka bir sayfaya gittiğinizde
        veya sekmeyi kapattığınızda temizlenir.
      </p>
    </section>

    <section class="doc-section">
      <h2>Özet</h2>
      <ol class="doc-list">
        <li>Üç hata ayıklama denetimi de varsayılan olarak <strong>Kapalı</strong>'dır ve sizin tarafınızdan açıkça etkinleştirilmesi gerekir</li>
        <li>Konsol Mesajları ve Ağ Öngörüleri asla cihazınızdan çıkmaz</li>
        <li>Ayrıntılı Tanılama bellekte kalır ve yalnızca bir destek raporu paylaşmayı seçerseniz paylaşılır</li>
        <li>Telemetri yalnızca anonimleştirilmiş, toplu olay sayıları gönderir — sıfır kişisel veri</li>
        <li>Tüm değerler, tanılama ayarlarından bağımsız olarak depolama veya iletimden önce düzeltilir</li>
        <li>Telemetri, tüm kimlik doğrulama sayfalarında tamamen bastırılır</li>
        <li>Hız sınırlama ve otomatik istemci geri çekilmesi, yanlışlıkla fazla raporlamayı önler</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Phantom Wing, tüm tanılamaları süresiz olarak kapalı bırakabilmeniz için tasarlanmıştır.
        Opt-in denetimleri, bir şeyler ters gittiğinde size ve destek ekibine ortak bir dil vermek
        için mevcuttur — varsayılan olarak veri toplamak için değil.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
