<?php declare(strict_types=1);

/**
 * soc2/request/index.php
 *
 * Purpose: SOC 2 report access request form with inline NDA review, NDJSON
 * persistence, admin notification email, and requester NDA delivery email.
 *
 * On submit:
 *  1. Validates and sanitizes all inputs.
 *  2. Appends a row to /var/www/paycal/logs/soc2-nda-requests.ndjson (LOCK_EX).
 *  3. Sends admin notification to the configured contact address.
 *  4. Sends a copy of the NDA to the requester asking them to sign and return.
 *
 * Why here: self-contained page controller; no DB; log file mirrors the
 * ContactSupportTelemetry pattern used elsewhere in this codebase.
 */

require_once __DIR__ . '/../../config.php';

use PayCal\Domain\Config\Environment;
use PayCal\Infrastructure\Email\EmailTransport;

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'SOC 2 Report Request - [PayCal]';
$pageLabel = 'SOC 2 Report Request';

const SOC2_NDA_LOG_PATH = '/var/www/paycal/logs/soc2-nda-requests.ndjson';

/**
 * Append one NDJSON line to the request log.
 *
 * @param array<string, string> $row
 */
function soc2NdaLogRequest(array $row): void
{
  $dir = dirname(SOC2_NDA_LOG_PATH);
  if (!is_dir($dir)) {
    @mkdir($dir, 0770, true);
  }
  $line = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($line === false) {
    return;
  }
  @file_put_contents(SOC2_NDA_LOG_PATH, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

/**
 * Send admin notification to the PayCal contact address.
 *
 * @param array<string, string> $data
 */
function soc2NdaSendAdminNotification(array $data): bool
{
  $to = Environment::emailContact();
  if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
    return false;
  }

  $eName    = htmlspecialchars($data['name'],    ENT_QUOTES, 'UTF-8');
  $eEmail   = htmlspecialchars($data['email'],   ENT_QUOTES, 'UTF-8');
  $eCompany = htmlspecialchars($data['company'], ENT_QUOTES, 'UTF-8');
  $ePurpose = nl2br(htmlspecialchars($data['purpose'], ENT_QUOTES, 'UTF-8'));
  $eStamp   = htmlspecialchars($data['submitted_at_utc'], ENT_QUOTES, 'UTF-8');

  $html = <<<HTML
  <html><body style="font-family:Arial,sans-serif;color:#1f2933;padding:24px;">
  <h2 style="margin:0 0 16px">New SOC 2 NDA Request</h2>
  <table style="border-collapse:collapse;width:100%;max-width:600px;">
    <tr><td style="padding:6px 12px 6px 0;font-weight:700;width:120px;">Name</td><td style="padding:6px 0">{$eName}</td></tr>
    <tr><td style="padding:6px 12px 6px 0;font-weight:700;">Email</td><td style="padding:6px 0">{$eEmail}</td></tr>
    <tr><td style="padding:6px 12px 6px 0;font-weight:700;">Company</td><td style="padding:6px 0">{$eCompany}</td></tr>
    <tr><td style="padding:6px 12px 6px 0;font-weight:700;vertical-align:top">Purpose</td><td style="padding:6px 0">{$ePurpose}</td></tr>
    <tr><td style="padding:6px 12px 6px 0;font-weight:700;">Submitted</td><td style="padding:6px 0">{$eStamp}</td></tr>
  </table>
  <p style="margin-top:20px;color:#616e7c;">
    The requester has acknowledged the NDA terms on the request form.<br>
    Reply to this email to begin the countersignature process.
  </p>
  </body></html>
  HTML;

  $text = "New SOC 2 NDA Request\n\n"
    . "Name:      {$data['name']}\n"
    . "Email:     {$data['email']}\n"
    . "Company:   {$data['company']}\n"
    . "Purpose:   {$data['purpose']}\n"
    . "Submitted: {$data['submitted_at_utc']}\n\n"
    . "The requester has acknowledged the NDA terms on the request form.\n"
    . "Reply to this email to begin the countersignature process.\n";

  $from    = 'PayCal <' . Environment::emailReplyTo() . '>';
  $subject = '[PayCal] SOC 2 NDA Request — ' . $data['company'];

  $transport = new EmailTransport();
  return $transport->send(
    to:       $to,
    subject:  $subject,
    htmlBody: $html,
    textBody: $text,
    from:     $from,
    headers:  ['Reply-To' => $data['email']]
  );
}

/**
 * Send the NDA document to the requester asking them to sign and return.
 *
 * @param array<string, string> $data
 */
function soc2NdaSendRequesterCopy(array $data): bool
{
  $to = $data['email'];
  if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
    return false;
  }

  $eName    = htmlspecialchars($data['name'],    ENT_QUOTES, 'UTF-8');
  $eCompany = htmlspecialchars($data['company'], ENT_QUOTES, 'UTF-8');
  $eDate    = htmlspecialchars(gmdate('F j, Y'), ENT_QUOTES, 'UTF-8');

  $ndaHtml  = soc2NdaHtml($data['name'], $eDate);
  $ndaText  = soc2NdaText($data['name'], gmdate('F j, Y'));

  $intro = <<<HTML
  <html><body style="font-family:Arial,sans-serif;color:#1f2933;padding:24px;max-width:680px;">
  <p>Hi {$eName},</p>
  <p>Thank you for requesting access to PayCal Technologies Inc.'s SOC 2 materials.</p>
  <p>Please review the Non-Disclosure Agreement below, <strong>print or copy it</strong>,
  complete the Recipient signature section, and return the signed copy by replying to this email.</p>
  <p>Once we receive your signed NDA, we will provide controlled access to the requested materials.</p>
  <p style="color:#616e7c;font-size:0.9em;">If you have questions, reply to this email or contact
  <a href="mailto:info@paycal.app">info@paycal.app</a>.</p>
  <hr style="margin:32px 0;border:none;border-top:1px solid #d9e2ec;">
  HTML;

  $html = $intro . $ndaHtml . '</body></html>';
  $text = "Hi {$data['name']},\n\n"
    . "Thank you for requesting access to PayCal Technologies Inc.'s SOC 2 materials.\n\n"
    . "Please review the NDA below, complete the Recipient signature section, and reply with your signed copy.\n"
    . "Once we receive it, we will provide controlled access.\n\n"
    . "Questions? Reply to this email or write to info@paycal.app.\n\n"
    . str_repeat('-', 60) . "\n\n"
    . $ndaText;

  $from    = 'PayCal <' . Environment::emailReplyTo() . '>';
  $subject = 'PayCal SOC 2 NDA — Please Sign and Return';

  $transport = new EmailTransport();
  return $transport->send(
    to:       $to,
    subject:  $subject,
    htmlBody: $html,
    textBody: $text,
    from:     $from
  );
}

