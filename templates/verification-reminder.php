<section id="email-verification-reminder" class="verification-reminder" role="alert" aria-live="polite">
	<div class="verification-reminder__content">
				<strong>__AUTH_VERIFICATION_REMINDER_TITLE__</strong>
				<span>__AUTH_VERIFICATION_REMINDER_BODY__</span>
	</div>
	<form id="verification-code-form" class="verification-reminder__form" method="post" action="__SITE_REGISTER_VERIFY_URL__">
		<input
			type="text"
			name="verification_code"
			class="verification-reminder__input"
			placeholder="__AUTH_VERIFICATION_REMINDER_CODE_PLACEHOLDER__"
			required
			autofocus
		/>
				<button type="submit" class="btn btn_primary btn_small">__AUTH_VERIFICATION_REMINDER_VERIFY_BUTTON__</button>
	</form>
	<div class="verification-reminder__resend">
		<a id="resend-verification-email-link" href="#" class="verification-reminder__link">__AUTH_VERIFICATION_REMINDER_RESEND_LINK__</a>
		<p id="verification_resend_cooldown_hint" class="verification-reminder__cooldown-hint" hidden></p>
	</div>
	<input type="hidden" id="resend-verification-endpoint" value="__SITE_API_RESEND_VERIFY_URL__" />
</section>
<script src="__VERIFICATION_REMINDER_JS_URL__"__VERIFICATION_REMINDER_JS_INTEGRITY_ATTR__ nonce="__CSP_NONCE__"></script>

