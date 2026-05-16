<?php
/**
 * Public Transparency: Auth, Passkey, and Redis Hardening — May 2026
 *
 * PURPOSE: Disclose all findings from the May 12, 2026 internal security audit of
 * authentication, passkey, and Redis infrastructure. Describes each flaw, its
 * risk, and exactly how it was fixed.
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
$pageTitle = 'Auth, Passkey ve Redis Güçlendirme — Mayıs 2026 - [PayCal]';
$pageLabel = 'Auth, Passkey & Redis Güçlendirme — Mayıs 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Auth, Passkey &amp; Redis Güçlendirme — Mayıs 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Auth, Passkey &amp; Redis Güçlendirme — Mayıs 2026</h1>
    <p class="deck">
      12 Mayıs 2026&apos;da kimlik doğrulama, passkey ve Redis altyapımızın dahili denetimini
      gerçekleştirdik. On bir sorun bulduk — hepsi kendi yazdığımız kodda. Bu makale ne
      bulduğumuzu, neden önemli olduğunu ve tam olarak neyi değiştirdiğimizi belgeliyor.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Yönetici Özeti</h2>
      <table class="doc-table" aria-label="Denetim bulgularının yönetici özeti">
        <tbody>
          <tr>
            <td><strong>Denetim Tarihi</strong></td>
            <td>12 Mayıs 2026</td>
          </tr>
          <tr>
            <td><strong>Kapsam</strong></td>
            <td>Kimlik doğrulama, passkey (WebAuthn) ve Redis altyapısı</td>
          </tr>
          <tr>
            <td><strong>Toplam Bulgu</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Önem Dağılımı</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Düzeltme Durumu</strong></td>
            <td>Tüm bulgular <code>493d5e44</code> commit&apos;inde çözüldü. Tam test paketi başarıyla geçti. Regresyon yok.</td>
          </tr>
          <tr>
            <td><strong>İstismar Kanıtı</strong></td>
            <td>Yok</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Bunu Neden Yayınlıyoruz</h2>
      <p>
        Bu sorunları kendi uygulama kodumuzdaki ve altyapı katmanlarımızdaki — üçüncü taraf
        bağımlılıklarında veya harici hizmetlerde değil — bulduk. İncelediğimiz, commit ettiğimiz
        ve teslim ettiğimiz kod.
      </p>
      <p>
        Bunu yayınlıyoruz çünkü güvenlik şeffaflığı, dış CVE&apos;leri açıklamaktan veya denetimlerden
        geçmekten daha fazlasını gerektiriyor. Kendi ekibimizin kendimiz için belirlediğimiz standardı
        karşılamayan kodu teslim ettiğinde kamuoyu önünde hesap vermek anlamına geliyor.
      </p>
      <p>
        Bundan utanmıyoruz. Daha büyük başarısızlık bu sorunları keşfedip açıklamamayı seçmek olurdu.
      </p>
    </section>

    <section class="doc-section">
      <h2>Denetim Metodolojisi</h2>
      <p>
        Bu denetim 12 Mayıs 2026&apos;da mühendislik ekibi tarafından dahili olarak gerçekleştirildi.
        İnceleme, kimlik doğrulama durumu yönetimi, WebAuthn kimlik bilgisi yaşam döngüsü ve Redis
        anahtar yönetimiyle ilgili tüm kod yollarını kapsadı.
      </p>
      <ul class="doc-list">
        <li><strong>Oturum oluşturma, passkey kaydı, passkey girişi ve hesap kurtarma akışlarında yer alan tüm kontrolör, etki alanı ve altyapı dosyalarının manuel kod incelemesi.</strong></li>
        <li><strong>Statik analiz</strong> PHPStan Seviye 9 aracılığıyla — güvensiz tip veya erişilemeyen kod yollarına sıfır tolerans.</li>
        <li><strong>Tehdit modellemesi</strong> WebAuthn Seviye 2 spesifikasyonuna göre (§6.1 kimlik doğrulayıcı verileri, §7.1 kayıt töreni, §7.2 kimlik doğrulama töreni).</li>
        <li><strong>Regresyon testi</strong> düzeltme sonrasında tam PHPUnit regresyon paketi ile. Tüm testler geçti.</li>
      </ul>
      <p>Bu incelemeyi hiçbir harici denetçi, hata ödül raporu veya güvenlik olayı önceden tetiklemedi. Bu sorunlar rutin bir dahili süreç aracılığıyla tespit edildi.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Mühendislik Felsefemiz</h2>
      <p>Bu denetim, temel saydığımız üç prensipte başarısızlıkları ortaya çıkardı:</p>
      <ul class="doc-list">
        <li>
          <strong>Doğruluktan önce atomiklik.</strong> İki işlemin birlikte gerçekleşmesi gerekiyorsa,
          bunları tek bir işlem olarak ele alın ya da tasarımı hiç denemeyin. &ldquo;Çoğu zaman doğru&rdquo;
          olan bir sistem doğru değildir.
        </li>
        <li>
          <strong>Katmanlı savunma.</strong> Hiçbir tek kontrol, bir güvenlik sınırındaki tek bariyer
          olmamalıdır. Veritabanı bir kimlik bilgisini iptal edilmiş olarak işaretliyorsa, kayıt yolu
          da bunu uygulamak zorundadır. Savunmanın katmanlar arasında boşlukları olmamalıdır.
        </li>
        <li>
          <strong>Bilgi asimetrisi bir tasarım hedefi olarak.</strong> Sistemi araştıran bir saldırgan,
          içeride neler olduğu hakkında olabildiğince az şey öğrenmelidir. Hata mesajları, günlük
          girişleri ve yanıt süreleri hepsi maruz kalma yüzeyleridir.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Bulgu 1 &mdash; Atomik Olmayan <code>hset + expire</code> (Redis Race Condition) <span class="doc-badge high">High</span></h2>
      <p><strong>Kategori: Redis / Atomiklik</strong></p>
      <p>
        Dokuz çağrı noktasında bir Redis hash&apos;i <code>HSET</code> ile yazılıyor ve ardından
        ayrı bir <code>EXPIRE</code> komutuyla hemen TTL atanıyordu:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        Bu, Redis&apos;e iki ayrı gidiş-dönüş demektir. PHP süreci bu ikisi arasında sonlanırsa,
        kesilirse, zaman aşımına uğrarsa veya Redis anlık bir arıza yaşarsa, hash sona erme süresi
        olmadan yazılır ve Redis&apos;te sonsuza dek yaşar.
      </p>
      <p>Etkilenen çağrı noktaları ve güvenlik sonuçları:</p>
      <table class="doc-table" aria-label="Atomik olmayan hset+expire için etkilenen çağrı noktaları">
        <thead>
          <tr>
            <th scope="col">Çağrı Noktası</th>
            <th scope="col">Anahtar Türü</th>
            <th scope="col">Eksik TTL&apos;nin Sonucu</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Oturum kaydı</td>
            <td>Oturum hiçbir zaman sona ermez — hesap amaçlanan ömrün ötesinde erişilebilir</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (enrollment challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Süresi dolmuş challenge verileri amaçlanan ömrün ötesinde kalır, yeniden oynatma riskini artırır</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (register challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Yukarıdakiyle aynı</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (login challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Yukarıdakiyle aynı</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Kurtarma passkey challenge&apos;ı</td>
            <td>Kurtarma oturumu verileri hiçbir zaman sona ermez</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (kod çıkarma)</td>
            <td>Kurtarma e-posta kodu</td>
            <td>Tek kullanımlık kodlar amaçlanan son kullanma penceresinin ötesinde hayatta kalır</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (kodu yeniden gönder)</td>
            <td>Kurtarma e-posta kodu</td>
            <td>Yukarıdakiyle aynı</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>Tek kullanımlık yönetici token&apos;ları</td>
            <td>5 dakika içinde sona ermesi tasarlanan token&apos;lar süresiz olarak kalabilir</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Kurtarma işlemi kaydı</td>
            <td>Kurtarma işlemi durumu hiçbir zaman temizlenmez</td>
          </tr>
        </tbody>
      </table>
      <p>
        Oturumlar için bu, erişim ömrünün doğrudan ihlalidir. Bir oturumun katı bir üst sınırı
        olmalıdır. TTL hiçbir zaman ayarlanmazsa, bu sınır mevcut değildir.
      </p>
      <p>
        Tek kullanımlık yetenek token&apos;ları için, tam olarak 300 saniye geçerli olacak şekilde
        tasarlanan bir token günler sonra hâlâ geçerli olabilir.
      </p>
      <p><strong>Düzeltme:</strong> <code>Database::hsetex()</code>&apos;i tanıttık — her iki işlemi bir Redis
      <code>MULTI/EXEC</code> işlemi içinde gerçekleştiren ve onları atomik yapan bir sarmalayıcı. İşlemler
      aynı yürütme biriminde çalışır, bu nedenle anahtar TTL&apos;si uygulanmadan var olamaz. Anahtarın ya
      verisi ve TTL&apos;si vardır ya da hiçbir şey yoktur.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Aynı anahtar üzerinde <code>hset</code>&apos;in ardından <code>expire</code> veren her çağrı noktası dönüştürüldü.</p>
    </section>

    <section class="doc-section">
      <h2>Bulgu 2 &mdash; Çıkış ve CSRF Geçersizleştirme Sessizce Başarısız Olabiliyordu <span class="doc-badge high">High</span></h2>
      <p><strong>Kategori: Redis / Çıkış, CSRF</strong></p>
      <p>
        <code>Database::del()</code> metodu — desene göre Redis anahtarlarını silmekten sorumlu —
        <em>okuma çoğaltması</em> kullanarak anahtarları numaralandırıyor ve ardından <em>birincil</em>&apos;e
        <code>DEL</code> komutları gönderiyordu:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        Redis çoğaltması eş zamansızdır. Çoğaltma milisaniyeler bile geri kalıyorsa, yeni yazılan
        anahtarı henüz içermiyor olabilir. Bu durumda <code>keys()</code> boş bir liste döndürür ve
        birincile hiçbir <code>DEL</code> gönderilmez. Anahtar hayatta kalır.
      </p>
      <p><code>del()</code>&apos;in iki en kritik çağırıcısı:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — çıkış:</strong> Bir kullanıcı çıkış yaptığında
          oturum anahtarını siliyoruz. Çoğaltma geri kalıyorsa, oturum anahtarlarının listesi boş
          döner, silme hiçbir zaman tetiklenmez ve oturum birincilde hâlâ mevcuttur. Kullanıcı çıkış
          yaptığına inanır. Yapmamıştır.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — nonce geçersizleştirme:</strong> CSRF token&apos;ları
          tek kullanımlık nonce&apos;lardır. İlk kullanımdan sonra silinmeleri gerekir. Silme hiçbir
          zaman tetiklenmezse, token ikinci bir istekte yeniden kullanılabilir. Tek kullanımlık, yeniden
          kullanılabilir hâle gelir.
        </li>
      </ul>
      <p>
        Bu hata, yalnızca yük altında veya geçici çoğaltma gecikmesi sırasında kendini gösterdiği
        için ince görünümlüdür. Tek bir Redis örneğine karşı geliştirmede hiçbir zaman tetiklenmez.
      </p>
      <p><strong>Düzeltme:</strong> Anahtar numaralandırma ve silme aynı örneği hedeflemelidir.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bulgu 3 &mdash; WebAuthn Kullanıcı Doğrulama Atlatma <span class="doc-badge high">High</span></h2>
      <p><strong>Kategori: Kimlik Doğrulama</strong></p>
      <p>
        <code>AccountRecoveryController</code>&apos;da, hesap kurtarmanın bir parçası olarak bir
        passkey kaydederken, <code>processCreate()</code> çağrısı <code>requireUserVerification</code>
        için <code>false</code> geçiriyordu:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — UV not enforced on verification
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    false,  // requireUserVerification — should be true
    true
);</code></pre>
      </div>
      <p>
        İstemciye gönderilen challenge <code>userVerification: 'required'</code> belirtiyordu —
        kimlik doğrulayıcıya kullanıcının biyometrik veya PIN doğrulamasını tamamlaması gerektiği
        söylendi. Ancak yanıtı doğrularken kütüphaneye UV bayrağının ayarlandığını zorunlu
        kılmamasını söylüyorduk.
      </p>
      <p>
        Değiştirilmiş bir istemci, UV biti temizlenmiş bir kimlik doğrulayıcı yanıtı gönderebilirdi.
        Sunucumuz, biyometrik doğrulamanın gerçekten gerçekleştiğini talep etmeksizin bunu kabul ederdi.
      </p>
      <p>
        Hesap kurtarma akışı, bir kullanıcının diğer kimlik bilgilerine erişimini kaybettiğinde
        izlediği yoldur. Bu, yönettiğimiz en yüksek riskli kimlik doğrulama yüzeyidir. Burada
        biyometrik zorunluluğu zayıflatmak tamamen yanlış bir uzlaşmadır.
      </p>
      <p><strong>Düzeltme:</strong> UV artık zorunlu kılınıyor. Kimlik doğrulayıcı verilerinin UV bayrağı taşımadığı yanıtlar reddediliyor.</p>
      <div class="doc-code-block">
        <pre><code>// After — UV enforced
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    true,   // requireUserVerification — enforced
    true
);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bulgu 4 &mdash; İmza Sayacı ile Klon Algılama Tekrar Oynatma Saldırılarını Kaçırıyordu <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategori: Kimlik Doğrulama</strong></p>
      <p>Passkey klon algılamamız şunu kontrol ediyordu:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        WebAuthn Seviye 2 spesifikasyonu (§6.1) şunu belirtiyor: saklanan imza sayacı sıfır değilse
        ve yeni imza sayacı saklanan değerden <em>kesinlikle büyük</em> değilse, kimlik bilgisi
        potansiyel olarak klonlanmış sayılmalıdır. Koşulumuz <code>&lt;</code> gerektiriyordu,
        <code>&lt;=</code> değil, bu yüzden eşit bir sayı — bir tekrar oynatma saldırısında olduğu
        gibi — klon bayrağını tetiklemeden geçiyordu.
      </p>
      <p><strong>Düzeltme:</strong> Spesifikasyona hizalandı.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bulgu 5 &mdash; İmza Sayacı Her Zaman Kalıcı Hâle Getirilmiyordu <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategori: Kimlik Doğrulama</strong></p>
      <p>Başarılı bir passkey girişinden sonra, imza sayacı güncellemesi sıfırdan farklı olmasına bağlıydı:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Bazı kimlik doğrulayıcılar, &ldquo;bu cihaz sayaç uygulamıyor&rdquo; anlamına gelen bir
        nöbetçi olarak <code>0</code> döndürür. Bir cihaz daha sonra gerçek bir sayaç döndürmeye
        başlarsa (yazılım güncellemesi veya kullanıcı sayaçları destekleyen bir platformda aynı
        kimlik bilgisini kaydeder), <code>0</code>&apos;ı sonsuza kadar sakladığımız için ilk gerçek
        sayacı hiçbir zaman kalıcı hâle getiremezdik.
      </p>
      <p>
        Klon algılama (Bulgu 4), saklanan sayacın sıfır olmamasını gerektirir; kalıcı olarak
        <code>0</code> olarak etiketlediğimiz bir kimlik doğrulayıcı, sayaç tabanlı korumadan
        kalıcı olarak hariç tutulur.
      </p>
      <p><strong>Düzeltme:</strong> İmza sayacı her zaman yazılır. Klon algılama eşiği yorumu yönetir.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bulgu 6 &mdash; İptal Edilmiş Passkey Yeniden Kaydedilebiliyordu <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategori: Kimlik Doğrulama</strong></p>
      <p>
        Bir kimlik bilgisi iptal edilmiş olarak işaretlendiğinde (klon algılama tetiklendiğinde),
        kayıt yolunda aynı <code>credential_id</code>&apos;nin yeniden kaydedilmesini engelleyen
        herhangi bir kontrol yoktu. Ham passkey kimlik bilgisine ve hesaba erişimi olan bir saldırgan,
        iptal edilmiş kimlik bilgisini yeniden kaydederek güvenliği ihlal edilmiş geçmişini silebilirdi.
      </p>
      <p>
        İptal, yalnızca kalıcı olduğunda anlamlıdır. Aynı kimlik bilgisiyle yeniden kayıt yoluyla
        üzerine yazılabiliyorsa, klon algılama kalıcı bir koruma sağlamaz.
      </p>
      <p><strong>Düzeltme:</strong> Mevcut bir kimlik bilgisi kaydında <code>revoked_at</code> boş değilse,
      yeniden kayıt HTTP 403 ile engellenir ve bir güvenlik günlüğü girişi yazılır.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bulgu 7 &mdash; Farklı Hata Yanıtları Aracılığıyla Hesap Sıralama <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategori: Bilgi İfşası</strong></p>
      <p>
        Tanınmayan bir e-posta ile passkey girişi denendiğinde, hata yanıtı gövdesi diğer başarısızlık
        durumlarından farklı bir biçim alıyordu — başka yerlerde döndürülen <code>{'error': 'passkey_invalid'}</code>
        gövdesi yerine boş bir <code>[]</code> veri yükü. API&apos;yi araştıran bir istemci, yanıt gövdesini
        inceleyerek &ldquo;bu e-postanın hesabı yok&rdquo; ile &ldquo;bu e-posta mevcut ancak challenge
        başarısız oldu&rdquo; arasındaki farkı anlayabilirdi.
      </p>
      <p>
        Ayrıca, ham e-posta adresi gözlemlenebilirlik günlüğüne yazılıyordu. Günlük toplama ardışık
        düzenleri hiçbir zaman ham kullanıcı e-posta adreslerini içermemelidir — günlük sistemi
        tehlikeye girerse, her sıralama girişimi bir e-posta listesine dönüşür.
      </p>
      <p><strong>Düzeltme:</strong> Hem &ldquo;e-posta bulunamadı&rdquo; hem de &ldquo;kayıtlı kimlik bilgisi yok&rdquo;
      artık aynı hata gövdesini döndürüyor. Gözlemlenebilirlik günlüğü yalnızca e-postanın SHA-256 hash&apos;ini
      kaydediyor — olay korelasyonu için yeterli, adresi yeniden oluşturmak için yetersiz.</p>
      <div class="doc-code-block">
        <pre><code>// Before
Lens::add('[PASSKEY] Login email not found', ['email' => $email]);
Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);

// After
Lens::add('[PASSKEY] Login email not found', ['email_hash' => hash('sha256', $email)]);
Response::error('Authentication failed.', ['error' => 'passkey_invalid'], HttpStatus::HTTP_UNAUTHORIZED);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bulgu 8 &mdash; Kurtarma Anahtarı DB Durumu E-posta Teslimi Onaylanmadan Yazılıyordu <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Kategori: Veri Bütünlüğü</strong></p>
      <p>
        Hesap kurtarma anahtarı oluşturma sırasında, sunucu kurtarma anahtarı e-postası
        gönderilmeden <em>önce</em> kullanıcı kaydına <code>recovery_key_generated = 1</code> ve
        <code>recovery_proof_key</code> yazıyordu:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — DB written first, email second
Database::hset(Keys::USER.':'.$user->user_uuid, [
    'recovery_key_generated' => '1',
    'recovery_proof_key' => $recoveryProofKey,
]);
$sent = EmailGarum::sendRecoveryKeyEmail(...);</code></pre>
      </div>
      <p>
        E-posta gönderilemeseydi, veritabanı <code>recovery_key_generated = 1</code> gösterirdi —
        sistem bir anahtarın verildiğine inanır. Kullanıcı onu hiçbir zaman almadı.
      </p>
      <p>
        Bu durumdaki bir kullanıcı için yeniden oluşturma yolu yoktur. Hesap kurtarma, o hesap için
        manuel müdahale yapılana kadar kalıcı olarak bozulur.
      </p>
      <p><strong>Düzeltme:</strong> E-posta teslimi önce onaylanır. Veritabanı durumu, gerçekte ne olduğunu yansıtır.</p>
      <div class="doc-code-block">
        <pre><code>// After — email first, then persist
$sent = EmailGarum::sendRecoveryKeyEmail(...);
if ($sent) {
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'recovery_key_generated' => '1',
        'recovery_proof_key' => $recoveryProofKey,
    ]);
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Bulgu 9 &mdash; Devre Dışı Kayıt Yolu Hâlâ Parola Alanlarını Topluyordu <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategori: Saldırı Yüzeyi</strong></p>
      <p>
        Parola tabanlı kayıt devre dışı bırakılmış olmasına rağmen, <code>RegistrationController</code>
        hâlâ POST&apos;tan <code>password</code> ve <code>confirm_password</code> okuyordu. PayCal
        kaydı yalnızca passkey ile gerçekleşir.
      </p>
      <p>
        Hiçbir amaca hizmet etmeyen alanları toplamak zararsız değildir. Kullanıcı girdisinden
        okunan her değer bir yüzeydir: günlüğe kaydedilebilir, denetlenebilir, yanlışlıkla diğer
        işlevlere aktarılabilir veya hata yüklerine dahil edilebilir. Minimum yüzey prensibi,
        kullanmadığımız şeyleri toplamamayı gerektirir.
      </p>
      <p><strong>Düzeltme:</strong> Her iki alan da girdi toplama haritasından kaldırıldı.</p>
    </section>

    <section class="doc-section">
      <h2>Bulgu 10 &mdash; Kullanıcı E-postası E-posta Doğrulama 403 Yanıtında <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategori: Bilgi İfşası</strong></p>
      <p>
        Korunan kaynaklara erişim verilmeden önce e-posta doğrulamasını zorunlu kılan ara katman
        olan <code>EmailVerificationGuard</code>, 403 yanıt gövdesine <code>user_email</code>
        ekliyordu:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        Bir saldırgan geçerli ancak doğrulanmamış bir oturum token&apos;ı elde ederse (oturum
        sabitleme veya ele geçirilmiş geçici bir bağlantı aracılığıyla), e-postayı kendisi sağlamadan
        403 yanıt gövdesinden hesaba bağlı e-posta adresini öğrenebilir. Bu hata yükündeki e-postadan
        yararlanan tek taraf, oturum token&apos;ına sahip olan ancak e-postaya sahip olmayan kişidir.
      </p>
      <p><strong>Düzeltme:</strong> E-posta alanı hata yükünden kaldırıldı.</p>
    </section>

    <section class="doc-section">
      <h2>Bulgu 11 &mdash; <code>EmailGarum::verifyNewUserEmail()</code>&apos;de Ölü Kod <span class="doc-badge low">Low</span></h2>
      <p><strong>Kategori: Ölü Kod / Saldırı Yüzeyi</strong></p>
      <p>
        <code>EmailGarum</code>, parola tabanlı e-posta değiştirme akışını yöneten 90 satırlık bir
        metot olan <code>verifyNewUserEmail()</code>&apos;i içeriyordu. Bu akış, platform tamamen
        passkey tabanlı kimlik doğrulamaya geçtiğinde değiştirildi. Metot, kod tabanında hiçbir
        yerde çağrılmıyordu.
      </p>
      <p>
        Ölü kod tarafsız değildir. Güvenlik inceleme yüzeyinde, statik analizde ve dosyayı okuyan
        herkesin bilişsel yükünde yer kaplar. Ayrıca, bunun kasıtlı olarak terk edildiğini bilmeyen
        gelecekteki bir geliştiricinin tam bağlam olmadan bunu yeni bir akışa bağlayabileceği riskini
        de barındırır.
      </p>
      <p><strong>Düzeltme:</strong> Kaldırıldı. Tüm çağrı noktalarının kaldırılmadan önce boş olduğu doğrulandı.</p>
    </section>

    <section class="doc-section">
      <h2>Tüm Bulguların Özeti</h2>
      <table class="doc-table" aria-label="Tüm bulguların özeti">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Bulgu</th>
            <th scope="col">Önem</th>
            <th scope="col">Kategori</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>9 çağrı noktasında atomik olmayan <code>hset + expire</code></td><td><span class="doc-badge high">High</span></td><td>Redis / Atomiklik</td></tr>
          <tr><td>2</td><td>Anahtar numaralandırma için okuma çoğaltması kullanan <code>del()</code></td><td><span class="doc-badge high">High</span></td><td>Redis / Çıkış, CSRF</td></tr>
          <tr><td>3</td><td>Hesap kurtarma kaydında WebAuthn UV atlatma</td><td><span class="doc-badge high">High</span></td><td>Kimlik Doğrulama</td></tr>
          <tr><td>4</td><td>İmza sayacı ile klon algılama tekrar oynatma saldırılarını kaçırıyordu</td><td><span class="doc-badge medium">Medium</span></td><td>Kimlik Doğrulama</td></tr>
          <tr><td>5</td><td>Kimlik doğrulayıcı sıfır döndürdüğünde imza sayacı kalıcı hâle getirilmiyordu</td><td><span class="doc-badge medium">Medium</span></td><td>Kimlik Doğrulama</td></tr>
          <tr><td>6</td><td>İptal edilmiş passkey yeniden kaydedilebiliyordu</td><td><span class="doc-badge medium">Medium</span></td><td>Kimlik Doğrulama</td></tr>
          <tr><td>7</td><td>Hata gövdesi + günlüklerdeki ham e-posta aracılığıyla hesap sıralama</td><td><span class="doc-badge medium">Medium</span></td><td>Bilgi İfşası</td></tr>
          <tr><td>8</td><td>Kurtarma anahtarı DB durumu e-posta onayından önce yazılıyordu</td><td><span class="doc-badge medium">Medium</span></td><td>Veri Bütünlüğü</td></tr>
          <tr><td>9</td><td>Devre dışı kayıt hâlâ parola alanlarını topluyordu</td><td><span class="doc-badge low">Low</span></td><td>Saldırı Yüzeyi</td></tr>
          <tr><td>10</td><td>Kullanıcı e-postası e-posta doğrulama 403 yanıtında</td><td><span class="doc-badge low">Low</span></td><td>Bilgi İfşası</td></tr>
          <tr><td>11</td><td>EmailGarum&apos;da ölü <code>verifyNewUserEmail()</code> metodu</td><td><span class="doc-badge low">Low</span></td><td>Ölü Kod</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>Neyi Doğru Yaptık</h2>
      <p>Tam bir tablo sunmak adına — mevcut olan temeller:</p>
      <ul class="doc-list">
        <li>
          <strong>Passkey öncelikli kimlik doğrulama.</strong> Platform, passkey kullanıcıları için
          parola geri dönüşü olmadan WebAuthn üzerinde çalışıyor. UV atlatma ve klon algılama sorunları,
          temelden sağlam bir mimari içindeki kusurlardı.
        </li>
        <li>
          <strong>Tek kullanımlık yetenek token&apos;ları.</strong> Yönetici düzeyindeki mutasyonlar
          zaten taze, süresi sınırlı token&apos;lar gerektiriyordu. Atomiklik düzeltmesi, eksik bir
          koruma eklemek yerine mevcut bir korumayı güçlendirdi.
        </li>
        <li>
          <strong>İmzalı güvenlik günlüğü.</strong> Bu commit&apos;te eklenen yeni
          <code>passkey_revoked_reregistration_blocked</code> olayları dahil olmak üzere her güvenlik
          olayı, yapılandırılmış alanlarla imzalanmış, yalnızca ekleme yapılabilen bir günlüğe yazılıyor.
        </li>
        <li>
          <strong>PHPStan Seviye 9&apos;da.</strong> Değiştirilen 11 dosyanın tamamı maksimum statik
          analiz titizliğiyle doğrulandı. Tam regresyon paketi, regresyon olmadan geçti.
        </li>
        <li>
          <strong>Klon algılama mevcuttu.</strong> Mantık mevcuttu ve kısmen doğruydu. Bulgu 4, eksik
          bir özellik değil, bir sınır koşulu hatasıydı.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Müşteri Etkisi</h2>
      <ul class="doc-list">
        <li><strong>İstismar kanıtı yok.</strong> Tüm bulgular, rutin kod incelemesi yoluyla dahili olarak tespit edildi. Bu açıklamadan önce hiçbir harici rapor, CVE veya olay gerçekleşmedi.</li>
        <li><strong>Düz metin kimlik bilgisi ifşası yok.</strong> Hiçbir parola veya kurtarma anahtarı ifşa edilmedi. Bekleyen kimlik bilgileri verileri şifrelenmiş olarak kalıyor. Biyometrik veriler kimlik doğrulayıcı cihazı hiçbir zaman terk etmiyor ve PayCal tarafından hiçbir zaman iletilmiyor veya saklanmıyor.</li>
        <li><strong>Yetkisiz hesap erişimi kanıtı yok.</strong> Güvenlik günlükleri, bu vektörlerin istismarıyla tutarlı anormal desenler göstermiyor.</li>
        <li><strong>Tüm bulgular açıklamadan önce düzeltildi.</strong> Bu makalede açıklanan her sorun, bu sayfa yayınlanmadan önce düzeltildi, commit edildi ve test edildi.</li>
        <li><strong>Tam regresyon paketi doğrulandı.</strong> Düzeltme sonrasında tam PHPUnit paketi ve PHPStan Seviye 9 statik analiz başarıyla tamamlandı.</li>
        <li><strong>İzleme genişletildi.</strong> Gelecekteki anormallikleri daha erken tespit etmek için passkey iptal uygulaması (Bulgu 6) için yeni güvenlik günlüğü olayları eklendi.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Önleme ve Tekrarlama Kontrolleri</h2>
      <p>Bu denetimden itibaren kalıcı politika olarak benimsenen iki mühendislik kuralı:</p>
      <div class="subject-example-cutout" role="note" aria-label="Yeni mühendislik kuralı: hsetex varsayılan Redis yazma deseni olarak">
        <h3><code>hsetex</code> Varsayılan Redis Yazma Desenidir</h3>
        <p>
          TTL&apos;li bir hash yazması gereken gelecekteki tüm kodlar <code>Database::hsetex()</code>&apos;i
          kullanmalıdır. Eski iki adımlı desen artık izin verilmiyor. Yeni örnekleri işaretlemek için
          PHPStan kuralları yazılacak.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="Yeni mühendislik kuralı: tüm anahtar işlemleri için yazma örneği önceliği">
        <h3>Tüm Anahtar İşlemleri İçin Yazma Örneği Önceliği</h3>
        <p>
          Doğruluğu az önce yazılanın yeniden okunmasına bağlı olan Redis işlemleri, yazma örneğini
          kullanmalıdır. Okuma çoğaltmaları yalnızca kritik olmayan, yoğun okuma sorguları içindir.
        </p>
      </div>
      <p>
        Bu düzeyde özgüllükteki öz-denetimler süregelen bir taahhüttür. Bulduklarımızı yayınlamaya
        devam edeceğiz. Gelecekteki raporlar
        <a href="<?php echo transparency_href('/transparency/'); ?>">Şeffaflık Merkezi</a>&apos;nde yayınlanacak.
      </p>
    </section>

    <section class="doc-section">
      <h2>Açıklama Zaman Çizelgesi</h2>
      <table class="doc-table" aria-label="Açıklama zaman çizelgesi">
        <thead>
          <tr>
            <th scope="col">Tarih</th>
            <th scope="col">Olay</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">12 Mayıs 2026</time></td>
            <td>Bulgular rutin bir dahili denetim oturumunda tespit edildi</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 Mayıs 2026</time></td>
            <td>Tüm düzeltmeler uygulandı ve commit edildi (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 Mayıs 2026</time></td>
            <td>Tam PHPUnit regresyon paketi başarıyla geçti, PHPStan Seviye 9 temiz</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 Mayıs 2026</time></td>
            <td>origin/main&apos;e gönderildi</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">12 Mayıs 2026</time></td>
            <td>Bu şeffaflık makalesi yayınlandı</td>
          </tr>
        </tbody>
      </table>
      <p>
        Tüm bulgular dahili olarak tespit edildi. Bu açıklamadan önce hiçbir harici rapor, CVE veya
        ihlal gerçekleşmedi. Bulguların herhangi birinin istismar edildiğine dair herhangi bir kanıt
        bulunmuyor.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