/**
 * Return the NDA as an HTML fragment (for embedding in email and page display).
 */
function soc2NdaHtml(string $recipientName = '[Full Legal Name]', string $effectiveDate = '[YYYY-MM-DD]'): string
{
  $r = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
  $d = htmlspecialchars($effectiveDate, ENT_QUOTES, 'UTF-8');

  return <<<HTML
  <div style="font-family:Georgia,serif;color:#1f2933;line-height:1.7;max-width:680px;">
    <h2 style="text-align:center;letter-spacing:0.04em;">NON-DISCLOSURE AGREEMENT<br><span style="font-size:0.85em;font-weight:400;">(SOC 2 Materials Access)</span></h2>
    <p><strong>Effective Date:</strong> {$d}</p>
    <p>This Non-Disclosure Agreement ("Agreement") is entered into by and between:<br>
    <strong>Disclosing Party:</strong> PayCal Technologies Inc. ("PayCal Technologies Inc.")<br>
    <strong>Receiving Party:</strong> {$r} ("Recipient")<br>
    Collectively, the "Parties".</p>

    <h3>1. Purpose</h3>
    <p>Recipient will be granted limited access to PayCal Technologies Inc.'s <strong>SOC 2-related materials</strong>, including evidence bundles, policies, reports, and supporting technical documentation (the "Confidential Information"), solely for the purpose of evaluating PayCal Technologies Inc.'s security, compliance posture, or business relationship (the "Purpose").</p>

    <h3>2. Definition of Confidential Information</h3>
    <p>"Confidential Information" includes, without limitation:</p>
    <ul>
      <li>SOC 2 evidence bundles and supporting artifacts</li>
      <li>Security policies, procedures, and internal controls</li>
      <li>System architecture, infrastructure details, and operational logs</li>
      <li>Test results, audit traces, and change management records</li>
      <li>Any non-public technical or business information disclosed by PayCal Technologies Inc.</li>
    </ul>
    <p>Confidential Information does <strong>not</strong> include information that is or becomes publicly available without breach of this Agreement, was lawfully known to Recipient prior to disclosure, is independently developed without use of PayCal Technologies Inc. information, or is received from a third party without restriction.</p>

    <h3>3. Obligations of Recipient</h3>
    <p>Recipient agrees to: use Confidential Information <strong>solely for the Purpose</strong>; restrict access to individuals with a <strong>need to know</strong>; protect the information using <strong>reasonable and industry-standard safeguards</strong>; not copy, distribute, or disclose Confidential Information without prior written consent; not attempt to reverse engineer, exploit, or misuse any disclosed system details.</p>

    <h3>4. Security Requirements</h3>
    <p>Recipient shall maintain appropriate administrative, technical, and physical safeguards; prevent unauthorized access, disclosure, or loss; notify PayCal Technologies Inc. <strong>immediately</strong> upon any suspected or actual breach.</p>

    <h3>5. No License or Transfer of Rights</h3>
    <p>All Confidential Information remains the exclusive property of PayCal Technologies Inc. No license, ownership, or other rights are granted except as explicitly stated.</p>

    <h3>6. Term and Survival</h3>
    <p>This Agreement is effective as of the Effective Date. Confidentiality obligations remain in effect for <strong>3 years</strong> from disclosure, or longer if required by law or audit obligations. Trade secrets remain protected indefinitely where applicable.</p>

    <h3>7. Return or Destruction</h3>
    <p>Upon request or termination, Recipient must return or securely destroy all Confidential Information. Written confirmation of destruction may be required.</p>

    <h3>8. Legal Disclosure</h3>
    <p>If Recipient is required by law to disclose Confidential Information, Recipient must provide prompt notice to PayCal Technologies Inc. (where legally permitted) and disclosure must be limited to the minimum required.</p>

    <h3>9. No Warranty</h3>
    <p>Confidential Information is provided "as is" without warranties of any kind. PayCal Technologies Inc. does not guarantee completeness or fitness for a particular purpose.</p>

    <h3>10. Remedies</h3>
    <p>Recipient acknowledges that unauthorized disclosure may cause irreparable harm. PayCal Technologies Inc. is entitled to seek injunctive relief and other legal remedies.</p>

    <h3>11. Governing Law</h3>
    <p>This Agreement shall be governed by the laws of the <strong>Province of Alberta, Canada</strong>.</p>

    <h3>12. Entire Agreement</h3>
    <p>This Agreement constitutes the entire agreement between the Parties regarding Confidential Information and supersedes all prior discussions.</p>

    <h3>13. Signatures</h3>
    <div style="width:100%;margin-top:8px;">
      <p><strong>PayCal Technologies Inc.</strong></p>
      <p>Name: ______________________<br>Title: ______________________<br>Signature: __________________<br>Date: _______________________</p>
      <hr style="margin:18px 0;border:none;border-top:1px solid #d9e2ec;">
      <p><strong>Recipient</strong></p>
      <p>Name: ______________________<br>Title: ______________________<br>Organization: _______________<br>Signature: __________________<br>Date: _______________________</p>
    </div>

    <h3>14. Optional Addendum (SOC 2 Access Scope)</h3>
    <p>If applicable, access may include: read-only access to SOC 2 evidence bundles; time-limited access to transparency or audit materials; redacted or scoped datasets as determined by PayCal Technologies Inc. Access is granted on a <strong>least-privilege basis</strong> and may be revoked at any time.</p>
  </div>
  HTML;
}

/**
 * Return the NDA as plain text (for email text-part).
 */
function soc2NdaText(string $recipientName = '[Full Legal Name]', string $effectiveDate = '[YYYY-MM-DD]'): string
{
  return <<<TEXT
  NON-DISCLOSURE AGREEMENT (SOC 2 Materials Access)
  ===================================================

  Effective Date: {$effectiveDate}

  Disclosing Party: PayCal Technologies Inc. ("PayCal Technologies Inc.")
  Receiving Party: {$recipientName} ("Recipient")

  1. PURPOSE
  Recipient will be granted limited access to PayCal Technologies Inc.'s SOC 2-related materials solely for evaluating
  PayCal Technologies Inc.'s security, compliance posture, or business relationship.

  2. CONFIDENTIAL INFORMATION
  Includes SOC 2 evidence bundles, security policies, system architecture, test results, audit traces,
  and any non-public technical or business information disclosed by PayCal Technologies Inc.

  3. OBLIGATIONS
  Use Confidential Information solely for the Purpose. Restrict access to those with a need to know.
  Protect with reasonable and industry-standard safeguards. No copying, distribution, or disclosure
  without prior written consent.

  4. SECURITY REQUIREMENTS
  Maintain appropriate safeguards. Notify PayCal Technologies Inc. immediately of any suspected or actual breach.

  5. NO LICENSE
  All Confidential Information remains the exclusive property of PayCal Technologies Inc. No license is granted.

  6. TERM AND SURVIVAL
  3 years from disclosure, or longer if required by law. Trade secrets protected indefinitely.

  7. RETURN OR DESTRUCTION
  Upon request or termination, return or securely destroy all Confidential Information.

  8. LEGAL DISCLOSURE
  Prompt notice to PayCal Technologies Inc. if legally required to disclose. Minimum required disclosure only.

  9. NO WARRANTY
  Provided "as is" without warranties of any kind.

  10. REMEDIES
  Unauthorized disclosure may cause irreparable harm. PayCal Technologies Inc. may seek injunctive relief.

  11. GOVERNING LAW
  Province of Alberta, Canada.

  12. ENTIRE AGREEMENT
  Supersedes all prior discussions regarding Confidential Information.

  ---

  SIGNATURES

  PayCal Technologies Inc.
  Name: __________________________
  Title: __________________________
  Signature: ______________________
  Date: ___________________________

  Recipient
  Name: __________________________
  Title: __________________________
  Organization: ____________________
  Signature: ______________________
  Date: ___________________________

  ---

  14. OPTIONAL ADDENDUM (SOC 2 Access Scope)
  Read-only access. Time-limited. Least-privilege basis. May be revoked at any time.
  TEXT;
}

// ── Form handling ────────────────────────────────────────────────────────────

$submitted = false;
$errors    = [];
/** @var array<string, string> $requestSummary */
$requestSummary = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name      = is_scalar($_POST['name']       ?? null) ? trim((string) $_POST['name'])       : '';
  $email     = is_scalar($_POST['email']      ?? null) ? trim((string) $_POST['email'])      : '';
  $company   = is_scalar($_POST['company']    ?? null) ? trim((string) $_POST['company'])    : '';
  $purpose   = is_scalar($_POST['purpose']    ?? null) ? trim((string) $_POST['purpose'])    : '';
  $ndaAccepted = isset($_POST['nda_accept']) && (string) $_POST['nda_accept'] === 'yes';

  // Sanitize lengths
  $name    = mb_substr($name,    0, 180);
  $email   = mb_substr($email,   0, 254);
  $company = mb_substr($company, 0, 180);
  $purpose = mb_substr($purpose, 0, 1200);

  if ($name === '') {
    $errors[] = 'Full name is required.';
  }
  if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $errors[] = 'Provide a valid email address.';
  }
  if ($company === '') {
    $errors[] = 'Company or organization name is required.';
  }
  if ($purpose === '') {
    $errors[] = 'Purpose is required.';
  }
  if (!$ndaAccepted) {
    $errors[] = 'You must acknowledge the NDA terms before submitting.';
  }

  if ($errors === []) {
    $stamp = gmdate('Y-m-d H:i:s') . ' UTC';
    $requestSummary = [
      'name'              => $name,
      'email'             => $email,
      'company'           => $company,
      'purpose'           => $purpose,
      'submitted_at_utc'  => $stamp,
    ];

    // 1. Persist
    soc2NdaLogRequest($requestSummary + ['event' => 'soc2_nda_request']);

    // 2. Admin notification (best-effort; non-fatal)
    soc2NdaSendAdminNotification($requestSummary);

    // 3. Send NDA copy to requester asking for signature (best-effort; non-fatal)
    soc2NdaSendRequesterCopy($requestSummary);

    $submitted = true;
  }
}

require_once HTML . '/header.php';
echo PHP_EOL . '<link rel="stylesheet" href="' . \PayCal\Domain\Render::cssURL('transparency') . '">' . PHP_EOL;
?>
<article class="article doc-article">
  <header class="doc-article-header">
    <h1>SOC 2 Report Request</h1>
    <p class="deck">Complete this intake form to request SOC 2 report access. You will receive a copy of the NDA by email with instructions to sign and return it before access is granted.</p>
  </header>

  <section class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Request Flow</h2>
      <ol class="doc-fact-list">
        <li>Submit your name, email, company, and access purpose below</li>
        <li>Acknowledge the NDA terms on this form</li>
        <li>We send you the NDA by email — sign and return by reply</li>
        <li>We countersign and deliver controlled access to the report package</li>
      </ol>
    </section>

    <?php if ($submitted): ?>
      <section class="doc-section success">
        <h2>Request Received</h2>
        <p>Your request has been logged and the PayCal team has been notified. A copy of the NDA has been sent to <strong><?= htmlspecialchars($requestSummary['email'], ENT_QUOTES, 'UTF-8') ?></strong> — please sign it and reply to that email to advance your request.</p>
        <ul class="doc-fact-list">
          <li><strong>Name:</strong> <?= htmlspecialchars($requestSummary['name'],    ENT_QUOTES, 'UTF-8') ?></li>
          <li><strong>Email:</strong> <?= htmlspecialchars($requestSummary['email'],  ENT_QUOTES, 'UTF-8') ?></li>
          <li><strong>Company:</strong> <?= htmlspecialchars($requestSummary['company'], ENT_QUOTES, 'UTF-8') ?></li>
          <li><strong>Submitted:</strong> <?= htmlspecialchars($requestSummary['submitted_at_utc'], ENT_QUOTES, 'UTF-8') ?></li>
        </ul>
        <p>For immediate follow-up, email <a href="mailto:info@paycal.app?subject=SOC%202%20Request%20Follow-up">info@paycal.app</a>.</p>
      </section>
    <?php else: ?>
      <?php if ($errors !== []): ?>
        <section class="doc-section">
          <h2>Fix Required</h2>
          <ul class="doc-fact-list">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <section class="doc-section">
        <h2>Request Form</h2>
        <form method="post" action="/soc2/request/" class="a11y-feedback-form">
          <p>
            <label for="soc2-name"><strong>Full Legal Name</strong></label><br>
            <input id="soc2-name" name="name" type="text" required maxlength="180" autocomplete="name">
          </p>
          <p>
            <label for="soc2-email"><strong>Email</strong></label><br>
            <input id="soc2-email" name="email" type="email" required maxlength="254" autocomplete="email">
          </p>
          <p>
            <label for="soc2-company"><strong>Company / Organization</strong></label><br>
            <input id="soc2-company" name="company" type="text" required maxlength="180" autocomplete="organization">
          </p>
          <p>
            <label for="soc2-purpose"><strong>Purpose of Access</strong></label><br>
            <textarea id="soc2-purpose" name="purpose" rows="5" required maxlength="1200" placeholder="Describe your intended use (e.g. vendor due diligence, security assessment)"></textarea>
          </p>
          <p>
            <label>
              <input type="checkbox" name="nda_accept" value="yes" required>
              I have reviewed the NDA terms below and agree to be bound by them.
            </label>
          </p>
          <p>
            <button type="submit" class="doc-read-more">Submit Request</button>
          </p>
        </form>
      </section>
    <?php endif; ?>

    <section class="doc-section">
      <details>
        <summary><strong>Non-Disclosure Agreement — Full Text</strong></summary>
        <div style="margin-top:1.25rem;">
          <?php echo soc2NdaHtml('[Your Full Legal Name]', '[Date of Execution]'); ?>
        </div>
      </details>
    </section>

  </section>
</article>
<?php
require_once HTML . '/footer.php';
