<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

if (function_exists('org_js_index_i18n') === false) {
  function org_js_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');
Javascript::renderDocBlock();

?>

import PC from "<?php echo \PayCal\Domain\Config\Environment::appURL('js/'); ?>";
import PW from "<?php echo \PayCal\Domain\Config\Environment::appURL('js/phantomwing/'); ?>";
import { createDataGrid } from "/js/datagrid/";
import { initializeBillingSection } from "../core/billing.js";

(() => {
  'use strict';

  const Guardian = window.Guardian;
  if (!Guardian || typeof Guardian.setHTML !== 'function') {
    throw new Error('Guardian module is required before organizations/index.php');
  }

  const isAdminUser = <?php echo User::isAdmin() ? 'true' : 'false'; ?>;
  const isSuperAdminUser = <?php echo User::isSuperAdmin() ? 'true' : 'false'; ?>;
  const isManagerUser = <?php echo User::isManager() ? 'true' : 'false'; ?>;
  const hasActivePremiumSubscription = <?php echo SubscriptionGate::hasActivePremium(User::currentUUID()) ? 'true' : 'false'; ?>;
  const isElevatedStaffUser = isAdminUser || isSuperAdminUser || isManagerUser;
  const currentUserUUID = '<?php echo addslashes(User::currentUUID()); ?>';
  const isDebugEnabled = window.PAYCAL_DEBUG === true;
  const debugLog = (...args) => {
    if (isDebugEnabled) {
      PW.log(...args);
    }
  };

  const T = {
    loading: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOADING')); ?>',
    none: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NONE')); ?>',
    noInvites: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_INVITES')); ?>',
    noDiscovery: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_DISCOVERY')); ?>',
    noAudit: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_AUDIT')); ?>',
    selectFirst: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SELECT_FIRST')); ?>',
    loadOrgsFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOAD_ORGS_FAILED')); ?>',
    loadInvitesFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOAD_INVITES_FAILED')); ?>',
    loadDefaultsFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOAD_DEFAULTS_FAILED')); ?>',
    loadAuditFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOAD_AUDIT_FAILED')); ?>',
    discoveryRunning: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_DISCOVERY_RUNNING')); ?>',
    discoveryComplete: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_DISCOVERY_COMPLETE')); ?>',
    discoveryFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_DISCOVERY_FAILED')); ?>',
    inviteSent: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_INVITE_SENT')); ?>',
    inviteSendFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_INVITE_SEND_FAILED')); ?>',
    inviteRevoked: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_INVITE_REVOKED')); ?>',
    inviteRevokeFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_INVITE_REVOKE_FAILED')); ?>',
    defaultsSaved: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_DEFAULTS_SAVED')); ?>',
    defaultsSaveFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_DEFAULTS_SAVE_FAILED')); ?>',
    created: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CREATED')); ?>',
    createFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CREATE_FAILED')); ?>',
    enterInviteEmail: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ENTER_INVITE_EMAIL')); ?>',
    selectScope: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SELECT_SCOPE')); ?>',
    selectTransferTarget: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SELECT_TRANSFER_TARGET')); ?>',
    ownershipTransferred: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_OWNERSHIP_TRANSFERRED')); ?>',
    ownershipTransferFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_OWNERSHIP_TRANSFER_FAILED')); ?>',
    withdrawn: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_WITHDRAWN')); ?>',
    withdrawFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_WITHDRAW_FAILED')); ?>',
    siteLinked: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SITE_LINKED')); ?>',
    siteLinkFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SITE_LINK_FAILED')); ?>',
    unknown: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_UNKNOWN')); ?>',
    pending: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PENDING')); ?>',
    revoke: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REVOKE')); ?>',
    linkSite: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LINK_SITE')); ?>',
    auditActor: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_AUDIT_ACTOR')); ?>',
    nameMin: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NAME_MIN')); ?>',
    manageAccessUnavailable: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_MANAGE_ACCESS_UNAVAILABLE')); ?>',
    memberAccessManageDenied: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_MEMBER_ACCESS_MANAGE_DENIED')); ?>',
    discoveryUnavailable: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_DISCOVERY_UNAVAILABLE')); ?>',
    inviteAccepted: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_INVITE_ACCEPTED')); ?>',
    inviteAcceptFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_INVITE_ACCEPT_FAILED')); ?>',
    previewEmpty: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREVIEW_EMPTY')); ?>',
    previewLabel: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREVIEW_LABEL')); ?>',
    personal: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_TYPE_PERSONAL')); ?>',
    owner: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ROLE_OWNER')); ?>',
    member: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ROLE_MEMBER')); ?>',
    viewer: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ROLE_VIEWER')); ?>',
    contributor: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ROLE_CONTRIBUTOR')); ?>',
    coordinator: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ROLE_COORDINATOR')); ?>',
    manager: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ROLE_MANAGER')); ?>',
    loadingDetails: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOADING_DETAILS')); ?>',
    noRelationships: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_RELATIONSHIPS')); ?>',
    removeConfirmPrefix: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REMOVE_CONFIRM_PREFIX')); ?>',
    removeConfirmSuffix: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REMOVE_CONFIRM_SUFFIX')); ?>',
    removeFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REMOVE_FAILED')); ?>',
    premiumAdminLocked: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREMIUM_ADMIN_LOCKED')); ?>',
    selfOrgWip: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SELF_ORG_WIP')); ?>',
    premiumAdminLockedDetailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREMIUM_ADMIN_LOCKED_DETAILED')); ?>',
    memberInviteSent: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_MEMBER_INVITE_SENT')); ?>',
    memberInviteNeedsPersonalOrg: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_MEMBER_INVITE_NEEDS_PERSONAL_ORG')); ?>',
    requestJoinPending: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REQUEST_JOIN_PENDING')); ?>',
    requestJoinFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REQUEST_JOIN_FAILED')); ?>',
    noAccessRequests: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_ACCESS_REQUESTS')); ?>',
    accessRequestApproved: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ACCESS_REQUEST_APPROVED')); ?>',
    accessRequestRejected: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ACCESS_REQUEST_REJECTED')); ?>',
    accessRequestActionFailed: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ACCESS_REQUEST_ACTION_FAILED')); ?>',
    relationshipLabel: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_RELATIONSHIP_LABEL')); ?>',
    ownerLabel: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_OWNER_LABEL')); ?>',
    statusLabel: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_STATUS_LABEL')); ?>',
    scopesLabel: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SCOPES')); ?>',
    signalAccessRequestReceived: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_ACCESS_REQUEST_RECEIVED')); ?>',
    signalAccessRequestApproved: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_ACCESS_REQUEST_APPROVED')); ?>',
    signalAccessRequestRejected: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_ACCESS_REQUEST_REJECTED')); ?>',
    signalInviteAccepted: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_INVITE_ACCEPTED')); ?>',
    signalInviteSent: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_INVITE_SENT')); ?>',
    signalInviteRevoked: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_INVITE_REVOKED')); ?>',
    signalAccessRevoked: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_ACCESS_REVOKED')); ?>',
    signalMemberLeftOrganization: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_MEMBER_LEFT_ORGANIZATION')); ?>',
    signalOwnershipTransferred: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_OWNERSHIP_TRANSFERRED')); ?>',
    signalSettingsUpdated: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_SETTINGS_UPDATED')); ?>',
    signalSiteLinked: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SIGNAL_SITE_LINKED')); ?>',
    membershipConsentTitle: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_MEMBERSHIP_CONSENT_TITLE')); ?>',
    membershipConsentIntro: '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_MEMBERSHIP_CONSENT_DESC')); ?>',
    membershipConsentAckRequired: 'You must acknowledge consent before continuing.',
    membershipConsentDefaultDisclaimer: 'Org shared encryption consent accepted.',
    orgDekBootstrapDone: 'Organization DEK bootstrap completed.',
    orgDekBootstrapFailed: 'Organization DEK bootstrap failed.',
  };

  const CURRENCY_LIST = <?php echo json_encode(Enums\Currency::toArray()); ?>;
  const TIMEZONE_LIST = <?php echo json_encode(Enums\Timezone::toArray()); ?>;
  const SERVER_TIMEZONE = '<?php echo addslashes(date_default_timezone_get()); ?>';

  const formatUtcOffset = (minutesEast) => {
    const sign = minutesEast >= 0 ? '+' : '-';
    const abs = Math.abs(minutesEast);
    const hours = String(Math.floor(abs / 60)).padStart(2, '0');
    const minutes = String(abs % 60).padStart(2, '0');
    return `${sign}${hours}:${minutes}`;
  };

  const timezoneOffsetMinutesEast = (zone, date) => {
    try {
      const dtf = new Intl.DateTimeFormat('en-US', {
        timeZone: zone,
        hour12: false,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
      const parts = dtf.formatToParts(date);
      const data = {};
      parts.forEach((part) => {
        if (part.type !== 'literal') data[part.type] = part.value;
      });
      const localAsUtc = Date.UTC(
        Number(data.year),
        Number(data.month) - 1,
        Number(data.day),
        Number(data.hour),
        Number(data.minute),
        Number(data.second)
      );
      return Math.round((localAsUtc - date.getTime()) / 60000);
    } catch {
      return 0;
    }
  };

  const timezoneAbbreviation = (zone, date) => {
    try {
      const dtf = new Intl.DateTimeFormat('en-US', {
        timeZone: zone,
        timeZoneName: 'short',
      });
      const parts = dtf.formatToParts(date);
      const tzName = String(parts.find((p) => p.type === 'timeZoneName')?.value || '').trim();
      if (tzName === '' || /^GMT[+-]|^UTC[+-]/i.test(tzName)) {
        return '';
      }
      return tzName.replace(/\s+/g, '');
    } catch {
      return '';
    }
  };

  const buildTimezoneMeta = () => {
    const now = new Date();
    const jan = new Date(now.getFullYear(), 0, 15, 12, 0, 0);
    const jul = new Date(now.getFullYear(), 6, 15, 12, 0, 0);

    return TIMEZONE_LIST.map((zone) => {
      const offsetNow = formatUtcOffset(timezoneOffsetMinutesEast(zone, now));
      const signedCompact = offsetNow.replace(':', '');
      const signedHours = String(parseInt(offsetNow.slice(0, 3), 10));
      const signedHourMinuteNoZero = `${signedHours}${offsetNow.slice(4, 6)}`;
      const abbrSet = new Set([
        timezoneAbbreviation(zone, now),
        timezoneAbbreviation(zone, jan),
        timezoneAbbreviation(zone, jul),
      ].filter(Boolean));
      const abbreviations = Array.from(abbrSet);
      const label = `${zone} [UTC${offsetNow}]${abbreviations.length ? ` ${abbreviations.join('/')}` : ''}`;
      const searchable = [
        zone.toLowerCase(),
        `utc${offsetNow}`.toLowerCase(),
        `utc${signedCompact}`.toLowerCase(),
        `utc${signedHours}`.toLowerCase(),
        offsetNow.toLowerCase(),
        signedCompact.toLowerCase(),
        signedHours.toLowerCase(),
        signedHourMinuteNoZero.toLowerCase(),
        ...abbreviations.map((abbr) => abbr.toLowerCase()),
      ].join(' ');

      return {
        zone,
        offsetNow,
        abbreviations,
        label,
        searchable,
      };
    });
  };

  const TIMEZONE_META = buildTimezoneMeta();
  const TIMEZONE_MAP = TIMEZONE_META.reduce((acc, item) => {
    acc[item.zone] = item;
    return acc;
  }, {});

  const FREQUENCY_LENGTHS = {
    weekly: '7',
    biweekly: '14',
    semimonthly: '15',
    monthly: '30',
  };

  const decodePossiblyEncodedText = (input) => {
    let text = String(input || '');
    if (text === '') {
      return '';
    }

    for (let i = 0; i < 4; i += 1) {
      const normalized = text.replace(/&(#\d+|#x[0-9a-fA-F]+|[a-zA-Z][a-zA-Z0-9]+)(?!;)/g, '&$1;');
      const parser = new DOMParser();
      const decodedDoc = parser.parseFromString(normalized, 'text/html');
      const decoded = String(decodedDoc.documentElement?.textContent || '');
      if (decoded === text) {
        return decoded;
      }
      text = decoded;
    }

    return text;
  };

  const PAY_PERIOD_CANONICAL_WEEKDAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const PAY_PERIOD_WEEKDAY_FULL_NAMES = <?php echo json_encode([
    org_js_index_i18n('WEEKDAY_SUNDAY'),
    org_js_index_i18n('WEEKDAY_MONDAY'),
    org_js_index_i18n('WEEKDAY_TUESDAY'),
    org_js_index_i18n('WEEKDAY_WEDNESDAY'),
    org_js_index_i18n('WEEKDAY_THURSDAY'),
    org_js_index_i18n('WEEKDAY_FRIDAY'),
    org_js_index_i18n('WEEKDAY_SATURDAY'),
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
  const PAY_PERIOD_DAY_NAMES = (() => {
    try {
      const locale = document.documentElement.lang || undefined;
      const formatter = new Intl.DateTimeFormat(locale, { weekday: 'short' });
      const sunday = new Date(Date.UTC(2026, 0, 4));

      return PAY_PERIOD_CANONICAL_WEEKDAY_NAMES.map((_, index) => formatter.format(new Date(sunday.getTime() + (index * 86400000))));
    } catch {
      return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    }
  })();
  const ACCESS_LOOKUP_MIN_CHARS = 2;
  const ACCESS_LOOKUP_DEBOUNCE_MS = 220;
  const ORG_BROWSER_SEARCH_DEBOUNCE_MS = 220;
  const ORG_BROWSER_RECENT_STORAGE_KEY = 'paycal.organizations.browser.recent.v1';
  const ORG_BROWSER_RECENT_MAX = 12;
  const PAY_PERIOD_WEEKDAY_MAP = {
    Sunday: 0,
    Monday: 1,
    Tuesday: 2,
    Wednesday: 3,
    Thursday: 4,
    Friday: 5,
    Saturday: 6,
  };

  const state = {
    organizations: [],
    currentRelationshipOrganizationId: '',
    selectedOrganizationId: '',
    inlineDeleteConfirmOrgId: '',
    inlineDeleteConfirmTimerId: null,
    personalAutoSaveTimerId: null,
    personalPreviewRafId: null,
    personalPreviewSignature: '',
    personalSaveInFlight: false,
    personalSavePendingSource: '',
    personalLastSavedSignature: '',
    personalPendingSignature: '',
    personalEditingGraceDaysValue: '0',
    editorAutoSaveTimerId: null,
    editorSaveInFlight: false,
    editorSavePendingSource: '',
    editorLastSavedSignature: '',
    editorHydrating: false,
    editorRiskBaseline: {
      type: '',
      role: '',
      status: '',
    },
    grid: {
      search: '',
      sort: 'name',
      direction: 'asc',
      page: '1',
    },
    searchDebounceId: null,
    browserSearchDebounceId: null,
    browserLastResults: [],
    browserRecent: [],
    auditRealtimeIntervalId: null,
    auditRealtimeTopEventId: '',
    auditRealtimeReady: false,
    auditGridManager: null,
    auditGridOrgId: '',
    freeAuditGridManager: null,
    freeAuditGridOrgId: '',
    liveRequestsIntervalId: null,
    liveRequestsReady: false,
    liveRequestsSignature: '',
    liveRequestsKnownIds: new Set(),
    notificationsIntervalId: null,
    notificationsSignature: '',
    discoveryIntervalId: null,
    discoverySignature: '',
    requestAccessLevel: 'readonly',
    inviteHistoryGridManager: null,
    inviteHistoryGridOrgId: '',
    membersGridManager: null,
    membersGridOrgId: '',
    membersGridRoleFilter: '',
    membersImport: {
      importId: '',
      challengeId: '',
      verified: false,
    },
    transferCandidates: [],
    transferSelectedUUID: '',
    contactImagePopoverTargetFieldId: '',
    customContactCards: [],
  };

  const EDITOR_SENSITIVE_FIELD_IDS = [
    'organizations_editor_type',
    'organizations_editor_role',
    'organizations_editor_status',
  ];

  const CONTACT_AVATAR_PLACEHOLDER_SRC = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22128%22 height=%22128%22 viewBox=%220 0 128 128%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23343a46%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23262c36%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22128%22 height=%22128%22 rx=%2264%22 fill=%22url(%23g)%22/%3E%3Ccircle cx=%2264%22 cy=%2248%22 r=%2222%22 fill=%22%238a95a8%22/%3E%3Cpath d=%22M24 110c4-21 20-33 40-33s36 12 40 33%22 fill=%22%238a95a8%22/%3E%3C/svg%3E';

  const normalizeContactImageDataUrl = (rawValue) => {
    const value = String(rawValue || '').trim();
    const match = value.match(/^data:image\/(png|jpe?g|webp|gif);base64,([a-z0-9+/=\s]+)$/i);
    if (!match) {
      return '';
    }

    const mime = String(match[1] || '').toLowerCase();
    const payload = String(match[2] || '').replace(/\s+/g, '');
    if (payload.length < 256 || payload.length > 19000) {
      return '';
    }

    // Quick decode sanity check to reject malformed values that still match regex.
    try {
      atob(payload);
    } catch (_error) {
      return '';
    }

    return `data:image/${mime};base64,${payload}`;
  };

  const getContactAvatarPreviewSrc = (rawValue) => {
    const normalized = normalizeContactImageDataUrl(rawValue);
    return normalized === '' ? CONTACT_AVATAR_PLACEHOLDER_SRC : normalized;
  };

  const elements = {
    searchForm: document.getElementById('organizations_search_form'),
    searchInput: document.getElementById('organizations_discovery_query'),
    requestJoinForm: document.getElementById('organizations_request_join_form'),
    requestOrgName: document.getElementById('organizations_request_org_name'),
    requestEmail: document.getElementById('organizations_request_email'),
    requestLookupDatalist: document.getElementById('organizations_access_lookup_request'),
    discoveryPanelStatus: document.getElementById('organizations_discovery_panel_status'),
    browserSearchForm: document.getElementById('organizations_browser_search_form'),
    browserSearchInput: document.getElementById('organizations_browser_search_input'),
    browserGrid: document.getElementById('organizations-browser-grid'),
    browserRecentGrid: document.getElementById('organizations-browser-recent-grid'),
    browserPanelStatus: document.getElementById('organizations_browser_panel_status'),
    browserGridStatus: document.getElementById('organizations_browser_grid_sr_status'),
    currentPanel: document.getElementById('organizations_current_panel'),
    currentSummary: document.getElementById('organizations_current_summary'),
    currentMeta: document.getElementById('organizations_current_meta'),
    currentStatus: document.getElementById('organizations_current_status'),
    currentInfoLink: document.getElementById('organizations_current_info_link'),
    currentRevokeButton: document.getElementById('organizations_current_revoke_button'),
    currentDetailsDialog: document.getElementById('organizations_current_details_dialog'),
    currentDetailsBody: document.getElementById('organizations_current_details_body'),
    membershipConsentDialog: document.getElementById('organizations_membership_consent_dialog'),
    membershipConsentForm: document.getElementById('organizations_membership_consent_form'),
    membershipConsentClose: document.getElementById('organizations_membership_consent_close'),
    membershipConsentCancel: document.getElementById('organizations_membership_consent_cancel'),
    membershipConsentAction: document.getElementById('organizations_membership_consent_action'),
    membershipConsentDisclaimer: document.getElementById('organizations_membership_consent_disclaimer'),
    membershipConsentAcknowledge: document.getElementById('organizations_membership_consent_ack'),
    membershipConsentError: document.getElementById('organizations_membership_consent_error'),
    freeAuditPanel: document.getElementById('organizations_free_audit_panel'),

    freeAuditStatus: document.getElementById('organizations_free_audit_sr_status'),
    freeAuditGridContainer: document.getElementById('organizations-free-audit-grid-host'),
    personalForm: document.getElementById('organizations_personal_form'),
    personalOrgId: document.getElementById('organizations_personal_org_id'),
    personalName: document.getElementById('organizations_personal_name'),
    personalPayFrequency: document.getElementById('organizations_personal_pay_frequency'),
    personalPayAnchor: document.getElementById('organizations_personal_pay_anchor'),
    personalPayPeriodStart: document.getElementById('organizations_personal_pay_period_start'),
    personalPayPeriodLength: document.getElementById('organizations_personal_pay_period_length'),
    personalEditingGraceDays: document.getElementById('organizations_personal_editing_grace_days'),
    personalEditingGraceDayRadios: document.querySelectorAll('input[name="organizations_personal_editing_grace_days"]'),
    personalDefaultWage: document.getElementById('organizations_personal_default_wage'),
    personalTimezone: document.getElementById('organizations_personal_timezone'),
    personalTimezoneSearch: document.getElementById('organizations_personal_timezone_search'),
    personalCurrency: document.getElementById('organizations_personal_currency'),
    personalCurrencySearch: document.getElementById('organizations_personal_currency_search'),
    personalPreview: document.getElementById('organizations_personal_preview'),
    accountActivityStatus: document.getElementById('account_activity_status'),
    accountActivityPanel: document.getElementById('panel-account-activity'),
    accountActivityLoginDetails: document.getElementById('account_activity_login_details'),
    accountActivityBrowserDetails: document.getElementById('account_activity_browser_details'),
    accountActivitySessions: document.getElementById('account_activity_sessions'),
    csrfToken: document.getElementById('organizations_csrf_token'),
    gridContainer: document.getElementById('organizations-grid'),
    gridBody: document.getElementById('organizations-grid')?.querySelector('.datagrid_body'),
    gridStatus: document.getElementById('organizations_grid_sr_status'),
    createButton: document.getElementById('organizations_create_button'),
    definitionsHelpButton: document.getElementById('organizations_definitions_help_button'),
    definitionsDialog: document.getElementById('organizations_definitions_dialog'),
    definitionsCloseButton: document.getElementById('organizations_definitions_close'),
    createDialog: document.getElementById('organizations_create_dialog'),
    createForm: document.getElementById('organizations_create_form'),
    createName: document.getElementById('organizations_create_name'),
    createNameError: document.getElementById('organizations_create_name_error'),
    createStatus: document.getElementById('organizations_create_status'),
    createSubmit: document.getElementById('organizations_create_submit'),
    dialog: document.getElementById('organizations_editor_dialog'),
    closeButton: document.getElementById('organizations_close_button'),
    bootstrapDekButton: document.getElementById('organizations_bootstrap_dek_button'),
    saveButton: document.getElementById('organizations_save_button'),
    title: document.getElementById('organizations_editor_title'),
    subtitle: document.getElementById('organizations_editor_subtitle'),
    premiumNotice: document.getElementById('organizations_editor_premium_notice'),
    orgId: document.getElementById('organizations_editor_org_id'),
    name: document.getElementById('organizations_editor_name'),
    type: document.getElementById('organizations_editor_type'),
    role: document.getElementById('organizations_editor_role'),
    status: document.getElementById('organizations_editor_status'),
    payFrequency: document.getElementById('organizations_editor_pay_frequency'),
    payAnchor: document.getElementById('organizations_editor_pay_anchor'),
    payPeriodStart: document.getElementById('organizations_editor_pay_period_start'),
    payPeriodLength: document.getElementById('organizations_editor_pay_period_length'),
    editingGraceDays: document.getElementById('organizations_editor_editing_grace_days'),
    editorEditingGraceDayRadios: document.querySelectorAll('input[name="organizations_editor_editing_grace_days"]'),
    payPeriodGridStatus: document.getElementById('organizations_editor_payperiod_sr_status'),
    defaultWage: document.getElementById('organizations_editor_default_wage'),
    timezone: document.getElementById('organizations_editor_timezone'),
    timezoneSearch: document.getElementById('organizations_editor_timezone_search'),
    currency: document.getElementById('organizations_editor_currency'),
    currencySearch: document.getElementById('organizations_editor_currency_search'),
    preview: document.getElementById('organizations_editor_preview'),
    ownerSummary: document.getElementById('organizations_owner_summary'),
    domainPolicyStatus: document.getElementById('organizations_editor_domain_policy_status'),
    enforceContactDomain: document.getElementById('organizations_editor_enforce_contact_domain'),
    allowedContactDomains: document.getElementById('organizations_editor_allowed_contact_domains'),
    contactCardAdd: document.getElementById('organizations_contact_card_add'),
    customCardsContainer: document.getElementById('organizations_contact_directory_custom_cards'),
    customCardsJson: document.getElementById('organizations_editor_contact_custom_json'),
    contactImagePopover: document.getElementById('organizations_contact_image_popover'),
    contactImageDropzone: document.getElementById('organizations_contact_image_dropzone'),
    contactImageFile: document.getElementById('organizations_contact_image_file'),
    contactImageClear: document.getElementById('organizations_contact_image_clear'),
    contactImageCancel: document.getElementById('organizations_contact_image_cancel'),
    inviteEmail: document.getElementById('organizations_invite_email'),
    inviteSend: document.getElementById('organizations_invite_send'),
    invitesReload: document.getElementById('organizations_invites_reload'),
    scopeGrid: document.getElementById('organizations_scope_grid'),
    scopeStatus: document.getElementById('organizations_scope_sr_status'),
    invitesStatus: document.getElementById('organizations_invites_sr_status'),
    invitesList: document.getElementById('organizations_invites_list'),
    membersInvitesList: document.getElementById('organizations_members_invites_list'),
    membersInviteHistoryGridContainer: document.getElementById('organizations-invite-history-grid-host'),
    accessRequestsStatus: document.getElementById('organizations_access_requests_sr_status'),
    accessRequestsList: document.getElementById('organizations_access_requests_list'),
    liveRequestsList: document.getElementById('organizations_live_requests_list'),
    liveRequestsStatus: document.getElementById('organizations_live_requests_sr_status'),
    membersRoleFilter: document.getElementById('organizations_members_role_filter'),
    membersGridContainer: document.getElementById('organizations-members-grid'),
    membersGridStatus: document.getElementById('organizations_members_grid_sr_status'),
    membersImportEmails: document.getElementById('organizations_members_import_emails'),
    membersImportPrepare: document.getElementById('organizations_members_import_prepare'),
    membersImportSendCode: document.getElementById('organizations_members_import_send_code'),
    membersImportCode: document.getElementById('organizations_members_import_code'),
    membersImportVerify: document.getElementById('organizations_members_import_verify'),
    membersImportCommit: document.getElementById('organizations_members_import_commit'),
    membersImportSummary: document.getElementById('organizations_members_import_summary'),
    membersImportStatus: document.getElementById('organizations_members_import_status'),
    discoveryRun: document.getElementById('organizations_discovery_run'),
    discoveryStatus: document.getElementById('organizations_discovery_sr_status'),
    discoveryResults: document.getElementById('organizations_discovery_results'),
    relationshipsReload: document.getElementById('organizations_relationships_reload'),
    transferTarget: document.getElementById('organizations_transfer_target'),
    transferTargetList: document.getElementById('organizations_transfer_target_list'),
    transferTargetUUID: document.getElementById('organizations_transfer_target_uuid'),
    transferSelectedMember: document.getElementById('organizations_transfer_selected_member'),
    transferConfirmationContainer: document.getElementById('organizations_transfer_confirmation_container'),
    transferConfirmation: document.getElementById('organizations_transfer_confirmation'),
    transferConfirmationStatus: document.getElementById('organizations_transfer_confirmation_status'),
    transferButton: document.getElementById('organizations_transfer_button'),
    transferNotice: document.getElementById('organizations_transfer_notice'),
    leaveButton: document.getElementById('organizations_leave_button'),
    auditReload: document.getElementById('organizations_audit_reload'),
    auditStatus: document.getElementById('organizations_audit_sr_status'),
    auditGridContainer: document.getElementById('organizations-audit-grid-host'),
    liveToast: document.getElementById('organizations_live_toast'),
    dialogLiveToast: document.getElementById('organizations_dialog_live_toast'),
  };

  let liveToastTimerId = null;
  const showOrganizationsToast = (message, type = 'save', durationMs = 2600, sticky = true) => {
    const text = String(message || '').trim();
    if (text === '') {
      return;
    }

    try {
      PC.showToast(text, type, durationMs, sticky);
    } catch {
      // Fall through to local toast region.
    }

    const toastTarget = (elements.dialog instanceof HTMLDialogElement
      && elements.dialog.open
      && elements.dialogLiveToast instanceof HTMLElement)
      ? elements.dialogLiveToast
      : elements.liveToast;

    if (!(toastTarget instanceof HTMLElement)) {
      return;
    }

    const toneClass = type === 'error'
      ? 'organizations_live_toast_error'
      : 'organizations_live_toast_save';

    toastTarget.classList.remove('organizations_live_toast_error', 'organizations_live_toast_save');
    toastTarget.classList.add(toneClass);
    toastTarget.textContent = text;
    toastTarget.classList.add('organizations_live_toast_show', 'organizations_live_toast_visible');
    if (liveToastTimerId !== null) {
      window.clearTimeout(liveToastTimerId);
    }

    liveToastTimerId = window.setTimeout(() => {
      if (elements.liveToast instanceof HTMLElement) {
        elements.liveToast.classList.remove('organizations_live_toast_show', 'organizations_live_toast_visible');
      }
      if (elements.dialogLiveToast instanceof HTMLElement) {
        elements.dialogLiveToast.classList.remove('organizations_live_toast_show', 'organizations_live_toast_visible');
      }
      liveToastTimerId = null;
    }, Math.max(1200, durationMs));
  };

  const EDITOR_META_FIELD_MAP = {
    organizations_editor_legal_name: 'legal_name',
    organizations_editor_industry: 'industry',
    organizations_editor_registration_number: 'registration_number',
    organizations_editor_tax_id: 'tax_id',
    organizations_editor_employee_count: 'employee_count',
    organizations_editor_founded_year: 'founded_year',
    organizations_editor_contact_email: 'contact_email',
    organizations_editor_contact_phone: 'contact_phone',
    organizations_editor_website: 'website',
    organizations_editor_address_line1: 'address_line1',
    organizations_editor_address_line2: 'address_line2',
    organizations_editor_address_city: 'address_city',
    organizations_editor_address_region: 'address_region',
    organizations_editor_address_postal: 'address_postal',
    organizations_editor_address_country: 'address_country',
    organizations_editor_support_hours: 'support_hours',
    organizations_editor_org_notes: 'org_notes',
    organizations_editor_enforce_contact_domain: 'enforce_contact_domain',
    organizations_editor_allowed_contact_domains: 'allowed_contact_domains',
    organizations_editor_contact_payroll_name: 'contact_payroll_name',
    organizations_editor_contact_payroll_image_url: 'contact_payroll_image_url',
    organizations_editor_contact_payroll_email: 'contact_payroll_email',
    organizations_editor_contact_payroll_phone: 'contact_payroll_phone',
    organizations_editor_contact_payroll_role: 'contact_payroll_role',
    organizations_editor_contact_hr_name: 'contact_hr_name',
    organizations_editor_contact_hr_image_url: 'contact_hr_image_url',
    organizations_editor_contact_hr_email: 'contact_hr_email',
    organizations_editor_contact_hr_phone: 'contact_hr_phone',
    organizations_editor_contact_hr_role: 'contact_hr_role',
    organizations_editor_contact_ceo_name: 'contact_ceo_name',
    organizations_editor_contact_ceo_image_url: 'contact_ceo_image_url',
    organizations_editor_contact_ceo_email: 'contact_ceo_email',
    organizations_editor_contact_ceo_phone: 'contact_ceo_phone',
    organizations_editor_contact_ceo_role: 'contact_ceo_role',
    organizations_editor_contact_coo_name: 'contact_coo_name',
    organizations_editor_contact_coo_image_url: 'contact_coo_image_url',
    organizations_editor_contact_coo_email: 'contact_coo_email',
    organizations_editor_contact_coo_phone: 'contact_coo_phone',
    organizations_editor_contact_coo_role: 'contact_coo_role',
    organizations_editor_contact_cto_name: 'contact_cto_name',
    organizations_editor_contact_cto_image_url: 'contact_cto_image_url',
    organizations_editor_contact_cto_email: 'contact_cto_email',
    organizations_editor_contact_cto_phone: 'contact_cto_phone',
    organizations_editor_contact_cto_role: 'contact_cto_role',
    organizations_editor_contact_support_name: 'contact_support_name',
    organizations_editor_contact_support_image_url: 'contact_support_image_url',
    organizations_editor_contact_support_email: 'contact_support_email',
    organizations_editor_contact_support_phone: 'contact_support_phone',
    organizations_editor_contact_support_role: 'contact_support_role',
    organizations_editor_contact_operations_name: 'contact_operations_name',
    organizations_editor_contact_operations_image_url: 'contact_operations_image_url',
    organizations_editor_contact_operations_email: 'contact_operations_email',
    organizations_editor_contact_operations_phone: 'contact_operations_phone',
    organizations_editor_contact_operations_role: 'contact_operations_role',
    organizations_editor_contact_manager_name: 'contact_manager_name',
    organizations_editor_contact_manager_image_url: 'contact_manager_image_url',
    organizations_editor_contact_manager_email: 'contact_manager_email',
    organizations_editor_contact_manager_phone: 'contact_manager_phone',
    organizations_editor_contact_manager_role: 'contact_manager_role',
    organizations_editor_contact_custom_json: 'contact_custom_json',
  };

  const escapeHtml = (value) => String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

  const uid = () => `cc_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;

  const normalizeCustomContactCards = (rawValue) => {
    let parsed = [];
    try {
      const maybe = JSON.parse(String(rawValue || '[]'));
      if (Array.isArray(maybe)) {
        parsed = maybe;
      }
    } catch {
      parsed = [];
    }

    return parsed
      .filter((item) => item && typeof item === 'object')
      .map((item) => ({
        id: String(item.id || uid()),
        name: String(item.name || ''),
        email: String(item.email || ''),
        phone: typeof PC.formatPhoneNumberValue === 'function'
          ? PC.formatPhoneNumberValue(String(item.phone || ''))
          : String(item.phone || ''),
        role: String(item.role || ''),
        image_url: String(item.image_url || ''),
      }));
  };

  const syncCustomCardsHiddenInput = () => {
    if (!(elements.customCardsJson instanceof HTMLInputElement)) {
      return;
    }
    elements.customCardsJson.value = JSON.stringify(state.customContactCards);
  };

  const renderCustomContactCards = () => {
    if (!(elements.customCardsContainer instanceof HTMLElement)) {
      return;
    }

    if (state.customContactCards.length === 0) {
      elements.customCardsContainer.innerHTML = '';
      return;
    }

    const markup = state.customContactCards.map((card) => {
      const previewId = `organizations_editor_contact_custom_${card.id}_avatar_preview`;
      const imageFieldId = `organizations_editor_contact_custom_${card.id}_image_url`;
      const previewSrc = getContactAvatarPreviewSrc(card.image_url);
      const isPlaceholderAvatar = previewSrc === CONTACT_AVATAR_PLACEHOLDER_SRC;
      return `
        <div class="organizations_contact_card organizations_contact_card_custom" data-custom-card-id="${escapeHtml(card.id)}">
          <img id="${escapeHtml(previewId)}" class="organizations_contact_card_avatar" src="${escapeHtml(previewSrc)}" alt="${isPlaceholderAvatar ? '' : '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT')); ?>'}"${isPlaceholderAvatar ? ' role="presentation"' : ''} loading="lazy">
          <input id="${escapeHtml(imageFieldId)}" class="organizations_contact_image_input" data-custom-field="image_url" data-custom-card-id="${escapeHtml(card.id)}" data-preview-id="${escapeHtml(previewId)}" type="hidden" maxlength="20000" value="${escapeHtml(card.image_url)}">
          <input class="organizations_contact_custom_input organizations_contact_body_input" name="name" autocomplete="name" data-custom-field="name" data-custom-card-id="${escapeHtml(card.id)}" type="text" maxlength="100" placeholder="<?php echo addslashes(org_js_index_i18n('NAME')); ?>" value="${escapeHtml(card.name)}">
          <input class="organizations_contact_custom_input organizations_contact_body_input" name="email" autocomplete="email" data-custom-field="email" data-custom-card-id="${escapeHtml(card.id)}" type="email" maxlength="160" placeholder="<?php echo addslashes(org_js_index_i18n('EMAIL')); ?>" value="${escapeHtml(card.email)}">
          <input class="organizations_contact_custom_input organizations_contact_body_input" name="phone" autocomplete="tel" data-custom-field="phone" data-custom-card-id="${escapeHtml(card.id)}" type="tel" inputmode="numeric" maxlength="14" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_PHONE_PLACEHOLDER')); ?>" value="${escapeHtml(typeof PC.formatPhoneNumberValue === 'function' ? PC.formatPhoneNumberValue(card.phone) : card.phone)}">
          <input class="organizations_contact_custom_input organizations_contact_role_input" data-custom-field="role" data-custom-card-id="${escapeHtml(card.id)}" type="text" maxlength="80" placeholder="<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH')); ?>" value="${escapeHtml(card.role)}">
          <div class="organizations_contact_card_menu">
            <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA')); ?>">...</button>
            <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="custom" data-custom-card-id="${escapeHtml(card.id)}" data-confirming="false" hidden><?php echo addslashes(org_js_index_i18n('REMOVE')); ?></button>
          </div>
        </div>
      `;
    }).join('');

    Guardian.setHTML(elements.customCardsContainer, markup);
  };

  const upsertCustomCardField = (cardId, fieldName, fieldValue) => {
    const idx = state.customContactCards.findIndex((card) => card.id === cardId);
    if (idx === -1) {
      return;
    }
    state.customContactCards[idx][fieldName] = String(fieldValue || '');
    syncCustomCardsHiddenInput();
  };

  const contactDeleteTimers = new WeakMap();

  const resetContactCardDeleteButton = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    const timerId = contactDeleteTimers.get(button);
    if (typeof timerId === 'number') {
      window.clearTimeout(timerId);
      contactDeleteTimers.delete(button);
    }

    button.dataset.confirming = 'false';
    button.classList.remove('is_confirming');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('REMOVE')); ?>';
  };

  const setContactCardMenuOpen = (menu, isOpen) => {
    if (!(menu instanceof HTMLElement)) {
      return;
    }

    const toggle = menu.querySelector('.organizations_contact_card_menu_toggle');
    const deleteButton = menu.querySelector('.organizations_contact_card_menu_delete');
    if (toggle instanceof HTMLButtonElement) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (deleteButton instanceof HTMLButtonElement) {
      deleteButton.hidden = !isOpen;
      if (!isOpen) {
        resetContactCardDeleteButton(deleteButton);
      }
    }
    menu.classList.toggle('is_open', isOpen);
  };

  const closeAllContactCardMenus = (exceptMenu = null) => {
    document.querySelectorAll('.organizations_contact_card_menu.is_open').forEach((menu) => {
      if (!(menu instanceof HTMLElement)) {
        return;
      }
      if (exceptMenu instanceof HTMLElement && menu === exceptMenu) {
        return;
      }
      setContactCardMenuOpen(menu, false);
    });
  };

  const armContactCardDeleteButton = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    button.dataset.confirming = 'true';
    button.classList.add('is_confirming');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('CONFIRM_DELETE')); ?>';

    const timerId = window.setTimeout(() => {
      resetContactCardDeleteButton(button);
    }, 3800);
    contactDeleteTimers.set(button, timerId);
  };

  const clearFixedContactCard = (card) => {
    if (!(card instanceof HTMLElement)) {
      return false;
    }

    let changed = false;
    card.querySelectorAll('input').forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      if (input.type === 'hidden') {
        if (input.classList.contains('organizations_contact_image_input') && String(input.value || '') !== '') {
          input.value = '';
          syncContactAvatarPreview(input);
          changed = true;
        }
        return;
      }

      if (String(input.value || '') !== '') {
        input.value = '';
        changed = true;
      }
    });

    return changed;
  };

  const handleContactCardDeleteAction = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    if (button.dataset.confirming !== 'true') {
      armContactCardDeleteButton(button);
      return;
    }

    resetContactCardDeleteButton(button);

    const cardType = String(button.dataset.cardType || 'fixed').trim();
    if (cardType === 'custom') {
      const cardId = String(button.dataset.customCardId || '').trim();
      if (cardId === '') {
        return;
      }

      state.customContactCards = state.customContactCards.filter((card) => card.id !== cardId);
      syncCustomCardsHiddenInput();
      renderCustomContactCards();
      scheduleEditorAutoSave(220, 'custom-contact-delete');
      showOrganizationsToast('Contact card deleted.', 'save', 2200, true);
      closeAllContactCardMenus();
      return;
    }

    const card = button.closest('.organizations_contact_card');
    if (!(card instanceof HTMLElement)) {
      return;
    }

    const changed = clearFixedContactCard(card);
    scheduleEditorAutoSave(220, 'fixed-contact-delete');
    showOrganizationsToast(changed ? 'Contact card cleared.' : 'Contact card is already empty.', 'save', 2200, true);
    const menu = button.closest('.organizations_contact_card_menu');
    if (menu instanceof HTMLElement) {
      setContactCardMenuOpen(menu, false);
    }
  };

  const closeContactImagePopover = () => {
    if (elements.contactImagePopover instanceof HTMLElement) {
      elements.contactImagePopover.classList.add('hidden');
      elements.contactImagePopover.style.top = '';
      elements.contactImagePopover.style.left = '';
    }
    state.contactImagePopoverTargetFieldId = '';
  };

  const openContactImagePopover = (targetField, anchorElement) => {
    if (!(elements.contactImagePopover instanceof HTMLElement)) {
      return;
    }
    state.contactImagePopoverTargetFieldId = targetField.id;

    const rect = anchorElement.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 220, rect.bottom + 8);
    const left = Math.min(window.innerWidth - 380, Math.max(8, rect.left - 12));
    elements.contactImagePopover.style.top = `${Math.max(8, top)}px`;
    elements.contactImagePopover.style.left = `${Math.max(8, left)}px`;
    elements.contactImagePopover.classList.remove('hidden');
  };

  const applyContactImageValue = (rawValue) => {
    const targetField = document.getElementById(state.contactImagePopoverTargetFieldId);
    if (!(targetField instanceof HTMLInputElement)) {
      return;
    }

    const nextValue = String(rawValue || '').trim();
    if (targetField.maxLength > 0 && nextValue.length > targetField.maxLength) {
      showOrganizationsToast(`Image value is too long (max ${targetField.maxLength} characters).`, 'error', 5000, true);
      return;
    }
    targetField.value = nextValue;
    syncContactAvatarPreview(targetField);

    const customCardId = String(targetField.dataset.customCardId || '');
    const customField = String(targetField.dataset.customField || '');
    if (customCardId !== '' && customField === 'image_url') {
      upsertCustomCardField(customCardId, 'image_url', nextValue);
      syncCustomCardsHiddenInput();
    }

    showOrganizationsToast('Saving contact image...', 'save', 1400, true);
    saveOrganizationEditorSettings('contact-image', false)
      .then((saved) => {
        if (!saved) {
          showOrganizationsToast('Contact image unchanged.', 'save', 2200, true);
        }
      })
      .catch((error) => PW.error(error));
    closeContactImagePopover();
  };

  const fileToCompactDataUrl = async (file) => {
    if (!(file instanceof File)) {
      return '';
    }

    const sourceDataUrl = await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('Image read failed'));
      reader.readAsDataURL(file);
    });

    if (sourceDataUrl === '') {
      return '';
    }

    const image = await new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error('Image load failed'));
      img.src = sourceDataUrl;
    });

    const canvas = document.createElement('canvas');
    const maxAllowedLength = 16000;
    const sizeCandidates = [96, 88, 80, 72, 64, 56];
    const qualityCandidates = [0.62, 0.55, 0.48, 0.42, 0.35];
    const ctx = canvas.getContext('2d');
    if (!ctx) {
      return '';
    }

    const srcW = image.width;
    const srcH = image.height;
    const crop = Math.min(srcW, srcH);
    const sx = Math.floor((srcW - crop) / 2);
    const sy = Math.floor((srcH - crop) / 2);

    let candidate = '';
    for (const size of sizeCandidates) {
      canvas.width = size;
      canvas.height = size;
      ctx.clearRect(0, 0, size, size);
      ctx.drawImage(image, sx, sy, crop, crop, 0, 0, size, size);

      for (const quality of qualityCandidates) {
        candidate = canvas.toDataURL('image/webp', quality);
        if (candidate.length <= maxAllowedLength) {
          return candidate;
        }
      }
    }

    return candidate;
  };

  const getImageFieldForAvatar = (avatar) => {
    if (!(avatar instanceof HTMLImageElement)) {
      return null;
    }

    const avatarId = String(avatar.id || '').trim();
    if (avatarId === '') {
      return null;
    }

    const field = document.querySelector(`.organizations_contact_image_input[data-preview-id="${CSS.escape(avatarId)}"]`);
    return field instanceof HTMLInputElement ? field : null;
  };

  const handleContactImageFiles = async (files) => {
    const first = files && files.length > 0 ? files[0] : null;
    if (!(first instanceof File)) {
      return;
    }

    try {
      const dataUrl = await fileToCompactDataUrl(first);
      applyContactImageValue(dataUrl);
    } catch (error) {
      PW.error(error);
      showOrganizationsToast('Could not process that image. Try a different file.', 'error', 5000, true);
    }
  };

  const syncContactAvatarPreview = (field) => {
    if (!(field instanceof HTMLInputElement) || !field.classList.contains('organizations_contact_image_input')) {
      return;
    }

    const previewId = String(field.dataset.previewId || '').trim();
    if (previewId === '') {
      return;
    }

    const preview = document.getElementById(previewId);
    if (!(preview instanceof HTMLImageElement)) {
      return;
    }

    const nextSrc = getContactAvatarPreviewSrc(field.value);
    preview.src = nextSrc;

    if (nextSrc === CONTACT_AVATAR_PLACEHOLDER_SRC) {
      preview.alt = '';
      preview.setAttribute('role', 'presentation');
    } else {
      preview.alt = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT')); ?>';
      preview.removeAttribute('role');
    }
  };

  const applyPhoneInputFormatting = (input) => {
    if (!(input instanceof HTMLInputElement)) {
      return;
    }

    input.type = 'tel';
    input.autocomplete = input.autocomplete || 'tel';
    input.inputMode = 'numeric';
    input.maxLength = 14;
    input.pattern = '\\([0-9]{3}\\) [0-9]{3}-[0-9]{4}';
    if (input.placeholder === '' || input.placeholder === 'Phone' || input.placeholder === '123-456-7890') {
      input.placeholder = '(123) 456-7890';
    }
    PC.formatPhoneNumber(input);
  };

  const formatPhoneInputsWithin = (root = document) => {
    if (!(root instanceof Document || root instanceof HTMLElement || root instanceof HTMLDialogElement)) {
      return;
    }

    root.querySelectorAll('input[id$="_phone"], .organizations_contact_custom_input[data-custom-field="phone"]').forEach((field) => {
      if (field instanceof HTMLInputElement) {
        applyPhoneInputFormatting(field);
      }
    });
  };

  const EDITOR_AUTOSAVE_SOURCE_IDS = [
    'organizations_editor_name',
    'organizations_editor_type',
    'organizations_editor_role',
    'organizations_editor_status',
    'organizations_editor_pay_frequency',
    'organizations_editor_default_wage',
    'organizations_editor_timezone',
    'organizations_editor_currency',
    'organizations_editor_enforce_contact_domain',
    ...Object.keys(EDITOR_META_FIELD_MAP),
  ];

  const getEditorFieldValueById = (fieldId) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      if (field instanceof HTMLInputElement && field.type === 'checkbox') {
        return field.checked ? '1' : '0';
      }
      return String(field.value || '').trim();
    }
    return '';
  };

  const setEditorFieldValueById = (fieldId, value) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      if (field instanceof HTMLInputElement && field.type === 'checkbox') {
        const normalized = String(value || '').trim().toLowerCase();
        field.checked = normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on';
      } else {
      field.value = String(value || '');
      }
      if (field instanceof HTMLInputElement) {
        if (fieldId.endsWith('_phone')) {
          applyPhoneInputFormatting(field);
        }
        syncContactAvatarPreview(field);
      }
    }

    if (fieldId === 'organizations_editor_contact_custom_json') {
      state.customContactCards = normalizeCustomContactCards(value);
      syncCustomCardsHiddenInput();
      renderCustomContactCards();
    }
  };

  const updateDomainPolicyStatus = () => {
    if (!(elements.domainPolicyStatus instanceof HTMLElement)) {
      return;
    }

    const enforceEnabled = elements.enforceContactDomain instanceof HTMLInputElement
      ? elements.enforceContactDomain.checked
      : false;
    const raw = elements.allowedContactDomains instanceof HTMLInputElement
      ? String(elements.allowedContactDomains.value || '')
      : '';

    const domains = raw
      .split(/[\s,;]+/)
      .map((item) => item.trim().toLowerCase())
      .filter((item, index, arr) => item !== '' && arr.indexOf(item) === index);

    if (!enforceEnabled) {
      elements.domainPolicyStatus.textContent = 'Contact domain enforcement is off.';
      return;
    }

    if (domains.length === 0) {
      elements.domainPolicyStatus.textContent = 'Contact domain enforcement is on, but no allowed domains are configured.';
      return;
    }

    elements.domainPolicyStatus.textContent = `Contact domain enforcement is on for ${domains.length} allowed domain${domains.length === 1 ? '' : 's'}.`;
  };

  const getEditorSensitiveValue = (fieldId) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      return String(field.value || '').trim().toLowerCase();
    }

    return '';
  };

  const getKnownTransferMemberCount = () => {
    return Array.isArray(state.transferCandidates) ? state.transferCandidates.length : 0;
  };

  const normalizeTransferLookupName = (value) => String(value || '').trim().toLowerCase();

  const deriveTransferCandidateDisplay = (member) => {
    const name = decodePossiblyEncodedText(String(member?.full_name || '').trim());
    const fallbackEmail = String(member?.email || '').trim();
    if (name !== '') {
      return name;
    }

    return fallbackEmail;
  };

  const getTransferCandidateByUUID = (userUUID) => {
    const target = String(userUUID || '').trim();
    if (target === '') {
      return null;
    }

    return state.transferCandidates.find((candidate) => candidate.userUUID === target) || null;
  };

  const renderTransferSelectedMember = () => {
    if (!(elements.transferSelectedMember instanceof HTMLElement)) {
      return;
    }

    const candidate = getTransferCandidateByUUID(state.transferSelectedUUID);
    if (!candidate) {
      elements.transferSelectedMember.classList.add('organizations_empty');
      elements.transferSelectedMember.textContent = '';
      return;
    }

    const metaParts = [candidate.email, candidate.roleLabel, candidate.statusLabel].filter((part) => String(part || '').trim() !== '');
    Guardian.setHTML(elements.transferSelectedMember, `
      <div class="organizations_transfer_selected_member_row">
        <div class="organizations_transfer_selected_member_text">
          <strong>${escapeHtml(candidate.displayName)}</strong>
          <span>${escapeHtml(metaParts.join(' | '))}</span>
        </div>
        <button type="button" class="btn btn_secondary organizations_transfer_selected_member_clear" data-transfer-selection-action="deselect">Deselect</button>
      </div>
    `);
    elements.transferSelectedMember.classList.remove('organizations_empty');
  };

  const setTransferInputLocked = (locked) => {
    if (!(elements.transferTarget instanceof HTMLInputElement)) {
      return;
    }

    const canTransfer = !(elements.transferButton instanceof HTMLButtonElement) || !elements.transferButton.disabled;
    elements.transferTarget.disabled = locked || !canTransfer;
  };

  const setTransferButtonVisible = (visible) => {
    if (!(elements.transferButton instanceof HTMLButtonElement)) {
      return;
    }

    if (visible) {
      elements.transferButton.hidden = false;
      elements.transferButton.removeAttribute('hidden');
      return;
    }

    elements.transferButton.hidden = true;
    elements.transferButton.setAttribute('hidden', 'hidden');
  };

  const clearTransferSelection = (clearInput = true) => {
    state.transferSelectedUUID = '';
    if (elements.transferTargetUUID instanceof HTMLInputElement) {
      elements.transferTargetUUID.value = '';
    }
    if (clearInput && elements.transferTarget instanceof HTMLInputElement) {
      elements.transferTarget.value = '';
    }
    if (elements.transferConfirmation instanceof HTMLInputElement) {
      elements.transferConfirmation.value = '';
    }
    if (elements.transferConfirmationContainer instanceof HTMLElement) {
      elements.transferConfirmationContainer.classList.add('organizations_empty');
    }
    setTransferButtonVisible(false);
    setTransferInputLocked(false);
    renderTransferSelectedMember();
    syncTransferConfirmation();
  };

  const applyTransferSelection = (candidate, announce = true) => {
    if (!candidate) {
      clearTransferSelection(false);
      return;
    }

    state.transferSelectedUUID = candidate.userUUID;
    if (elements.transferTarget instanceof HTMLInputElement) {
      elements.transferTarget.value = candidate.displayName;
    }
    if (elements.transferTargetUUID instanceof HTMLInputElement) {
      elements.transferTargetUUID.value = candidate.userUUID;
    }
    if (elements.transferConfirmation instanceof HTMLInputElement) {
      elements.transferConfirmation.value = '';
    }
    if (elements.transferConfirmationContainer instanceof HTMLElement) {
      elements.transferConfirmationContainer.classList.remove('organizations_empty');
    }
    setTransferButtonVisible(false);
    setTransferInputLocked(true);
    renderTransferSelectedMember();
    syncTransferConfirmation();

    if (announce) {
      showOrganizationsToast(`Member ${candidate.displayName} chosen.`, 'save', 3200, true);
    }
  };

  const syncTransferTargetFromLookup = () => {
    const field = elements.transferTarget;
    if (!(field instanceof HTMLInputElement)) {
      return;
    }

    const lookupValue = normalizeTransferLookupName(field.value);
    if (!(elements.transferTargetUUID instanceof HTMLInputElement)) {
      return;
    }

    if (lookupValue === '') {
      elements.transferTargetUUID.value = '';
      return;
    }

    const matches = state.transferCandidates.filter((candidate) => candidate.lookupKey === lookupValue);
    if (matches.length === 1) {
      applyTransferSelection(matches[0], true);
      return;
    }

    if (state.transferSelectedUUID === '') {
      elements.transferTargetUUID.value = '';
    }
  };

  const syncTransferConfirmation = () => {
    const confirmInput = elements.transferConfirmation;
    if (!(confirmInput instanceof HTMLInputElement)) {
      return;
    }

    const expectedPhrase = 'TRANSFER ORGANIZATION';
    const rawTyped = String(confirmInput.value || '');
    const uppercaseTyped = rawTyped.toUpperCase();
    if (rawTyped !== uppercaseTyped) {
      confirmInput.value = uppercaseTyped;
    }
    const normalizedTyped = uppercaseTyped
      .replace(/[^A-Z\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
    const isMatch = normalizedTyped === expectedPhrase;
    const hasSelection = state.transferSelectedUUID !== '';

    if (elements.transferConfirmationStatus instanceof HTMLElement) {
      if (!hasSelection) {
        elements.transferConfirmationStatus.textContent = '';
      } else if (rawTyped.trim() === '') {
        elements.transferConfirmationStatus.textContent = 'Type "TRANSFER ORGANIZATION" to reveal the transfer button.';
      } else if (isMatch) {
        elements.transferConfirmationStatus.textContent = 'Confirmation accepted. You can transfer ownership now.';
      } else {
        elements.transferConfirmationStatus.textContent = 'Confirmation must match "TRANSFER ORGANIZATION".';
      }
    }

    setTransferButtonVisible(hasSelection && isMatch);
  };

  const syncEditorRiskBaselineFromInputs = () => {
    state.editorRiskBaseline.type = getEditorSensitiveValue('organizations_editor_type');
    state.editorRiskBaseline.role = getEditorSensitiveValue('organizations_editor_role');
    state.editorRiskBaseline.status = getEditorSensitiveValue('organizations_editor_status');
  };

  const setEditorSensitiveFieldValue = (fieldId, nextValue) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      field.value = String(nextValue || '');
    }
  };

  const promptSensitiveEditorTransition = (fieldId, previousValue, nextValue) => {
    const organization = getSelectedOrganization();
    const orgName = decodePossiblyEncodedText(String(organization?.name || 'this organization'));

    if (fieldId === 'organizations_editor_type') {
      if (
        previousValue === 'shared'
        && nextValue === 'personal'
        && hasActivePremiumSubscription
        && organization
        && isOrganizationOwner(organization)
      ) {
        const memberCount = getKnownTransferMemberCount();
        const memberWarning = memberCount > 0
          ? ` There ${memberCount === 1 ? 'is 1 active linked member' : `are ${memberCount} active linked members`} in the transfer list.`
          : '';
        return window.confirm(
          `Change Type for ${orgName} from shared to personal? This can sever collaboration and member relationship paths.${memberWarning}\n\nPress OK to proceed or Cancel to keep shared.`
        );
      }

      return window.confirm(
        `Change Type for ${orgName} from ${previousValue || 'unknown'} to ${nextValue || 'unknown'}? This can affect how access and membership behave.`
      );
    }

    if (fieldId === 'organizations_editor_role') {
      return window.confirm(
        `Change your role from ${previousValue || 'unknown'} to ${nextValue || 'unknown'}? This may remove organization capabilities (settings, invites, or access controls).`
      );
    }

    if (fieldId === 'organizations_editor_status') {
      if (previousValue === 'active' && nextValue === 'pending') {
        return window.confirm(
          `Move status from active to pending? Pending status can interrupt active organization workflows and access expectations.`
        );
      }

      return window.confirm(
        `Change status from ${previousValue || 'unknown'} to ${nextValue || 'unknown'}? Status changes can affect organization access and workflow behavior.`
      );
    }

    return true;
  };

  const guardSensitiveEditorFieldChange = (fieldId) => {
    if (state.editorHydrating || !EDITOR_SENSITIVE_FIELD_IDS.includes(fieldId)) {
      return true;
    }

    const baselineKey = fieldId === 'organizations_editor_type'
      ? 'type'
      : fieldId === 'organizations_editor_role'
        ? 'role'
        : 'status';

    const previousValue = String(state.editorRiskBaseline[baselineKey] || '').trim().toLowerCase();
    const nextValue = getEditorSensitiveValue(fieldId);

    if (previousValue === '' || nextValue === '' || previousValue === nextValue) {
      return true;
    }

    const confirmed = promptSensitiveEditorTransition(fieldId, previousValue, nextValue);
    if (!confirmed) {
      setEditorSensitiveFieldValue(fieldId, previousValue);
      if (state.editorAutoSaveTimerId !== null) {
        window.clearTimeout(state.editorAutoSaveTimerId);
        state.editorAutoSaveTimerId = null;
      }
      showOrganizationsToast('Change canceled. Previous value restored.', 'error', 4200, true);
      return false;
    }

    return true;
  };

  const collectOrganizationEditorPayload = () => {
    syncCustomCardsHiddenInput();

    const payload = {
      name: elements.name instanceof HTMLInputElement ? decodePossiblyEncodedText(elements.name.value).trim() : '',
      organization_type: (elements.type instanceof HTMLSelectElement || elements.type instanceof HTMLInputElement)
        ? String(elements.type.value || '').trim().toLowerCase()
        : '',
      role: (elements.role instanceof HTMLSelectElement || elements.role instanceof HTMLInputElement)
        ? String(elements.role.value || '').trim().toLowerCase()
        : '',
      status: (elements.status instanceof HTMLSelectElement || elements.status instanceof HTMLInputElement)
        ? String(elements.status.value || '').trim().toLowerCase()
        : '',
      pay_frequency: elements.payFrequency instanceof HTMLSelectElement ? elements.payFrequency.value : 'biweekly',
      pay_anchor: getEditorPayAnchor(),
      pay_period_start: elements.payPeriodStart instanceof HTMLInputElement ? elements.payPeriodStart.value : '',
      pay_period_length: elements.payPeriodLength instanceof HTMLInputElement ? elements.payPeriodLength.value : FREQUENCY_LENGTHS.biweekly,
      editing_grace_days: getEditorEditingGraceDays(),
      default_wage: elements.defaultWage instanceof HTMLInputElement ? elements.defaultWage.value.trim() : '',
      timezone: elements.timezone instanceof HTMLInputElement ? elements.timezone.value.trim() : '',
      currency: elements.currency instanceof HTMLInputElement ? elements.currency.value.trim() : '',
    };

    Object.entries(EDITOR_META_FIELD_MAP).forEach(([fieldId, payloadKey]) => {
      payload[payloadKey] = getEditorFieldValueById(fieldId);
    });

    return payload;
  };

  const buildEditorPayloadSignature = (payload) => {
    return Object.keys(payload)
      .sort()
      .map((key) => `${key}:${String(payload[key] ?? '')}`)
      .join('|');
  };

  const saveOrganizationEditorSettings = async (source = 'manual', refreshAfterSave = false) => {
    if (state.selectedOrganizationId === '' || state.editorHydrating) {
      return false;
    }

    const payload = collectOrganizationEditorPayload();
    const signature = buildEditorPayloadSignature(payload);
    if (signature === state.editorLastSavedSignature) {
      return false;
    }

    if (state.editorSaveInFlight) {
      state.editorSavePendingSource = source;
      return false;
    }

    state.editorSaveInFlight = true;
    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/settings/update`, payload);
      state.editorLastSavedSignature = signature;
      syncEditorRiskBaselineFromInputs();
      let toastMessage = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_AUTO_SAVE_DETAILS')); ?>';
      if (source === 'manual') {
        toastMessage = T.defaultsSaved;
      } else if (source === 'contact-image') {
        toastMessage = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_SAVED')); ?>';
      } else if (source.startsWith('custom-contact')) {
        toastMessage = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_CARD_SAVED')); ?>';
      }
      showOrganizationsToast(toastMessage, 'save', source === 'manual' ? 5000 : 2600, true);
      if (refreshAfterSave) {
        await refreshIndex(state.selectedOrganizationId, true);
      }
      return true;
    } catch (error) {
      PW.error(error);
      showOrganizationsToast(error instanceof Error && error.message ? error.message : T.defaultsSaveFailed, 'error', 7000, true);
      return false;
    } finally {
      state.editorSaveInFlight = false;
      if (state.editorSavePendingSource !== '') {
        const pending = state.editorSavePendingSource;
        state.editorSavePendingSource = '';
        saveOrganizationEditorSettings(pending, false).catch((error) => PW.error(error));
      }
    }
  };

  const scheduleEditorAutoSave = (delayMs = 600, source = 'auto') => {
    if (state.editorHydrating) {
      return;
    }

    if (state.editorAutoSaveTimerId !== null) {
      window.clearTimeout(state.editorAutoSaveTimerId);
    }

    state.editorAutoSaveTimerId = window.setTimeout(() => {
      saveOrganizationEditorSettings(source, false).catch((error) => PW.error(error));
    }, delayMs);
  };

  const isPersonalOrganization = (organization) => String(organization?.organization_type || '').toLowerCase() === T.personal;

  const isOrganizationOwner = (organization) => {
    const ownerUUID = String(organization?.owner_uuid || '');
    const role = String(organization?.role || '').toLowerCase();

    return ownerUUID === currentUserUUID || role === T.owner;
  };

  const canEditOwnRoleInEditor = (organization) => {
    const role = String(organization?.role || '').trim().toLowerCase();
    return role !== 'owner' && role !== 'coordinator';
  };

  const canUsePremiumOrgFeatures = (organization) => {
    if (isElevatedStaffUser) {
      return true;
    }

    if (hasActivePremiumSubscription) {
      return true;
    }

    if (!organization || typeof organization !== 'object') {
      return false;
    }

    if (isOrganizationOwner(organization) || isPersonalOrganization(organization)) {
      return true;
    }

    return false;
  };

  const ACCESS_MANAGE_WARNING = String(T.memberAccessManageDenied || T.manageAccessUnavailable || '').trim();

  const getOrganizationScopes = (organization) => {
    if (!Array.isArray(organization?.scopes)) {
      return [];
    }

    return organization.scopes
      .map((scope) => String(scope || '').trim().toLowerCase())
      .filter((scope) => scope !== '');
  };

  const canManageOrganizationAccess = (organization) => {
    if (!organization || typeof organization !== 'object') {
      return false;
    }

    if (isOrganizationOwner(organization)) {
      return true;
    }

    const relationshipStatus = String(organization.relationship_status || organization.status || '').trim().toLowerCase();
    if (relationshipStatus !== 'active') {
      return false;
    }

    const role = String(organization.role || '').trim().toLowerCase();
    if (role === 'owner' || role === 'coordinator') {
      return true;
    }

    const scopeSet = new Set(getOrganizationScopes(organization));
    return scopeSet.has('access.manage') || scopeSet.has('org.settings.write');
  };

  const canManageSelectedOrganizationAccess = () => {
    const organization = getSelectedOrganization();
    if (!organization) {
      return false;
    }

    return canManageOrganizationAccess(organization);
  };

  const showAccessManagementDeniedWarning = (message = ACCESS_MANAGE_WARNING) => {
    const warning = String(message || ACCESS_MANAGE_WARNING);
    setCurrentOrganizationStatus(warning);
    announceInvitesStatus(warning);
    announceAccessRequestsStatus(warning);
    if (elements.membersGridStatus instanceof HTMLElement) {
      elements.membersGridStatus.textContent = warning;
    }
    const membersInviteStatus = document.getElementById('organizations_members_invite_status');
    if (membersInviteStatus instanceof HTMLElement) {
      membersInviteStatus.textContent = warning;
      membersInviteStatus.classList.remove('success');
      membersInviteStatus.classList.add('error', 'is-visible');
    }
    const membersRequestsStatus = document.getElementById('organizations_access_requests_sr_status');
    if (membersRequestsStatus instanceof HTMLElement) {
      membersRequestsStatus.textContent = warning;
    }
  };

  const getSelectedOrganization = () => findOrganization(state.selectedOrganizationId);

  const isSelectedOrganizationPremiumLocked = () => {
    const organization = getSelectedOrganization();
    if (!organization) {
      return false;
    }

    return !canUsePremiumOrgFeatures(organization);
  };

  const updatePremiumNotice = (organization) => {
    if (!elements.premiumNotice) {
      return;
    }

    if (!organization) {
      elements.premiumNotice.textContent = (isElevatedStaffUser || hasActivePremiumSubscription)
        ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREMIUM_NOTICE_SELECT')); ?>'
        : `${T.premiumAdminLocked} ${T.selfOrgWip}`;
      return;
    }

    if (!canUsePremiumOrgFeatures(organization)) {
      elements.premiumNotice.textContent = T.premiumAdminLockedDetailed;
      return;
    }

    const type = isPersonalOrganization(organization)
      ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREMIUM_NOTICE_SELF_SELECTED')); ?>'
      : '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREMIUM_NOTICE_SHARED_SELECTED')); ?>';
    elements.premiumNotice.textContent = `${type} ${T.selfOrgWip}`;
  };

  const setPremiumLockedState = (organization) => {
    const isLocked = !!organization && !canUsePremiumOrgFeatures(organization);
    const roleLocked = !!organization && !canEditOwnRoleInEditor(organization);
    const accessLocked = !!organization && !canManageOrganizationAccess(organization);

    if (elements.saveButton instanceof HTMLButtonElement) {
      elements.saveButton.disabled = isLocked;
    }
    if (elements.bootstrapDekButton instanceof HTMLButtonElement) {
      elements.bootstrapDekButton.disabled = isLocked;
    }
    if (elements.role instanceof HTMLSelectElement || elements.role instanceof HTMLInputElement) {
      elements.role.disabled = isLocked || roleLocked;
      elements.role.classList.toggle('organizations_field_locked', isLocked || roleLocked);
      if (isLocked || roleLocked) {
        elements.role.setAttribute('aria-disabled', 'true');
      } else {
        elements.role.removeAttribute('aria-disabled');
      }
    }
    if (elements.inviteSend instanceof HTMLButtonElement) {
      elements.inviteSend.disabled = isLocked || accessLocked;
    }
    if (elements.invitesReload instanceof HTMLButtonElement) {
      elements.invitesReload.disabled = isLocked || accessLocked;
    }
    if (elements.discoveryRun instanceof HTMLButtonElement) {
      elements.discoveryRun.disabled = isLocked;
    }
    if (elements.relationshipsReload instanceof HTMLButtonElement) {
      elements.relationshipsReload.disabled = isLocked || accessLocked;
    }
    if (elements.leaveButton instanceof HTMLButtonElement) {
      if (isLocked) {
        elements.leaveButton.disabled = true;
      }
    }
    if (elements.scopeGrid instanceof HTMLFieldSetElement) {
      elements.scopeGrid.disabled = isLocked;
    }
    if (elements.inviteEmail instanceof HTMLInputElement) {
      elements.inviteEmail.disabled = isLocked;
    }
    if (elements.membersImportEmails instanceof HTMLTextAreaElement) {
      elements.membersImportEmails.disabled = isLocked || accessLocked;
    }
    if (elements.membersImportPrepare instanceof HTMLButtonElement) {
      elements.membersImportPrepare.disabled = isLocked || accessLocked;
    }
    if (elements.membersImportSendCode instanceof HTMLButtonElement) {
      elements.membersImportSendCode.disabled = isLocked || accessLocked || state.membersImport.importId === '';
    }
    if (elements.membersImportCode instanceof HTMLInputElement) {
      elements.membersImportCode.disabled = isLocked || accessLocked || state.membersImport.challengeId === '';
    }
    if (elements.membersImportVerify instanceof HTMLButtonElement) {
      elements.membersImportVerify.disabled = isLocked || accessLocked || state.membersImport.challengeId === '';
    }
    if (elements.membersImportCommit instanceof HTMLButtonElement) {
      elements.membersImportCommit.disabled = isLocked || accessLocked || !state.membersImport.verified;
    }
    if (elements.membersRoleFilter instanceof HTMLSelectElement) {
      elements.membersRoleFilter.disabled = accessLocked;
    }

    updatePremiumNotice(organization);
  };

  const blockPremiumActionWhenLocked = () => {
    if (!isSelectedOrganizationPremiumLocked()) {
      return false;
    }

    PC.showToast(T.premiumAdminLockedDetailed, 'error', 9000, true);
    return true;
  };

  const blockAccessManagementActionWhenLocked = () => {
    if (canManageSelectedOrganizationAccess()) {
      return false;
    }

    showAccessManagementDeniedWarning();
    PC.showToast(ACCESS_MANAGE_WARNING, 'error', 9000, true);
    return true;
  };

  const getCsrfToken = () => {
    if (!(elements.csrfToken instanceof HTMLInputElement)) {
      return '';
    }

    return String(elements.csrfToken.value || '');
  };

  const buildHeaders = (extra = {}) => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...extra,
  });

  const extractPayloadData = (payload) => {
    if (payload && typeof payload === 'object') {
      const { status, message, _lens, ...data } = payload;
      return data;
    }

    return {};
  };

  const buildApiError = (message, status = 0, data = {}) => {
    const error = new Error(message);
    error.status = Number(status || 0);
    error.data = data && typeof data === 'object' ? data : {};
    return error;
  };

  const apiRequest = async (url, options = {}) => {
    const { timeoutMs: customTimeoutMs, ...fetchOptions } = options;
    const timeoutMs = Number.isFinite(customTimeoutMs)
      ? Math.max(1000, Number(customTimeoutMs))
      : 30000;
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);

    const mergedSignal = fetchOptions.signal || controller.signal;

    let response;
    try {
      response = await fetch(url, {
        credentials: 'same-origin',
        headers: buildHeaders(fetchOptions.headers || {}),
        signal: mergedSignal,
        ...fetchOptions,
      });
    } catch (error) {
      window.clearTimeout(timeoutId);
      if (error instanceof DOMException && error.name === 'AbortError') {
        throw new Error('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REQUEST_TIMEOUT')); ?>');
      }
      throw new Error('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REQUEST_NETWORK_FAILED')); ?>');
    }

    window.clearTimeout(timeoutId);

    const raw = await response.text();
    let payload = null;
    if (raw.trim() !== '') {
      try {
        payload = JSON.parse(raw);
      } catch (_error) {
        if (!response.ok) {
          throw new Error(`Request failed (${response.status}).`);
        }
        payload = {};
      }
    }

    if (!response.ok) {
      const message = payload && typeof payload === 'object' && 'message' in payload
        ? String(payload.message || 'Request failed.')
        : `Request failed (${response.status}).`;
      const data = payload && typeof payload === 'object' && 'data' in payload && payload.data && typeof payload.data === 'object'
        ? payload.data
        : {};
      throw buildApiError(message, response.status, data);
    }

    if (payload && typeof payload === 'object' && 'status' in payload && payload.status !== 'success') {
      const data = payload && typeof payload === 'object' && 'data' in payload && payload.data && typeof payload.data === 'object'
        ? payload.data
        : {};
      throw buildApiError(String(payload.message || 'Request failed.'), response.status, data);
    }

    return extractPayloadData(payload || {});
  };

  const postForm = async (url, values, requestOptions = {}) => {
    const body = new URLSearchParams();

    Object.entries(values).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        value.forEach((item) => body.append(`${key}[]`, String(item)));
        return;
      }

      if (value === null || typeof value === 'undefined') {
        return;
      }

      body.set(key, String(value));
    });

    const csrfToken = getCsrfToken();
    if (csrfToken !== '') {
      body.set('csrf_token', csrfToken);
    }

    return apiRequest(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: body.toString(),
      ...requestOptions,
    });
  };

  const getSelectedInviteScopes = () => {
    return Array.from(document.querySelectorAll('#organizations_scope_grid .organizations_scope:checked'))
      .map((input) => input instanceof HTMLInputElement ? input.value.trim() : '')
      .filter((value) => value !== '');
  };

  const announceScopeSelectionStatus = (reason = 'updated') => {
    if (!elements.scopeStatus) {
      return;
    }

    const count = getSelectedInviteScopes().length;
    if (count === 0) {
      elements.scopeStatus.textContent = reason === 'required'
        ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SCOPE_REQUIRED')); ?>'
        : '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SCOPE_NONE_SELECTED')); ?>';
      return;
    }

    elements.scopeStatus.textContent = `Invite scopes ${reason}. ${count} scope${count === 1 ? '' : 's'} selected.`;
  };

  const announceInvitesStatus = (message) => {
    if (elements.invitesStatus) {
      elements.invitesStatus.textContent = message;
    }
  };

  const announceAccessRequestsStatus = (message) => {
    if (elements.accessRequestsStatus) {
      elements.accessRequestsStatus.textContent = message;
    }
  };

  const announceLiveRequestsStatus = (message) => {
    if (elements.liveRequestsStatus) {
      elements.liveRequestsStatus.textContent = String(message || '');
    }
  };

  const setLiveRequestsNotificationState = (pendingCount) => {
    const panel = document.getElementById('organizations-live-requests-panel');
    const title = document.getElementById('organizations_live_requests_title');
    if (!(panel instanceof HTMLElement) || !(title instanceof HTMLElement)) {
      return;
    }

    const count = Math.max(0, Number(pendingCount || 0));
    panel.toggleAttribute('data-has-pending-requests', count > 0);

    const existingDot = title.querySelector('.organizations_live_requests_dot');
    if (count <= 0) {
      existingDot?.remove();
      return;
    }

    if (existingDot instanceof HTMLElement) {
      existingDot.setAttribute('aria-label', count > 99
        ? '99 plus pending live requests'
        : `${String(count)} pending live request${count === 1 ? '' : 's'}`
      );
      return;
    }

    const dot = document.createElement('span');
    dot.className = 'organizations_live_requests_dot';
    dot.setAttribute('aria-hidden', 'true');
    dot.title = count > 99
      ? '99+ pending live requests'
      : `${String(count)} pending live request${count === 1 ? '' : 's'}`;
    title.appendChild(dot);
  };

  const announceDiscoveryStatus = (message) => {
    if (elements.discoveryStatus) {
      elements.discoveryStatus.textContent = message;
    }
  };

  const announceAuditStatus = (message) => {
    if (elements.auditStatus) {
      elements.auditStatus.textContent = message;
    }
  };

  const announceFreeAuditStatus = (message) => {
    if (elements.freeAuditStatus) {
      elements.freeAuditStatus.textContent = String(message || '');
    }
  };

  const findOrganization = (organizationId) => {
    return state.organizations.find((organization) => String(organization.organization_id || '') === String(organizationId)) || null;
  };

  const getCurrentRelationshipOrganization = () => {
    const preferred = state.organizations.find((organization) => {
      if (!organization || isPersonalOrganization(organization)) {
        return false;
      }

      const status = String(organization.relationship_status || '').toLowerCase();
      return status === 'active';
    });

    if (preferred) {
      return preferred;
    }

    return state.organizations.find((organization) => {
      if (!organization || isPersonalOrganization(organization)) {
        return false;
      }

      const status = String(organization.relationship_status || '').toLowerCase();
      return status === 'pending';
    }) || null;
  };

  const setCurrentOrganizationStatus = (message) => {
    if (elements.currentStatus instanceof HTMLElement) {
      elements.currentStatus.textContent = String(message || '');
    }
  };

  const formatPhoneDisplayValue = (value) => {
    if (typeof PC.formatPhoneNumberValue === 'function') {
      return PC.formatPhoneNumberValue(String(value || ''));
    }

    return String(value || '');
  };

  const renderCurrentOrganizationMeta = (organization) => {
    if (!(elements.currentMeta instanceof HTMLElement)) {
      return;
    }

    const role = String(organization?.role || 'member').trim() || 'member';
    const relationshipStatus = String(organization?.relationship_status || organization?.status || 'active').trim() || 'active';
    const scopes = Array.isArray(organization?.scopes)
      ? organization.scopes.map((scope) => String(scope || '').trim()).filter((scope) => scope !== '').sort((left, right) => left.localeCompare(right))
      : [];
    const scopesText = scopes.length === 0 ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SCOPES_NONE_LISTED')); ?>' : scopes.join(', ');
    const ownerEmail = String(organization?.owner_email || '').trim();
    const ownerMarkup = ownerEmail === ''
      ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_UNAVAILABLE')); ?>'
      : `<a href="mailto:${encodeURIComponent(ownerEmail)}">${safeText(ownerEmail)}</a>`;

    Guardian.setHTML(elements.currentMeta, `
      <div class="organizations_current_meta_grid">
        <p><strong>${safeText(T.relationshipLabel)}:</strong> ${safeText(role)}</p>
        <p><strong>${safeText(T.ownerLabel)}:</strong> ${ownerMarkup}</p>
        <p><strong>${safeText(T.statusLabel)}:</strong> ${safeText(relationshipStatus)}</p>
        <p><strong>${safeText(T.scopesLabel)}:</strong> ${safeText(scopesText)}</p>
      </div>
    `);
  };

  const renderCurrentOrganizationPanel = () => {
    if (!(elements.currentPanel instanceof HTMLElement)) {
      return;
    }

    const organization = getCurrentRelationshipOrganization();
    if (!organization) {
      state.currentRelationshipOrganizationId = '';
      elements.currentPanel.classList.add('hidden');
      if (elements.freeAuditPanel instanceof HTMLElement) {
        elements.freeAuditPanel.classList.add('hidden');
      }
      if (elements.freeAuditGridContainer instanceof HTMLElement) {
        setDatagridMessage(elements.freeAuditGridContainer, '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_CURRENT_RELATIONSHIP')); ?>');
      }
      announceFreeAuditStatus('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_CURRENT_SELECTED_AUDIT')); ?>');
      if (elements.currentRevokeButton instanceof HTMLButtonElement) {
        elements.currentRevokeButton.disabled = true;
      }
      return;
    }

    state.currentRelationshipOrganizationId = String(organization.organization_id || '');
    elements.currentPanel.classList.remove('hidden');

    const relationshipLabel = String(organization.role || 'member').trim() || 'member';
    const organizationName = String(organization.name || 'Organization').trim() || 'Organization';

    if (elements.currentSummary instanceof HTMLElement) {
      Guardian.setHTML(elements.currentSummary, `You have provided <strong>${safeText(organizationName)}</strong> access to your work data. They may view your work entries at sites you have worked at.`);
    }

    renderCurrentOrganizationMeta(organization);
    setCurrentOrganizationStatus('');

    if (elements.currentRevokeButton instanceof HTMLButtonElement) {
      elements.currentRevokeButton.disabled = String(organization.role || '').toLowerCase() === 'owner';
    }

    if (elements.freeAuditPanel instanceof HTMLElement) {
      elements.freeAuditPanel.classList.remove('hidden');
    }

    loadFreeProfileAudit(state.currentRelationshipOrganizationId).catch((error) => PW.error(error));
  };

  const renderCurrentOrganizationDetailsDialog = (organization) => {
    if (!(elements.currentDetailsBody instanceof HTMLElement)) {
      return;
    }

    const addressLine1 = String(organization?.address_line1 || '').trim();
    const addressLine2 = String(organization?.address_line2 || '').trim();
    const city = String(organization?.address_city || '').trim();
    const province = String(organization?.address_region || '').trim();
    const postalCode = String(organization?.address_postal || '').trim();
    const country = String(organization?.address_country || '').trim();
    const localityLine = [city, province, postalCode].filter((part) => part !== '').join(' ');
    const addressLines = [addressLine1, addressLine2, localityLine, country].filter((line) => line !== '');
    const addressMarkup = addressLines.length > 0
      ? addressLines.map((line) => safeText(line)).join('<br>')
      : '';
    const scopes = Array.isArray(organization?.scopes)
      ? organization.scopes.map((scope) => String(scope || '').trim()).filter((scope) => scope !== '')
      : [];
    const rows = [
      ['Organization', String(organization?.name || '')],
      ['Relationship', String(organization?.role || '')],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_RELATIONSHIP_STATUS_LABEL')); ?>', String(organization?.relationship_status || organization?.status || '')],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_OWNER_NAME_LABEL')); ?>', String(organization?.owner_name || '')],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_OWNER_EMAIL_LABEL')); ?>', String(organization?.owner_email || '')],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_EMAIL')); ?>', String(organization?.contact_email || '')],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CONTACT_PHONE')); ?>', formatPhoneDisplayValue(String(organization?.contact_phone || ''))],
      ['Industry', String(organization?.industry || '')],
      ['Website', String(organization?.website || '')],
      ['Address', addressMarkup],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SUPPORT_HOURS')); ?>', String(organization?.support_hours || '')],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_EMPLOYEE_COUNT')); ?>', String(organization?.employee_count || '')],
      ['<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ACCESS_SCOPES_LABEL')); ?>', scopes.join(', ')],
    ].filter(([, value]) => String(value || '').trim() !== '');

    Guardian.setHTML(elements.currentDetailsBody, `
      <dl class="organizations_current_details_grid">
        ${rows.map(([label, value]) => `<dt>${safeText(label)}</dt><dd>${label === '<?php echo addslashes(org_js_index_i18n('ADDRESS')); ?>' ? String(value || '') : safeText(value)}</dd>`).join('')}
      </dl>
      <p class="help_text"><?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REVOKE_DETAILS_HELP')); ?></p>
    `);
  };

  const openCurrentOrganizationDetailsDialog = () => {
    const organization = getCurrentRelationshipOrganization();
    if (!organization || !(elements.currentDetailsDialog instanceof HTMLDialogElement)) {
      return;
    }

    renderCurrentOrganizationDetailsDialog(organization);
    if (!elements.currentDetailsDialog.open) {
      elements.currentDetailsDialog.showModal();
    }
  };

  const closeCurrentOrganizationDetailsDialog = () => {
    if (elements.currentDetailsDialog instanceof HTMLDialogElement && elements.currentDetailsDialog.open) {
      elements.currentDetailsDialog.close();
    }
  };

  const handleRevokeCurrentOrganizationAccess = async () => {
    const organization = getCurrentRelationshipOrganization();
    const organizationId = String(organization?.organization_id || '');
    if (organizationId === '') {
      return;
    }

    const confirmed = window.confirm('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REVOKE_CONFIRM')); ?>');
    if (!confirmed) {
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(organizationId)}/leave`, {});
      setCurrentOrganizationStatus('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ACCESS_REVOKED_STATUS')); ?>');
      PC.showToast(T.withdrawn, 'save', 6000, true);
      closeCurrentOrganizationDetailsDialog();
      await refreshIndex();
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.withdrawFailed;
      setCurrentOrganizationStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };

  const setStackMessage = (container, message) => {
    if (!container) {
      return;
    }

    container.textContent = message;
    container.classList.add('organizations_empty');
  };

  const announceGridStatus = (changeReason = 'loaded') => {
    if (!elements.gridStatus) {
      return;
    }

    const grid = elements.gridContainer?.querySelector('.datagrid[data-grid="organizations"]');
    if (!grid) {
      elements.gridStatus.textContent = `Organizations grid ${changeReason}. No results.`;
      return;
    }

    const rowCount = grid.querySelectorAll('.datagrid_row').length;
    const search = state.grid.search ? `search ${state.grid.search}` : 'no search filter';
    const sortInfo = state.grid.sort ? `${state.grid.sort} ${state.grid.direction || 'asc'}` : 'default order';
    const page = state.grid.page || 1;

    elements.gridStatus.textContent = `Organizations grid ${changeReason}. ${rowCount} result${rowCount === 1 ? '' : 's'}. ${sortInfo}. ${search}. Page ${page}.`;
  };

  const setGridMessage = (message) => {
    if (!elements.gridBody) {
      return;
    }

    Guardian.setHTML(elements.gridBody, `<div class="datagrid_empty">${message}</div>`);
    announceGridStatus('loaded');
  };

  const setDatagridMessage = (container, message) => {
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const body = container.querySelector('.datagrid_body');
    if (!(body instanceof HTMLElement)) {
      return;
    }

    Guardian.setHTML(body, `<div class="datagrid_empty">${String(message || '')}</div>`);
  };

  const setDiscoveryPanelStatus = (message) => {
    if (!elements.discoveryPanelStatus) {
      return;
    }

    elements.discoveryPanelStatus.textContent = String(message || '');
  };

  const getOrganizationUnreadCount = (organizationId) => {
    const org = findOrganization(organizationId);
    if (!org) {
      return 0;
    }

    const raw = Number(org.notification_unread_count || 0);
    if (!Number.isFinite(raw) || raw <= 0) {
      return 0;
    }

    return Math.floor(raw);
  };

  const markOrganizationNotificationsRead = async (organizationId) => {
    const orgId = String(organizationId || '').trim();
    if (orgId === '') {
      return;
    }

    try {
      const payload = await postForm(`/api/v1/organizations/${encodeURIComponent(orgId)}/notifications/read`, {});
      const unreadByOrg = payload && payload.unread_by_org && typeof payload.unread_by_org === 'object'
        ? payload.unread_by_org
        : {};

      state.organizations = state.organizations.map((organization) => {
        const id = String(organization?.organization_id || '');
        const unread = Math.max(0, Number(unreadByOrg[id] || 0));
        return {
          ...organization,
          notification_unread_count: String(unread),
          has_unread_notifications: unread > 0 ? '1' : '0',
        };
      });

      decorateGridRowsForPremiumLocks();
      window.dispatchEvent(new CustomEvent('paycal:notifications-updated'));
    } catch (_error) {
      // Non-blocking best-effort update.
    }
  };

  const applyUnreadByOrganizationMap = (unreadByOrg) => {
    if (!unreadByOrg || typeof unreadByOrg !== 'object') {
      return;
    }

    state.organizations = state.organizations.map((organization) => {
      const orgId = String(organization?.organization_id || '');
      const unread = Math.max(0, Number(unreadByOrg[orgId] || 0));
      return {
        ...organization,
        notification_unread_count: String(unread),
        has_unread_notifications: unread > 0 ? '1' : '0',
      };
    });

    decorateGridRowsForPremiumLocks();
    window.dispatchEvent(new CustomEvent('paycal:notifications-updated'));
  };

  const fetchOrganizationNotificationSnapshot = async () => {
    const params = new URLSearchParams({
      channel: 'organization_notifications',
    });

    if (state.notificationsSignature !== '') {
      params.set('since_signature', state.notificationsSignature);
    }

    const response = await fetch(`/ws/?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Organization notifications channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Organization notifications payload invalid.'));
    }

    return payload;
  };

  const syncOrganizationNotificationDots = async () => {
    if (state.organizations.length === 0) {
      return;
    }

    const payload = await fetchOrganizationNotificationSnapshot();
    state.notificationsSignature = String(payload.latest_signature || '');
    if (!payload.unchanged) {
      applyUnreadByOrganizationMap(payload.unread_by_org || {});
    }
  };

  const stopOrganizationNotificationPolling = () => {
    if (state.notificationsIntervalId !== null) {
      window.clearInterval(state.notificationsIntervalId);
      state.notificationsIntervalId = null;
    }
  };

  const startOrganizationNotificationPolling = () => {
    stopOrganizationNotificationPolling();
    state.notificationsSignature = '';

    syncOrganizationNotificationDots().catch((error) => PW.error(error));

    state.notificationsIntervalId = window.setInterval(() => {
      syncOrganizationNotificationDots().catch((error) => PW.error(error));
    }, 15000);
  };

  const extractLookupEmail = (rawValue) => {
    const value = String(rawValue || '').trim();
    if (value === '') {
      return '';
    }

    const emailMatch = value.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
    if (!emailMatch) {
      return '';
    }

    return String(emailMatch[0] || '').trim().toLowerCase();
  };

  const renderAccessLookupOptions = (datalistEl, suggestions) => {
    if (!(datalistEl instanceof HTMLDataListElement)) {
      return;
    }

    Guardian.setHTML(datalistEl, '');

    suggestions.forEach((suggestion) => {
      const email = String(suggestion && suggestion.email ? suggestion.email : '').trim();
      if (email === '') {
        return;
      }

      const ownerName = String(suggestion && suggestion.name ? suggestion.name : '').trim();
      const organizationName = String(suggestion && suggestion.organization_name ? suggestion.organization_name : '').trim();

      let value = ownerName === '' ? email : `${ownerName} <${email}>`;
      if (organizationName !== '') {
        value = `${organizationName} (${value})`;
      }

      const option = document.createElement('option');
      option.value = value;
      datalistEl.appendChild(option);
    });
  };

  const fetchAccessLookupSuggestions = async (query, options = {}) => {
    const params = new URLSearchParams();
    const trimmed = String(query || '').trim();
    if (trimmed !== '') {
      params.set('q', trimmed);
    }

    if (typeof options.mode === 'string' && options.mode.trim() !== '') {
      params.set('mode', options.mode.trim());
    }

    if (Number.isFinite(options.limit)) {
      params.set('limit', String(Math.max(1, Math.min(25, Number(options.limit)))));
    }

    const qs = params.toString();
    const endpoint = qs === ''
      ? '/api/v1/organizations/access/search'
      : `/api/v1/organizations/access/search?${qs}`;

    const payload = await apiRequest(endpoint, {
      timeoutMs: 12000,
    });

    return Array.isArray(payload.suggestions) ? payload.suggestions : [];
  };

  const safeText = (value) => {
    if (Guardian && typeof Guardian.sanitizedText === 'function') {
      return Guardian.sanitizedText(String(value ?? ''));
    }

    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  };

  const normalizeBrowserSuggestion = (suggestion) => {
    const email = String(suggestion?.email || '').trim().toLowerCase();
    const ownerName = String(suggestion?.name || '').trim();
    const organizationName = String(suggestion?.organization_name || '').trim();
    const key = `${organizationName.toLowerCase()}|${email}`;

    return {
      key,
      email,
      ownerName,
      organizationName,
      publicProfile: suggestion && typeof suggestion.public_profile === 'object' && suggestion.public_profile
        ? suggestion.public_profile
        : {},
      searchedAt: Date.now(),
    };
  };

  const setBrowserPanelStatus = (message) => {
    const text = String(message || '');
    if (elements.browserPanelStatus instanceof HTMLElement) {
      elements.browserPanelStatus.textContent = text;
    }
    if (elements.browserGridStatus instanceof HTMLElement) {
      elements.browserGridStatus.textContent = text;
    }
  };

  const renderBrowserGrid = (container, rows, emptyMessage) => {
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const body = container.querySelector('.datagrid_body');
    if (!(body instanceof HTMLElement)) {
      return;
    }

    if (!Array.isArray(rows) || rows.length === 0) {
      setDatagridMessage(container, emptyMessage);
      return;
    }

    const cards = rows.map((row) => {
      const organizationText = row.organizationName === '' ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_UNKNOWN_NAME')); ?>' : row.organizationName;
      const profile = row.publicProfile && typeof row.publicProfile === 'object' ? row.publicProfile : {};

      const city = String(profile.address_city || '').trim();
      const province = String(profile.address_region || '').trim();
      const countryRaw = String(profile.address_country || '').trim();
      const country = countryRaw === '' ? 'Canada' : countryRaw;
      const cityProvince = [city, province]
        .filter((part) => part !== '')
        .join(' ');
      const locationLine = [cityProvince, country]
        .filter((part) => part !== '')
        .join(', ');

      const industry = String(profile.industry || '').trim();
      const rawWebsite = String(profile.website || '').trim();
      const websiteText = rawWebsite.replace(/^https?:\/\//i, '').replace(/\/$/, '');
      const websiteHref = rawWebsite === ''
        ? ''
        : (/^https?:\/\//i.test(rawWebsite) ? rawWebsite : `https://${rawWebsite}`);
      const employeeCountRaw = String(profile.employee_count || '').trim();
      const employeeCount = /^\d+$/.test(employeeCountRaw)
        ? `${employeeCountRaw} ${employeeCountRaw === '1' ? 'employee' : 'employees'}`
        : employeeCountRaw;
      const supportHours = String(profile.support_hours || '').trim();
      const locationDisplay = locationLine === '' ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOCATION_UNAVAILABLE')); ?>' : locationLine;
      const ownerEmailDisplay = String(row.email || '').trim() === '' ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_EMAIL_AVAILABLE')); ?>' : String(row.email || '').trim();
      const industryDisplay = industry === '' ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_INDUSTRY_UNAVAILABLE')); ?>' : industry;
      const employeesDisplay = employeeCount === '' ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_EMPLOYEES_UNAVAILABLE')); ?>' : employeeCount;
      const websiteDisplay = websiteHref === ''
        ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_WEBSITE_UNAVAILABLE')); ?>'
        : `<a href="${safeText(websiteHref)}" target="_blank" rel="noopener noreferrer">${safeText(websiteText)}</a>`;
      const supportDisplay = supportHours === '' ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_SUPPORT_HOURS_UNAVAILABLE')); ?>' : safeText(supportHours);

      return `
        <article class="organizations_browser_card">
          <section class="organizations_browser_data_grid">
            <p class="organizations_browser_cell organizations_browser_name organizations_browser_span_full">${safeText(organizationText)}</p>
            <p class="organizations_browser_cell organizations_browser_location organizations_browser_span_full">${safeText(locationDisplay)}</p>
            <p class="organizations_browser_cell organizations_browser_owner_email organizations_browser_span_full">${safeText(ownerEmailDisplay)}</p>

            <p class="organizations_browser_cell organizations_browser_cell_label organizations_browser_website">${websiteDisplay}</p>
            <p class="organizations_browser_cell organizations_browser_cell_value organizations_browser_employees">${safeText(employeesDisplay)}</p>

            <p class="organizations_browser_cell organizations_browser_cell_label organizations_browser_industry">${safeText(industryDisplay)}</p>
            <p class="organizations_browser_cell organizations_browser_cell_value organizations_browser_support">${supportDisplay}</p>
          </section>
          <section class="organizations_browser_card_footer">
            <button
              type="button"
              class="btn btn_primary btn_sm organizations_browser_row_action"
              data-browser-action="connect"
              data-email="${safeText(row.email)}"
              data-org-name="${safeText(row.organizationName)}"
              data-owner-name="${safeText(row.ownerName)}"
            ><?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_REQUEST_JOIN_BTN')); ?></button>
          </section>
        </article>
      `;
    }).join('');

    Guardian.setHTML(body, `<div class="organizations_browser_cards" aria-label="<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CARDS_ARIA')); ?>">${cards}</div>`);
  };

  const loadBrowserRecent = () => {
    if (!window.localStorage) {
      return [];
    }

    try {
      const raw = window.localStorage.getItem(ORG_BROWSER_RECENT_STORAGE_KEY);
      if (!raw) {
        return [];
      }

      const parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) {
        return [];
      }

      return parsed
        .map((item) => normalizeBrowserSuggestion(item))
        .filter((item) => item.email !== '')
        .slice(0, ORG_BROWSER_RECENT_MAX);
    } catch (_error) {
      return [];
    }
  };

  const saveBrowserRecent = (rows) => {
    if (!window.localStorage) {
      return;
    }

    try {
      window.localStorage.setItem(ORG_BROWSER_RECENT_STORAGE_KEY, JSON.stringify(rows.slice(0, ORG_BROWSER_RECENT_MAX)));
    } catch (_error) {
      // Ignore storage errors to keep connect flow resilient.
    }
  };

  const renderBrowserRecent = () => {
    renderBrowserGrid(elements.browserRecentGrid, state.browserRecent, '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_BROWSER_RECENT_PLACEHOLDER')); ?>');
  };

  const rememberBrowserEntry = (row) => {
    const normalized = normalizeBrowserSuggestion(row);
    if (normalized.email === '') {
      return;
    }

    const nextRows = [
      normalized,
      ...state.browserRecent.filter((item) => item.key !== normalized.key),
    ].slice(0, ORG_BROWSER_RECENT_MAX);

    state.browserRecent = nextRows;
    saveBrowserRecent(nextRows);
    renderBrowserRecent();
  };

  const runBrowserSearch = async (query) => {
    const trimmed = String(query || '').trim();
    if (trimmed.length < ACCESS_LOOKUP_MIN_CHARS) {
      state.browserLastResults = [];
      renderBrowserGrid(elements.browserGrid, [], '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_BROWSER_MIN_CHARS')); ?>');
      setBrowserPanelStatus('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_BROWSER_MIN_CHARS')); ?>');
      return;
    }

    const suggestions = await fetchAccessLookupSuggestions(trimmed);
    const rows = suggestions
      .map((suggestion) => normalizeBrowserSuggestion(suggestion))
      .filter((row) => row.email !== '');

    state.browserLastResults = rows;
    renderBrowserGrid(elements.browserGrid, rows, '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_BROWSER_NO_MATCHES')); ?>');

    if (rows.length === 0) {
      setBrowserPanelStatus(`No organizations matched "${trimmed}".`);
      return;
    }

    setBrowserPanelStatus(`Found ${rows.length} organization result${rows.length === 1 ? '' : 's'} for "${trimmed}".`);
  };

  const connectToOrganizationFromBrowser = async (email, organizationName = '', ownerName = '') => {
    const normalizedEmail = String(email || '').trim().toLowerCase();
    if (normalizedEmail === '') {
      PC.showToast('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_BROWSER_SELECT_VALID_CONTACT')); ?>', 'error', 5000, true);
      return;
    }

    await postForm('/api/v1/organizations/access/request', {
      owner_email: normalizedEmail,
    });

    const matchingResult = state.browserLastResults.find((row) => {
      return row.email === normalizedEmail
        && String(row.organizationName || '') === String(organizationName || '');
    });

    rememberBrowserEntry({
      email: normalizedEmail,
      organization_name: organizationName,
      name: ownerName,
      public_profile: matchingResult && matchingResult.publicProfile ? matchingResult.publicProfile : {},
    });

    const organizationLabel = String(organizationName || '').trim();
    const successMessage = organizationLabel === ''
      ? `<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ACCESS_REQUEST_SUBMITTED_FOR')); ?>`.replace('%s', normalizedEmail)
      : `<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_ACCESS_REQUEST_SUBMITTED_TO')); ?>`.replace('%s', organizationLabel);

    if (elements.requestEmail instanceof HTMLInputElement) {
      elements.requestEmail.value = normalizedEmail;
    }

    setDiscoveryPanelStatus(successMessage);
    setBrowserPanelStatus(successMessage);
    PC.showToast(successMessage, 'save', 7000, true);
  };

  const initializeOrganizationBrowser = () => {
    if (!(elements.browserGrid instanceof HTMLElement)) {
      return;
    }

    fetchAccessLookupSuggestions('', { mode: 'latest', limit: 10 })
      .then((suggestions) => {
        const rows = suggestions
          .map((suggestion) => normalizeBrowserSuggestion(suggestion))
          .filter((row) => row.email !== '');

        state.browserRecent = rows;
        renderBrowserRecent();
      })
      .catch((error) => {
        PW.error(error);
        state.browserRecent = loadBrowserRecent();
        renderBrowserRecent();
      });

    setBrowserPanelStatus('<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_BROWSER_HELP')); ?>');
  };

  const bindAccessLookupInput = (inputEl, datalistEl) => {
    if (!(inputEl instanceof HTMLInputElement) || !(datalistEl instanceof HTMLDataListElement)) {
      return;
    }

    let debounceId = null;
    let requestSeq = 0;

    const runLookup = async () => {
      const query = String(inputEl.value || '').trim();
      if (query.length < ACCESS_LOOKUP_MIN_CHARS) {
        renderAccessLookupOptions(datalistEl, []);
        return;
      }

      requestSeq += 1;
      const mySeq = requestSeq;

      try {
        const suggestions = await fetchAccessLookupSuggestions(query);
        if (mySeq !== requestSeq) {
          return;
        }

        renderAccessLookupOptions(datalistEl, suggestions);
      } catch (_error) {
        if (mySeq !== requestSeq) {
          return;
        }

        renderAccessLookupOptions(datalistEl, []);
      }
    };

    inputEl.addEventListener('input', () => {
      if (debounceId !== null) {
        window.clearTimeout(debounceId);
      }

      debounceId = window.setTimeout(() => {
        runLookup().catch((error) => PW.error(error));
        debounceId = null;
      }, ACCESS_LOOKUP_DEBOUNCE_MS);
    });

    inputEl.addEventListener('focus', () => {
      const query = String(inputEl.value || '').trim();
      if (query.length >= ACCESS_LOOKUP_MIN_CHARS) {
        runLookup().catch((error) => PW.error(error));
      }
    });
  };

  const getPersonalPayAnchor = () => {
    if (elements.personalPayAnchor instanceof HTMLInputElement || elements.personalPayAnchor instanceof HTMLSelectElement) {
      return String(elements.personalPayAnchor.value || 'Monday');
    }

    return 'Monday';
  };

  const setPersonalPayAnchor = (value) => {
    if (elements.personalPayAnchor instanceof HTMLInputElement || elements.personalPayAnchor instanceof HTMLSelectElement) {
      elements.personalPayAnchor.value = value;
    }
  };

  const getPersonalPayPeriodStart = () => {
    if (elements.personalPayPeriodStart instanceof HTMLInputElement) {
      return String(elements.personalPayPeriodStart.value || '');
    }

    return '';
  };

  const setPersonalPayPeriodStart = (value) => {
    if (elements.personalPayPeriodStart instanceof HTMLInputElement) {
      elements.personalPayPeriodStart.value = value;
    }
  };

  const getPersonalEditingGraceDays = () => {
    const checkedRadio = Array.from(elements.personalEditingGraceDayRadios).find((radio) => radio instanceof HTMLInputElement && radio.checked);
    return checkedRadio instanceof HTMLInputElement ? String(checkedRadio.value || '0') : '0';
  };

  const setPersonalEditingGraceDays = (value) => {
    const normalizedValue = ['0', '1', '2', '3'].includes(String(value)) ? String(value) : '0';
    Array.from(elements.personalEditingGraceDayRadios).forEach((radio) => {
      if (radio instanceof HTMLInputElement) {
        radio.checked = radio.value === normalizedValue;
      }
    });
  };

  const toTitleLabel = (value, fallback = '') => {
    const source = String(value || '').trim();
    if (source === '') {
      return fallback;
    }

    return source
      .replace(/[_-]+/g, ' ')
      .split(/\s+/)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
      .join(' ');
  };

  const setSelectValueSafe = (selectEl, rawValue, fallback = '') => {
    if (!(selectEl instanceof HTMLSelectElement)) {
      return;
    }

    const normalized = String(rawValue || fallback || '').trim().toLowerCase();
    if (normalized === '') {
      return;
    }

    const hasOption = Array.from(selectEl.options).some((option) => option.value === normalized);
    if (!hasOption) {
      const fallbackLabel = toTitleLabel(normalized, normalized);
      const option = new Option(fallbackLabel, normalized);
      selectEl.add(option);
    }

    selectEl.value = normalized;
  };

  const isPersonalOrganizationById = (organizationId) => {
    const organization = findOrganization(organizationId);
    return !!organization && isPersonalOrganization(organization);
  };

  const getPersonalOrganization = () => {
    return state.organizations.find((organization) => isPersonalOrganization(organization)) || null;
  };

  const syncPersonalFrequency = () => {
    if (!(elements.personalPayFrequency instanceof HTMLSelectElement)) {
      if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
        elements.personalPayPeriodLength.value = FREQUENCY_LENGTHS.biweekly;
      }
      return FREQUENCY_LENGTHS.biweekly;
    }

    const nextLength = FREQUENCY_LENGTHS[elements.personalPayFrequency.value] || FREQUENCY_LENGTHS.biweekly;
    if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
      elements.personalPayPeriodLength.value = nextLength;
    }

    return nextLength;
  };

  const renderPersonalPreview = () => {
    if (!elements.personalPreview) {
      return;
    }

    const frequency = elements.personalPayFrequency instanceof HTMLSelectElement
      ? elements.personalPayFrequency.value
      : 'biweekly';

    const startRaw = getPersonalPayPeriodStart();
    const anchor = getPersonalPayAnchor();
    const graceDays = getPersonalEditingGraceDays();

    const parseYmd = (ymd) => new Date(`${ymd}T00:00:00`);
    const ppAddDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
    const ppFormatYmd = (date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };
    const alignToAnchor = (start, anchorDay) => {
      const target = PAY_PERIOD_WEEKDAY_MAP[anchorDay] ?? 1;
      let cursor = new Date(start.getFullYear(), start.getMonth(), start.getDate());
      while (cursor.getDay() !== target) {
        cursor = ppAddDays(cursor, -1);
      }
      return cursor;
    };
    const nextPeriod = (start, periodFrequency) => {
      if (periodFrequency === 'weekly') {
        return ppAddDays(start, 7);
      }
      if (periodFrequency === 'biweekly') {
        return ppAddDays(start, 14);
      }
      if (periodFrequency === 'semimonthly') {
        if (start.getDate() <= 15) {
          return new Date(start.getFullYear(), start.getMonth(), 16);
        }
        return new Date(start.getFullYear(), start.getMonth() + 1, 1);
      }

      return new Date(start.getFullYear(), start.getMonth() + 1, 1);
    };
    const currentPeriod = (startRaw, periodFrequency, anchorDay) => {
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

      if (periodFrequency === 'weekly') {
        const start = alignToAnchor(today, anchorDay);
        return { start, endExclusive: ppAddDays(start, 7) };
      }
      if (periodFrequency === 'biweekly') {
        // Keep the user-selected cadence, but align to the currently selected anchor day.
        // This makes anchor changes reflect immediately without requiring an extra calendar click.
        const start = alignToAnchor(parseYmd(startRaw), anchorDay);
        return { start, endExclusive: ppAddDays(start, 14) };
      }
      if (periodFrequency === 'semimonthly') {
        if (today.getDate() <= 15) {
          const start = new Date(today.getFullYear(), today.getMonth(), 1);
          return { start, endExclusive: new Date(today.getFullYear(), today.getMonth(), 16) };
        }

        const start = new Date(today.getFullYear(), today.getMonth(), 16);
        return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
      }

      const start = new Date(today.getFullYear(), today.getMonth(), 1);
      return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
    };
    const startOfWeek = (date) => ppAddDays(date, -date.getDay());
    const inRange = (date, start, endExclusive) => date >= start && date < endExclusive;
    const monthLabel = (date) => date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    const buildRibbonCalendar = (periods, grace, today) => {
      const stripbar = PAY_PERIOD_DAY_NAMES.map((day) => `<span class="pp_day_head">${day}</span>`).join('');
      const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
      const gridStart = startOfWeek(firstOfMonth);
      const badgesPlaced = { p1: false, p2: false };
      let bodyRows = '';

      for (let week = 0; week < 6; week += 1) {
        bodyRows += '<tr>';
        for (let day = 0; day < 7; day += 1) {
          const offset = (week * 7) + day;
          const cellDate = ppAddDays(gridStart, offset);
          const isToday = ppFormatYmd(cellDate) === ppFormatYmd(today);
          const classes = ['pp_day_cell'];
          let badge = '';

          periods.forEach((period, index) => {
            const periodKey = index === 0 ? 'p1' : 'p2';
            const prevDate = ppAddDays(cellDate, -1);
            const nextDate = ppAddDays(cellDate, 1);
            const active = inRange(cellDate, period.start, period.endExclusive);
            const prevActive = inRange(prevDate, period.start, period.endExclusive);
            const nextActive = inRange(nextDate, period.start, period.endExclusive);
            const graceStart = period.endExclusive;
            const graceEndExclusive = ppAddDays(graceStart, grace);
            const graceActive = inRange(cellDate, graceStart, graceEndExclusive);

            if (active) {
              classes.push('pp_in_period', `pp_in_${periodKey}`);
              if (!prevActive) {
                classes.push(`pp_ribbon_start_${periodKey}`);
              }
              if (!nextActive) {
                classes.push(`pp_ribbon_end_${periodKey}`);
              }
              if (!badgesPlaced[periodKey]) {
                badge = `<span class="pp_badge ${periodKey === 'p2' ? 'pp_badge_p2' : ''}">${period.label}</span>`;
                badgesPlaced[periodKey] = true;
              }
            }

            if (graceActive && grace > 0) {
              const graceIndex = Math.min(grace, Math.max(1, Math.floor((cellDate - graceStart) / 86400000) + 1));
              classes.push('pp_grace_day', `pp_grace_${graceIndex}`, `pp_grace_${periodKey}`);
            }
          });

          if (isToday) {
            classes.push('pp_today');
          }

          bodyRows += `<td class="${classes.join(' ')}" data-ymd="${ppFormatYmd(cellDate)}" tabindex="0"><span class="pp_day_number">${String(cellDate.getDate()).padStart(2, '0')}</span>${badge}</td>`;
        }
        bodyRows += '</tr>';
      }

      return `
        <div class="pp_month_label">${monthLabel(today)}</div>
        <div class="pp_stripbar">${stripbar}</div>
        <table class="pp_three_week">
          <tbody>${bodyRows}</tbody>
        </table>
      `;
    };

    // For biweekly with no stored start, seed from the anchor-aligned date closest to today
    // so the calendar renders and the user can click to set their real period start.
    // alignToAnchor is defined above by this point.
    const now2 = new Date();
    const today2 = new Date(now2.getFullYear(), now2.getMonth(), now2.getDate());
    const startValue = startRaw !== '' ? startRaw : ppFormatYmd(alignToAnchor(today2, anchor));

    const period1 = currentPeriod(startValue, frequency, anchor);
    const period2 = {
      start: period1.endExclusive,
      endExclusive: nextPeriod(period1.endExclusive, frequency),
    };
    const endInclusive1 = ppAddDays(period1.endExclusive, -1);
    const endInclusive2 = ppAddDays(period2.endExclusive, -1);
    const periods = [
      { label: 'P1', start: period1.start, endExclusive: period1.endExclusive },
      { label: 'P2', start: period2.start, endExclusive: period2.endExclusive },
    ];
    const today = new Date();
    const graceDaysInt = parseInt(graceDays, 10);
    const safeGraceDays = Number.isNaN(graceDaysInt) ? 0 : graceDaysInt;
    const previewSignature = [
      frequency,
      startValue,
      anchor,
      String(safeGraceDays),
      ppFormatYmd(today),
    ].join('|');

    if (state.personalPreviewSignature === previewSignature) {
      return;
    }

    state.personalPreviewSignature = previewSignature;

    Guardian.setHTML(elements.personalPreview, `
      ${buildRibbonCalendar(periods, safeGraceDays, today)}
      <div class="pp_preview_summary"><span class="pp_preview_summary_item">P1: ${ppFormatYmd(period1.start)} to ${ppFormatYmd(endInclusive1)}</span><span class="pp_preview_summary_item">P2: ${ppFormatYmd(period2.start)} to ${ppFormatYmd(endInclusive2)}</span></div>
    `);
  };

  const schedulePersonalPreviewRender = () => {
    if (state.personalPreviewRafId !== null) {
      return;
    }

    state.personalPreviewRafId = window.requestAnimationFrame(() => {
      state.personalPreviewRafId = null;
      renderPersonalPreview();
    });
  };

  const displayCurrencyValue = (searchEl, code) => {
    if (!(searchEl instanceof HTMLInputElement)) return;
    const entry = (code && CURRENCY_LIST[code]) ? CURRENCY_LIST[code] : null;
    searchEl.value = entry ? `${entry.code} \u2014 ${entry.name}` : (code || '');
  };

  const initCurrencyFinder = (searchId, hiddenId, listboxId, wrapperId) => {
    const searchEl = document.getElementById(searchId);
    const hiddenEl = document.getElementById(hiddenId);
    const listboxEl = document.getElementById(listboxId);
    const wrapperEl = document.getElementById(wrapperId);
    if (!(searchEl instanceof HTMLInputElement) || !(hiddenEl instanceof HTMLInputElement) || !listboxEl || !wrapperEl) return;

    let activeIndex = -1;

    const closeList = () => {
      listboxEl.hidden = true;
      wrapperEl.setAttribute('aria-expanded', 'false');
      activeIndex = -1;
    };

    const setActive = (index) => {
      const items = Array.from(listboxEl.querySelectorAll('.currency_finder_item'));
      items.forEach((item, i) => {
        const on = i === index;
        item.setAttribute('aria-selected', on ? 'true' : 'false');
        item.classList.toggle('currency_finder_item_active', on);
      });
      if (items[index]) items[index].scrollIntoView({ block: 'nearest' });
      activeIndex = index;
    };

    const selectCode = (code) => {
      hiddenEl.value = code;
      displayCurrencyValue(searchEl, code);
      closeList();
      const currency = CURRENCY_LIST[code] || null;
      const label = currency ? `${currency.code} - ${currency.name}` : code;
      PC.showToast(`Currency updated: ${label}.`, 'save');
      hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const buildList = (query) => {
      const q = query.toLowerCase().trim();
      const matches = Object.values(CURRENCY_LIST).filter((c) =>
        q === '' ||
        c.code.toLowerCase().includes(q) ||
        c.name.toLowerCase().includes(q) ||
        c.countries.toLowerCase().includes(q)
      ).slice(0, 60);
      if (matches.length === 0) { closeList(); return; }
      const html = matches.map((c, i) =>
        `<li class="currency_finder_item" role="option" id="${listboxId}_item_${i}" data-code="${c.code}" aria-selected="false" tabindex="-1">` +
        `<span class="currency_finder_code">${c.code}</span>` +
        `<span class="currency_finder_symbol">${c.symbol}</span>` +
        `<span class="currency_finder_name">${c.name}</span>` +
        `</li>`
      ).join('');
      Guardian.setHTML(listboxEl, html);
      activeIndex = -1;
      listboxEl.hidden = false;
      wrapperEl.setAttribute('aria-expanded', 'true');
      listboxEl.querySelectorAll('.currency_finder_item').forEach((item) => {
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          const code = String(item.getAttribute('data-code') || '');
          if (code) selectCode(code);
        });
      });
    };

    searchEl.addEventListener('input', () => buildList(searchEl.value));
    searchEl.addEventListener('focus', () => {
      const currentCode = hiddenEl.value || '';
      buildList(currentCode && CURRENCY_LIST[currentCode] ? '' : searchEl.value);
    });
    searchEl.addEventListener('blur', () => setTimeout(closeList, 160));
    searchEl.addEventListener('keydown', (e) => {
      const items = Array.from(listboxEl.querySelectorAll('.currency_finder_item'));
      const pageStep = 10;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIndex + 1, items.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIndex - 1, 0));
      } else if (e.key === 'Home') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(0);
        }
      } else if (e.key === 'End') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(items.length - 1);
        }
      } else if (e.key === 'PageDown') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? Math.min(pageStep - 1, items.length - 1)
            : Math.min(activeIndex + pageStep, items.length - 1);
          setActive(nextIndex);
        }
      } else if (e.key === 'PageUp') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? 0
            : Math.max(activeIndex - pageStep, 0);
          setActive(nextIndex);
        }
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex >= 0 && items[activeIndex]) {
          const code = String(items[activeIndex].getAttribute('data-code') || '');
          if (code) selectCode(code);
        }
      } else if (e.key === 'Escape') {
        closeList();
        displayCurrencyValue(searchEl, hiddenEl.value);
      }
    });
  };

  const displayTimezoneValue = (searchEl, value) => {
    if (!(searchEl instanceof HTMLInputElement)) return;
    const zone = String(value || '');
    if (zone === '') {
      searchEl.value = '';
      return;
    }
    const meta = TIMEZONE_MAP[zone] || null;
    searchEl.value = meta ? meta.label : zone;
  };

  const initTimezoneFinder = (searchId, hiddenId, listboxId, wrapperId) => {
    const searchEl = document.getElementById(searchId);
    const hiddenEl = document.getElementById(hiddenId);
    const listboxEl = document.getElementById(listboxId);
    const wrapperEl = document.getElementById(wrapperId);
    if (!(searchEl instanceof HTMLInputElement) || !(hiddenEl instanceof HTMLInputElement) || !listboxEl || !wrapperEl) return;

    let activeIndex = -1;

    const closeList = () => {
      listboxEl.hidden = true;
      wrapperEl.setAttribute('aria-expanded', 'false');
      activeIndex = -1;
    };

    const setActive = (index) => {
      const items = Array.from(listboxEl.querySelectorAll('.timezone_finder_item'));
      items.forEach((item, i) => {
        const on = i === index;
        item.setAttribute('aria-selected', on ? 'true' : 'false');
        item.classList.toggle('timezone_finder_item_active', on);
      });
      if (items[index]) items[index].scrollIntoView({ block: 'nearest' });
      activeIndex = index;
    };

    const selectZone = (zone) => {
      hiddenEl.value = zone;
      displayTimezoneValue(searchEl, zone);
      closeList();
      const meta = TIMEZONE_MAP[zone] || null;
      const label = meta ? `${zone} [UTC${meta.offsetNow}]` : zone;
      PC.showToast(`Timezone updated: ${label}.`, 'save');
      hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const buildList = (query) => {
      const q = query.toLowerCase().trim();
      const matches = TIMEZONE_META.filter((item) => q === '' || item.searchable.includes(q)).slice(0, 80);
      if (matches.length === 0) {
        closeList();
        return;
      }
      const html = matches.map((item, i) =>
        `<li class="timezone_finder_item" role="option" id="${listboxId}_item_${i}" data-zone="${item.zone}" aria-selected="false" tabindex="-1">` +
        `<span class="timezone_finder_name">${item.zone}</span>` +
        `<span class="timezone_finder_offset">[UTC${item.offsetNow}]</span>` +
        `<span class="timezone_finder_abbr">${item.abbreviations.join('/')}</span>` +
        `</li>`
      ).join('');
      Guardian.setHTML(listboxEl, html);
      activeIndex = -1;
      listboxEl.hidden = false;
      wrapperEl.setAttribute('aria-expanded', 'true');
      listboxEl.querySelectorAll('.timezone_finder_item').forEach((item) => {
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          const zone = String(item.getAttribute('data-zone') || '');
          if (zone) selectZone(zone);
        });
      });
    };

    searchEl.addEventListener('input', () => buildList(searchEl.value));
    searchEl.addEventListener('focus', () => {
      buildList(searchEl.value);
    });
    searchEl.addEventListener('blur', () => setTimeout(closeList, 160));
    searchEl.addEventListener('keydown', (e) => {
      const items = Array.from(listboxEl.querySelectorAll('.timezone_finder_item'));
      const pageStep = 10;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIndex + 1, items.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIndex - 1, 0));
      } else if (e.key === 'Home') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(0);
        }
      } else if (e.key === 'End') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(items.length - 1);
        }
      } else if (e.key === 'PageDown') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? Math.min(pageStep - 1, items.length - 1)
            : Math.min(activeIndex + pageStep, items.length - 1);
          setActive(nextIndex);
        }
      } else if (e.key === 'PageUp') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? 0
            : Math.max(activeIndex - pageStep, 0);
          setActive(nextIndex);
        }
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex >= 0 && items[activeIndex]) {
          const zone = String(items[activeIndex].getAttribute('data-zone') || '');
          if (zone) selectZone(zone);
        }
      } else if (e.key === 'Escape') {
        closeList();
        displayTimezoneValue(searchEl, hiddenEl.value);
      }
    });
  };

  const loadPersonalOrganizationPanel = () => {
    const panel = document.getElementById('panel-pay-period');
    let settings = {};
    try {
      const raw = typeof panel?.dataset.userSettings === 'string' ? panel.dataset.userSettings : '';
      if (raw !== '') {
        settings = JSON.parse(raw);
      }
    } catch (_err) {
      PW.error(_err);
    }

    if (elements.personalDefaultWage instanceof HTMLInputElement) {
      elements.personalDefaultWage.value = String(settings.pay_rate || '');
    }
    if (elements.personalTimezone instanceof HTMLInputElement) {
      const tz = String(settings.timezone || '');
      elements.personalTimezone.value = tz;
      displayTimezoneValue(elements.personalTimezoneSearch, tz);
    }
    if (elements.personalCurrency instanceof HTMLInputElement) {
      const cur = String(settings.currency || '');
      elements.personalCurrency.value = cur;
      displayCurrencyValue(elements.personalCurrencySearch, cur);
    }
    if (elements.personalPayFrequency instanceof HTMLSelectElement) {
      elements.personalPayFrequency.value = String(settings.pay_frequency || 'biweekly');
    }
    setPersonalPayAnchor(String(settings.pay_anchor || 'Monday'));
    setPersonalPayPeriodStart(String(settings.pay_period_start || ''));
    if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
      elements.personalPayPeriodLength.value = String(settings.pay_period_length || syncPersonalFrequency());
    }
    setPersonalEditingGraceDays(String(settings.editing_grace_days || '0'));
    state.personalEditingGraceDaysValue = getPersonalEditingGraceDays();

    syncPersonalFrequency();
    schedulePersonalPreviewRender();
  };

  const buildPersonalSettingsFormData = () => {
    const formData = new FormData();

    const settingsCsrf = String(
      (document.getElementById('settings_csrf_token') instanceof HTMLInputElement
        ? (/** @type {HTMLInputElement} */ (document.getElementById('settings_csrf_token'))).value
        : '') || ''
    ).trim();
    if (settingsCsrf !== '') {
      formData.set('csrf_token', settingsCsrf);
    }

    formData.set('pay_frequency', elements.personalPayFrequency instanceof HTMLSelectElement ? elements.personalPayFrequency.value : 'biweekly');
    formData.set('pay_anchor', getPersonalPayAnchor());
    formData.set('pay_period_start', getPersonalPayPeriodStart());
    formData.set('pay_period_length', String(syncPersonalFrequency()));
    formData.set('editing_grace_days', getPersonalEditingGraceDays());
    formData.set('pay_rate', elements.personalDefaultWage instanceof HTMLInputElement ? elements.personalDefaultWage.value.trim() : '');
    formData.set('timezone', elements.personalTimezone instanceof HTMLInputElement ? elements.personalTimezone.value.trim() : '');
    formData.set('currency', elements.personalCurrency instanceof HTMLInputElement ? elements.personalCurrency.value.trim() : '');

    return formData;
  };

  /**
   * Save personal pay/profile settings with request de-duplication.
   *
   * Plain-language behavior:
   * 1) Build the payload from current form values.
   * 2) Skip saving if nothing changed.
   * 3) If a save is already running, queue one final save with the latest values.
   */
  const savePersonalOrganizationSettings = async (source = 'auto') => {
    const formData = buildPersonalSettingsFormData();

    // Build a stable signature of the current values for dedup.
    const payloadSignature = [
      formData.get('pay_frequency'),
      formData.get('pay_anchor'),
      formData.get('pay_period_start'),
      formData.get('pay_period_length'),
      formData.get('editing_grace_days'),
      formData.get('pay_rate'),
      formData.get('timezone'),
      formData.get('currency'),
    ].join('|');

    if (state.personalSaveInFlight) {
      state.personalSavePendingSource = source;
      state.personalPendingSignature = payloadSignature;
      return;
    }

    if (state.personalLastSavedSignature === payloadSignature) {
      return;
    }

    state.personalSaveInFlight = true;

    const savingMessage = source === 'calendar-day'
      ? 'Saving pay period start...'
      : 'Saving profile settings...';
    PC.showToast(savingMessage, 'save');

    try {
        // Debug: log what we're sending
        const debugPayload = {
          csrf_token: formData.get('csrf_token') ? '***' : 'MISSING',
          pay_frequency: formData.get('pay_frequency'),
          pay_anchor: formData.get('pay_anchor'),
          pay_period_start: formData.get('pay_period_start'),
          pay_period_length: formData.get('pay_period_length'),
          editing_grace_days: formData.get('editing_grace_days'),
          pay_rate: formData.get('pay_rate'),
          timezone: formData.get('timezone'),
          currency: formData.get('currency'),
        };
        debugLog('[savePersonalOrganizationSettings] Sending to /api/v1/profile/update/', debugPayload);

        const result = await PC.updateResource('profile', formData, { timeoutMs: 45000 });
        debugLog('[savePersonalOrganizationSettings] Success response', result);
      state.personalLastSavedSignature = payloadSignature;

      const successMessage = source === 'calendar-day'
        ? 'Pay period start and anchor updated.'
        : 'Profile settings saved.';
      PC.showToast(successMessage, 'save');
    } catch (error) {
      PW.error(error);
      debugLog('[savePersonalOrganizationSettings] Error caught:', {
        message: error instanceof Error ? error.message : String(error),
        stack: error instanceof Error ? error.stack : undefined,
      });
      PC.showToast(error instanceof Error && error.message ? error.message : T.defaultsSaveFailed, 'error');
    } finally {
      state.personalSaveInFlight = false;

      if (state.personalSavePendingSource !== '' && state.personalPendingSignature !== state.personalLastSavedSignature) {
        const queuedSource = state.personalSavePendingSource;
        state.personalSavePendingSource = '';
        state.personalPendingSignature = '';
        savePersonalOrganizationSettings(queuedSource).catch((error) => PW.error(error));
      } else {
        state.personalSavePendingSource = '';
        state.personalPendingSignature = '';
      }
    }
  };

  /**
   * Debounce helper for autosave.
   * Waits briefly after user input so we do one save for a burst of changes.
   */
  const schedulePersonalAutoSave = (delayMs = 450, source = 'auto') => {
    if (state.personalAutoSaveTimerId !== null) {
      window.clearTimeout(state.personalAutoSaveTimerId);
    }

    state.personalAutoSaveTimerId = window.setTimeout(() => {
      savePersonalOrganizationSettings(source).catch((error) => PW.error(error));
      state.personalAutoSaveTimerId = null;
    }, delayMs);
  };

  const handlePersonalGraceDaysChange = () => {
    const nextValue = getPersonalEditingGraceDays();
    if (state.personalEditingGraceDaysValue === nextValue) {
      return;
    }

    state.personalEditingGraceDaysValue = nextValue;
    schedulePersonalPreviewRender();
    PC.showToast('Saving pay period settings...', 'save');
    schedulePersonalAutoSave(180, 'grace');
  };

  const handlePersonalPreviewInteraction = (event) => {
    const target = event.target instanceof Element
      ? event.target.closest('.pp_day_cell[data-ymd]')
      : null;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const selectedYmd = String(target.dataset.ymd || '');
    if (selectedYmd === '') {
      return;
    }

    const selectedDate = new Date(`${selectedYmd}T00:00:00`);
    if (Number.isNaN(selectedDate.getTime())) {
      return;
    }

    const selectedAnchor = PAY_PERIOD_CANONICAL_WEEKDAY_NAMES[selectedDate.getDay()] || 'Monday';

    setPersonalPayPeriodStart(selectedYmd);
    setPersonalPayAnchor(selectedAnchor);
    schedulePersonalPreviewRender();
    schedulePersonalAutoSave(120, 'calendar-day');
  };

  const maskActorLabel = (actorUUID) => {
    const actor = String(actorUUID || '').trim();
    if (actor === '') {
      return T.unknown;
    }

    if (actor === currentUserUUID) {
      return 'You';
    }

    return 'Organization member';
  };

  const maskTechnicalDetails = (details) => {
    const value = String(details || '').trim();
    if (value === '') {
      return '';
    }

    if (/uuid|organization_id|site_owner_uuid|target_user_uuid/i.test(value)) {
      return '';
    }

    return value;
  };

  const resetInlineDeleteConfirm = () => {
    if (state.inlineDeleteConfirmTimerId !== null) {
      window.clearTimeout(state.inlineDeleteConfirmTimerId);
      state.inlineDeleteConfirmTimerId = null;
    }

    state.inlineDeleteConfirmOrgId = '';

    if (!elements.gridContainer) {
      return;
    }

    elements.gridContainer.querySelectorAll('.organizations_delete_pill').forEach((button) => {
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }

      button.dataset.confirm = '0';
      button.classList.remove('organizations_delete_pill_confirm');
      button.textContent = '<?php echo addslashes(org_js_index_i18n('REMOVE')); ?>';
    });
  };

  const armInlineDeleteConfirm = (button, organizationId) => {
    resetInlineDeleteConfirm();

    button.dataset.confirm = '1';
    button.classList.add('organizations_delete_pill_confirm');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('CONFIRM_DELETE')); ?>';

    state.inlineDeleteConfirmOrgId = organizationId;
    state.inlineDeleteConfirmTimerId = window.setTimeout(() => {
      resetInlineDeleteConfirm();
    }, 5000);
  };

  const decorateGridRowsForPremiumLocks = () => {
    if (!elements.gridContainer) {
      return;
    }

    elements.gridContainer.querySelectorAll('.datagrid_row').forEach((row) => {
      if (!(row instanceof HTMLElement)) {
        return;
      }

      row.classList.remove('organizations_row_premium_locked');

      const existingChip = row.querySelector('.organizations_premium_chip');
      if (existingChip) {
        existingChip.remove();
      }

      const removeButton = row.querySelector('.datagrid_action[data-action="remove"]');
      if (removeButton instanceof HTMLButtonElement) {
        removeButton.classList.add('organizations_delete_pill');
        removeButton.classList.remove('organizations_delete_pill_confirm');
        removeButton.dataset.confirm = '0';
        removeButton.textContent = '<?php echo addslashes(org_js_index_i18n('REMOVE')); ?>';

        const organizationId = String(row.dataset.id || '');
        if (isPersonalOrganizationById(organizationId)) {
          removeButton.remove();
        }
      }

      const organizationId = String(row.dataset.id || '');
      if (organizationId === '') {
        return;
      }

      const firstCell = row.querySelector('.datagrid_item');
      if (!(firstCell instanceof HTMLElement)) {
        return;
      }

      const existingDot = firstCell.querySelector('.organizations_notification_dot');
      if (existingDot) {
        existingDot.remove();
      }

      const unreadCount = getOrganizationUnreadCount(organizationId);
      if (unreadCount > 0) {
        const dot = document.createElement('span');
        dot.className = 'organizations_notification_dot';
        dot.setAttribute('aria-label', `Unread notifications: ${String(unreadCount)}`);
        dot.title = unreadCount > 99 ? '99+ unread notifications' : `${String(unreadCount)} unread notification${unreadCount === 1 ? '' : 's'}`;
        firstCell.appendChild(dot);
      }

      const organization = findOrganization(organizationId);
      if (!organization || canUsePremiumOrgFeatures(organization)) {
        return;
      }

      row.classList.add('organizations_row_premium_locked');

      const chip = document.createElement('span');
      chip.className = 'organizations_premium_chip';
      chip.textContent = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_PREMIUM_LOCKED')); ?>';
      firstCell.appendChild(chip);
    });
  };

  const clearInviteTokenFromURL = () => {
    try {
      const current = new URL(window.location.href);
      current.searchParams.delete('org_invite_token');
      const next = `${current.pathname}${current.search}${current.hash}`;
      window.history.replaceState({}, '', next);
    } catch (error) {
      PW.error(error);
    }
  };

  const acceptOrganizationInviteToken = async (token) => {
    if (token === '') {
      return false;
    }

    const consentContext = await promptMembershipConsent('Accept organization invite');
    if (consentContext === null) {
      clearInviteTokenFromURL();
      return false;
    }

    try {
      await postForm('/api/v1/organizations/invites/accept', {
        invite_token: token,
        ...consentContext,
      });
      PC.showToast(T.inviteAccepted, 'save', 9000, true);
      clearInviteTokenFromURL();
      return true;
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.inviteAcceptFailed, 'error', 9000, true);
      clearInviteTokenFromURL();
      return false;
    }
  };

  const syncGridDataset = (grid) => {
    if (!grid) {
      return;
    }

    grid.dataset.search = state.grid.search;
    grid.dataset.sort = state.grid.sort;
    grid.dataset.direction = state.grid.direction;
    grid.dataset.page = state.grid.page;
    syncGridSortA11y(grid);
  };

  const syncGridSortA11y = (grid) => {
    if (!(grid instanceof HTMLElement)) {
      return;
    }

    const activeColumn = String(state.grid.sort || '').trim();
    const activeDirection = String(state.grid.direction || 'asc').toLowerCase();

    grid.querySelectorAll('.datagrid_sort[data-column]').forEach((button) => {
      if (!(button instanceof HTMLElement)) {
        return;
      }

      const column = String(button.dataset.column || '').trim();
      const isActive = column !== '' && column === activeColumn;
      const ariaSort = isActive ? (activeDirection === 'desc' ? 'descending' : 'ascending') : 'none';

      button.setAttribute('aria-sort', ariaSort);

      const headerCell = button.closest('th, [role="columnheader"]');
      if (headerCell instanceof HTMLElement) {
        headerCell.setAttribute('aria-sort', ariaSort);
      }
    });
  };

  const loadGrid = async (overrides = {}) => {
    if (!elements.gridBody) {
      return;
    }

    state.grid = {
      ...state.grid,
      ...overrides,
    };

    const params = new URLSearchParams({
      search: state.grid.search,
      sort: state.grid.sort,
      direction: state.grid.direction,
      page: state.grid.page,
    });

    const changeReason = Object.prototype.hasOwnProperty.call(overrides, 'search')
      ? 'updated after search'
      : Object.prototype.hasOwnProperty.call(overrides, 'sort')
        ? 'updated after sorting'
        : Object.prototype.hasOwnProperty.call(overrides, 'page')
          ? 'updated after page change'
          : 'loaded';

    try {
      const payload = await apiRequest(`/api/v1/organizations/lists?${params.toString()}`);
      const html = typeof payload.html === 'string' ? payload.html : '';

      if (html === '') {
        resetInlineDeleteConfirm();
        setGridMessage(T.none);
        announceGridStatus(changeReason);
        return;
      }

      resetInlineDeleteConfirm();
      Guardian.setHTML(elements.gridBody, html);
      const grid = elements.gridContainer?.querySelector('.datagrid[data-grid="organizations"]');
      syncGridDataset(grid);
      decorateGridRowsForPremiumLocks();
      announceGridStatus(changeReason);
    } catch (error) {
      PW.error(error);
      setGridMessage(T.loadOrgsFailed);
      announceGridStatus('failed to load');
    }
  };

  const loadOrganizations = async () => {
    const payload = await apiRequest('/api/v1/organizations');
    state.organizations = Array.isArray(payload.organizations) ? payload.organizations : [];
    renderCurrentOrganizationPanel();
    return state.organizations;
  };

  const closeDialog = () => {
    stopRealtimeAuditPolling();
    state.auditRealtimeReady = false;
    state.auditRealtimeTopEventId = '';
    if (state.discoveryIntervalId !== null) {
      window.clearInterval(state.discoveryIntervalId);
      state.discoveryIntervalId = null;
    }
    state.discoverySignature = '';
    closeContactImagePopover();
    if (elements.dialog instanceof HTMLDialogElement && elements.dialog.open) {
      elements.dialog.close();
    }
  };

  const openDialog = () => {
    if (elements.dialog instanceof HTMLDialogElement && !elements.dialog.open) {
      elements.dialog.showModal();
    }
  };

  const setTransferAvailability = (organization) => {
    const premiumLocked = !canUsePremiumOrgFeatures(organization);
    const organizationType = String(organization?.organization_type || 'shared').toLowerCase();
    const role = String(organization?.role || '').toLowerCase();
    const canTransfer = !premiumLocked && organizationType !== T.personal && role === T.owner;
    const canLeave = role !== T.owner;

    if (elements.transferButton instanceof HTMLButtonElement) {
      elements.transferButton.disabled = !canTransfer;
    }
    if (elements.leaveButton instanceof HTMLButtonElement) {
      elements.leaveButton.disabled = !canLeave;
    }
    if (elements.transferNotice) {
      if (premiumLocked && !canLeave) {
        elements.transferNotice.textContent = T.premiumAdminLockedDetailed;
      } else {
        elements.transferNotice.textContent = canTransfer
          ? '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_TRANSFER_SELECT_MEMBER')); ?>'
          : '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_TRANSFER_NOTICE')); ?>';
      }
    }

    setTransferInputLocked(state.transferSelectedUUID !== '');
  };

  const setEditorMeta = (organization) => {
    if (elements.title) {
      elements.title.textContent = decodePossiblyEncodedText(String(organization?.name || '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS')); ?>'));
    }
    if (elements.subtitle) {
      const type = toTitleLabel(organization?.organization_type, 'Shared');
      const normalizedRole = String(organization?.role || '').trim().toLowerCase();
      const role = T[normalizedRole] || T.member;
      const status = toTitleLabel(organization?.status, 'Active');
      elements.subtitle.textContent = `${type} organization | ${role} | ${status}`;
    }
    if (elements.orgId instanceof HTMLInputElement) {
      elements.orgId.value = String(organization?.organization_id || '');
    }
    if (elements.type instanceof HTMLInputElement) {
      elements.type.value = String(organization?.organization_type || 'shared');
    }
    if (elements.type instanceof HTMLSelectElement) {
      setSelectValueSafe(elements.type, organization?.organization_type, 'shared');
    }
    if (elements.role instanceof HTMLInputElement) {
      elements.role.value = String(organization?.role || 'member');
    }
    if (elements.role instanceof HTMLSelectElement) {
      setSelectValueSafe(elements.role, organization?.role, 'member');
    }
    if (elements.status instanceof HTMLInputElement) {
      elements.status.value = String(organization?.status || 'active');
    }
    if (elements.status instanceof HTMLSelectElement) {
      setSelectValueSafe(elements.status, organization?.status, 'active');
    }
    updateDomainPolicyStatus();
    syncEditorRiskBaselineFromInputs();
    setTransferAvailability(organization);
    setPremiumLockedState(organization);
    populateOrgDetails(organization);
  };

  const populateOrgDetails = (organization) => {
    const fields = [
      { id: 'organizations_detail_name', value: organization?.name },
      { id: 'organizations_detail_contact_email', value: organization?.contact_email },
      { id: 'organizations_detail_contact_phone', value: organization?.contact_phone },
      { id: 'organizations_detail_website', value: organization?.website },
      { id: 'organizations_detail_address_line1', value: organization?.address_line1 },
      { id: 'organizations_detail_address_line2', value: organization?.address_line2 },
      { id: 'organizations_detail_address_city', value: organization?.address_city },
      { id: 'organizations_detail_address_region', value: organization?.address_region },
      { id: 'organizations_detail_address_postal', value: organization?.address_postal },
      { id: 'organizations_detail_address_country', value: organization?.address_country },
    ];

    fields.forEach(({ id, value }) => {
      const element = document.getElementById(id);
      if (element instanceof HTMLElement) {
        element.textContent = String(value || '').trim();
      }
    });
  };

  const parseDate = (value) => {
    if (typeof value !== 'string' || value.trim() === '') {
      return null;
    }

    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime()) ? null : date;
  };

  const addDays = (date, days) => {
    const next = new Date(date.getTime());
    next.setDate(next.getDate() + days);
    return next;
  };

  const addMonths = (date, months) => {
    const next = new Date(date.getTime());
    next.setMonth(next.getMonth() + months);
    return next;
  };

  const formatYmd = (date) => {
    return date.toISOString().slice(0, 10);
  };

  const formatDateLabel = (date) => {
    return new Intl.DateTimeFormat(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }).format(date);
  };

  const periodEndExclusive = (start, frequency) => {
    switch (frequency) {
      case 'weekly':
        return addDays(start, 7);
      case 'semimonthly':
        return addDays(start, 15);
      case 'monthly':
        return addMonths(start, 1);
      case 'biweekly':
      default:
        return addDays(start, 14);
    }
  };

  const getEditorPayAnchor = () => {
    if (elements.payAnchor instanceof HTMLInputElement || elements.payAnchor instanceof HTMLSelectElement) {
      return String(elements.payAnchor.value || 'Monday');
    }

    return 'Monday';
  };

  const setEditorPayAnchor = (value) => {
    if (elements.payAnchor instanceof HTMLInputElement || elements.payAnchor instanceof HTMLSelectElement) {
      elements.payAnchor.value = value;
    }
  };

  const getEditorEditingGraceDays = () => {
    const selected = document.querySelector('input[name="organizations_editor_editing_grace_days"]:checked');
    if (selected instanceof HTMLInputElement) {
      return String(selected.value || '0');
    }

    return '0';
  };

  const setEditorEditingGraceDays = (value) => {
    const normalized = String(value || '0');
    let matched = false;

    Array.from(elements.editorEditingGraceDayRadios).forEach((radio) => {
      if (!(radio instanceof HTMLInputElement)) {
        return;
      }

      const isTarget = radio.value === normalized;
      radio.checked = isTarget;
      if (isTarget) {
        matched = true;
      }
    });

    if (!matched) {
      const fallback = document.getElementById('organizations_editor_grace_0');
      if (fallback instanceof HTMLInputElement) {
        fallback.checked = true;
      }
    }
  };

  const syncPayPeriodLength = () => {
    if (!(elements.payFrequency instanceof HTMLSelectElement) || !(elements.payPeriodLength instanceof HTMLInputElement)) {
      return;
    }

    const nextLength = FREQUENCY_LENGTHS[elements.payFrequency.value] || FREQUENCY_LENGTHS.biweekly;
    elements.payPeriodLength.value = nextLength;
  };

  const renderPreview = () => {
    if (!elements.preview) {
      return;
    }

    syncPayPeriodLength();

    const parseYmd = (ymd) => new Date(`${ymd}T00:00:00`);
    const ppAddDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
    const ppFormatYmd = (date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };
    const alignToAnchor = (start, anchorDay) => {
      const target = PAY_PERIOD_WEEKDAY_MAP[anchorDay] ?? 1;
      let cursor = new Date(start.getFullYear(), start.getMonth(), start.getDate());
      while (cursor.getDay() !== target) {
        cursor = ppAddDays(cursor, -1);
      }
      return cursor;
    };
    const nextPeriod = (start, periodFrequency) => {
      if (periodFrequency === 'weekly') {
        return ppAddDays(start, 7);
      }
      if (periodFrequency === 'biweekly') {
        return ppAddDays(start, 14);
      }
      if (periodFrequency === 'semimonthly') {
        if (start.getDate() <= 15) {
          return new Date(start.getFullYear(), start.getMonth(), 16);
        }
        return new Date(start.getFullYear(), start.getMonth() + 1, 1);
      }

      return new Date(start.getFullYear(), start.getMonth() + 1, 1);
    };
    const currentPeriod = (startYmd, periodFrequency, anchorDay) => {
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

      if (periodFrequency === 'weekly') {
        const start = alignToAnchor(today, anchorDay);
        return { start, endExclusive: ppAddDays(start, 7) };
      }
      if (periodFrequency === 'biweekly') {
        const start = parseYmd(startYmd);
        return { start, endExclusive: ppAddDays(start, 14) };
      }
      if (periodFrequency === 'semimonthly') {
        if (today.getDate() <= 15) {
          const start = new Date(today.getFullYear(), today.getMonth(), 1);
          return { start, endExclusive: new Date(today.getFullYear(), today.getMonth(), 16) };
        }

        const start = new Date(today.getFullYear(), today.getMonth(), 16);
        return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
      }

      const start = new Date(today.getFullYear(), today.getMonth(), 1);
      return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
    };
    const startOfWeek = (date) => ppAddDays(date, -date.getDay());
    const inRange = (date, start, endExclusive) => date >= start && date < endExclusive;
    const monthLabel = (date) => date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    const buildRibbonCalendar = (periods, grace, today) => {
      const stripbar = PAY_PERIOD_DAY_NAMES.map((day) => `<span class="pp_day_head">${day}</span>`).join('');
      const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
      const gridStart = startOfWeek(firstOfMonth);
      const badgesPlaced = { p1: false, p2: false };
      let bodyRows = '';

      for (let week = 0; week < 6; week += 1) {
        bodyRows += '<tr>';
        for (let day = 0; day < 7; day += 1) {
          const offset = (week * 7) + day;
          const cellDate = ppAddDays(gridStart, offset);
          const isToday = ppFormatYmd(cellDate) === ppFormatYmd(today);
          const classes = ['pp_day_cell'];
          let badge = '';

          periods.forEach((period, index) => {
            const periodKey = index === 0 ? 'p1' : 'p2';
            const prevDate = ppAddDays(cellDate, -1);
            const nextDate = ppAddDays(cellDate, 1);
            const active = inRange(cellDate, period.start, period.endExclusive);
            const prevActive = inRange(prevDate, period.start, period.endExclusive);
            const nextActive = inRange(nextDate, period.start, period.endExclusive);
            const graceStart = period.endExclusive;
            const graceEndExclusive = ppAddDays(graceStart, grace);
            const graceActive = inRange(cellDate, graceStart, graceEndExclusive);

            if (active) {
              classes.push('pp_in_period', `pp_in_${periodKey}`);
              if (!prevActive) {
                classes.push(`pp_ribbon_start_${periodKey}`);
              }
              if (!nextActive) {
                classes.push(`pp_ribbon_end_${periodKey}`);
              }
              if (!badgesPlaced[periodKey]) {
                badge = `<span class="pp_badge ${periodKey === 'p2' ? 'pp_badge_p2' : ''}">${period.label}</span>`;
                badgesPlaced[periodKey] = true;
              }
            }

            if (graceActive && grace > 0) {
              const graceIndex = Math.min(grace, Math.max(1, Math.floor((cellDate - graceStart) / 86400000) + 1));
              classes.push('pp_grace_day', `pp_grace_${graceIndex}`, `pp_grace_${periodKey}`);
            }
          });

          if (isToday) {
            classes.push('pp_today');
          }

          bodyRows += `<td class="${classes.join(' ')}" data-ymd="${ppFormatYmd(cellDate)}" tabindex="0"><span class="pp_day_number">${String(cellDate.getDate()).padStart(2, '0')}</span>${badge}</td>`;
        }
        bodyRows += '</tr>';
      }

      return `
        <div class="pp_month_label">${monthLabel(today)}</div>
        <div class="pp_stripbar">${stripbar}</div>
        <table class="pp_three_week">
          <tbody>${bodyRows}</tbody>
        </table>
      `;
    };

    const startRaw = elements.payPeriodStart instanceof HTMLInputElement ? String(elements.payPeriodStart.value || '') : '';
    const frequency = elements.payFrequency instanceof HTMLSelectElement ? elements.payFrequency.value : 'biweekly';
    const anchor = getEditorPayAnchor();
    const graceDays = getEditorEditingGraceDays();
    const now2 = new Date();
    const today2 = new Date(now2.getFullYear(), now2.getMonth(), now2.getDate());
    const startValue = startRaw !== '' ? startRaw : ppFormatYmd(alignToAnchor(today2, anchor));

    if (elements.payPeriodStart instanceof HTMLInputElement && elements.payPeriodStart.value === '') {
      elements.payPeriodStart.value = startValue;
    }

    if (startValue === '') {
      elements.preview.textContent = T.previewEmpty;
      if (elements.payPeriodGridStatus instanceof HTMLElement) {
        elements.payPeriodGridStatus.textContent = T.previewEmpty;
      }
      return;
    }

    const period1 = currentPeriod(startValue, frequency, anchor);
    const period2 = {
      start: period1.endExclusive,
      endExclusive: nextPeriod(period1.endExclusive, frequency),
    };
    const endInclusive1 = ppAddDays(period1.endExclusive, -1);
    const endInclusive2 = ppAddDays(period2.endExclusive, -1);
    const periods = [
      { label: 'P1', start: period1.start, endExclusive: period1.endExclusive },
      { label: 'P2', start: period2.start, endExclusive: period2.endExclusive },
    ];
    const today = new Date();
    const graceDaysInt = parseInt(graceDays, 10);
    const safeGraceDays = Number.isNaN(graceDaysInt) ? 0 : graceDaysInt;

    Guardian.setHTML(elements.preview, `
      ${buildRibbonCalendar(periods, safeGraceDays, today)}
      <div class="pp_preview_summary"><span class="pp_preview_summary_item">P1: ${ppFormatYmd(period1.start)} to ${ppFormatYmd(endInclusive1)}</span><span class="pp_preview_summary_item">P2: ${ppFormatYmd(period2.start)} to ${ppFormatYmd(endInclusive2)}</span></div>
    `);

    if (elements.payPeriodGridStatus instanceof HTMLElement) {
      elements.payPeriodGridStatus.textContent = `Pay period preview updated. P1 ${ppFormatYmd(period1.start)} to ${ppFormatYmd(endInclusive1)}. P2 ${ppFormatYmd(period2.start)} to ${ppFormatYmd(endInclusive2)}.`;
    }
  };

  const handleEditorPreviewInteraction = (event) => {
    const target = event.target instanceof Element
      ? event.target.closest('.pp_day_cell[data-ymd]')
      : null;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const selectedYmd = String(target.dataset.ymd || '');
    if (selectedYmd === '') {
      return;
    }

    const selectedDate = new Date(`${selectedYmd}T00:00:00`);
    if (Number.isNaN(selectedDate.getTime())) {
      return;
    }

    const selectedAnchor = PAY_PERIOD_CANONICAL_WEEKDAY_NAMES[selectedDate.getDay()] || 'Monday';

    if (elements.payPeriodStart instanceof HTMLInputElement) {
      elements.payPeriodStart.value = selectedYmd;
    }
    setEditorPayAnchor(selectedAnchor);
    renderPreview();
    scheduleEditorAutoSave(220, 'calendar-day');
  };

  const hydrateSettings = (payload, organization) => {
    state.editorHydrating = true;
    const settings = payload && typeof payload === 'object' && payload.settings && typeof payload.settings === 'object'
      ? payload.settings
      : {};

    renderOwnerSummary(payload, organization);

    if (elements.name instanceof HTMLInputElement) {
      elements.name.value = decodePossiblyEncodedText(String((payload.organization && payload.organization.name) || organization?.name || ''));
    }
    if (elements.defaultWage instanceof HTMLInputElement) {
      elements.defaultWage.value = String(settings.default_wage || '');
    }
    if (elements.timezone instanceof HTMLInputElement) {
      elements.timezone.value = String(settings.timezone || '');
      displayTimezoneValue(elements.timezoneSearch, String(settings.timezone || ''));
    }
    if (elements.currency instanceof HTMLInputElement) {
      elements.currency.value = String(settings.currency || '');
      displayCurrencyValue(elements.currencySearch, String(settings.currency || ''));
    }
    if (elements.payFrequency instanceof HTMLSelectElement) {
      elements.payFrequency.value = String(settings.pay_frequency || 'biweekly');
    }
    setEditorPayAnchor(String(settings.pay_anchor || 'Monday'));
    if (elements.payPeriodStart instanceof HTMLInputElement) {
      elements.payPeriodStart.value = String(settings.pay_period_start || '');
    }
    if (elements.payPeriodLength instanceof HTMLInputElement) {
      elements.payPeriodLength.value = String(settings.pay_period_length || FREQUENCY_LENGTHS.biweekly);
    }
    setEditorEditingGraceDays(String(settings.editing_grace_days || '0'));

    Object.entries(EDITOR_META_FIELD_MAP).forEach(([fieldId, payloadKey]) => {
      setEditorFieldValueById(fieldId, String(settings[payloadKey] || ''));
    });

    formatPhoneInputsWithin(elements.dialog ?? document);

    renderPreview();
    state.editorHydrating = false;
    updateDomainPolicyStatus();
    syncEditorRiskBaselineFromInputs();
    state.editorLastSavedSignature = buildEditorPayloadSignature(collectOrganizationEditorPayload());
  };

  const formatInviteTimestamp = (value) => {
    const raw = String(value || '').trim();
    if (raw === '') {
      return 'unknown';
    }

    const parsed = new Date(raw);
    if (Number.isNaN(parsed.getTime())) {
      return raw;
    }

    return parsed.toLocaleString();
  };

  const renderOwnerSummary = (payload, organization) => {
    if (!(elements.ownerSummary instanceof HTMLElement)) {
      return;
    }

    const payloadOrg = payload && typeof payload === 'object' && payload.organization && typeof payload.organization === 'object'
      ? payload.organization
      : {};
    const ownerName = String(payloadOrg.owner_name || organization?.owner_name || '').trim();
    const ownerEmail = String(payloadOrg.owner_email || organization?.owner_email || '').trim();
    const ownerPhone = formatPhoneDisplayValue(String(payloadOrg.owner_phone || '').trim());
    const ownerSinceRaw = String(payloadOrg.owner_since || '').trim();
    const ownerSince = ownerSinceRaw === '' ? 'Unavailable' : formatInviteTimestamp(ownerSinceRaw);

    const rows = [
      ['Name', ownerName !== '' ? ownerName : 'Unavailable'],
      ['Email', ownerEmail !== '' ? ownerEmail : 'Unavailable'],
      ['Phone', ownerPhone !== '' ? ownerPhone : 'Unavailable'],
      ['Since', ownerSince],
    ];

    Guardian.setHTML(elements.ownerSummary, rows.map(([label, value]) => `
      <div class="organizations_owner_summary_item">
        <span>${safeText(label)}</span>
        <strong>${safeText(value)}</strong>
      </div>
    `).join(''));
  };

  const parseHistoryTimestampValue = (value) => {
    if (typeof value !== 'string' || value.trim() === '') {
      return null;
    }

    const trimmed = value.trim();
    const parsed = new Date(trimmed);
    if (!Number.isNaN(parsed.getTime())) {
      return parsed;
    }

    const dateTimeMatch = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})$/.exec(trimmed);
    if (!dateTimeMatch) {
      return null;
    }

    const asUtc = new Date(`${dateTimeMatch[1]}T${dateTimeMatch[2]}Z`);
    return Number.isNaN(asUtc.getTime()) ? null : asUtc;
  };

  const formatTimestampInTimeZone = (dateValue, timeZone) => {
    if (!(dateValue instanceof Date) || Number.isNaN(dateValue.getTime())) {
      return 'Unavailable';
    }

    try {
      const options = {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      };

      const normalizedZone = typeof timeZone === 'string' && timeZone.trim() !== ''
        ? timeZone.trim()
        : undefined;

      const formatter = normalizedZone
        ? new Intl.DateTimeFormat('en-US', { ...options, timeZone: normalizedZone })
        : new Intl.DateTimeFormat('en-US', options);

      const parts = formatter.formatToParts(dateValue);
      const data = {};
      parts.forEach((part) => {
        if (part.type !== 'literal') {
          data[part.type] = part.value;
        }
      });

      const mm = String(data.month || '00').padStart(2, '0');
      const dd = String(data.day || '00').padStart(2, '0');
      const yy = String(data.year || '00').slice(-2).padStart(2, '0');
      const hh = String(data.hour || '00').padStart(2, '0');
      const min = String(data.minute || '00').padStart(2, '0');

      return `${mm}/${dd}/${yy} ${hh}:${min}`;
    } catch {
      // Fall back to viewer locale timezone when timezone is unavailable.
      const mm = String(dateValue.getMonth() + 1).padStart(2, '0');
      const dd = String(dateValue.getDate()).padStart(2, '0');
      const yy = String(dateValue.getFullYear()).slice(-2);
      const hh = String(dateValue.getHours()).padStart(2, '0');
      const min = String(dateValue.getMinutes()).padStart(2, '0');
      return `${mm}/${dd}/${yy} ${hh}:${min}`;
    }
  };

  const viewerTimeZone = (() => {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local';
    } catch {
      return 'Local';
    }
  })();

  let openHistoryTimestampPopover = null;

  const positionHistoryTimestampPopover = (trigger, popover) => {
    if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    const margin = 8;
    const triggerRect = trigger.getBoundingClientRect();

    popover.style.position = 'fixed';
    popover.style.right = 'auto';
    popover.style.left = '0px';
    popover.style.top = '0px';

    const popoverWidth = Math.ceil(popover.offsetWidth || 0);
    const popoverHeight = Math.ceil(popover.offsetHeight || 0);

    let left = triggerRect.left;
    if (popoverWidth > 0) {
      left = Math.min(left, window.innerWidth - popoverWidth - margin);
    }
    left = Math.max(margin, left);

    const maxTop = Math.max(margin, window.innerHeight - popoverHeight - margin);
    const preferredBelowTop = triggerRect.bottom + 6;
    const preferredAboveTop = triggerRect.top - popoverHeight - 6;

    let top = preferredBelowTop;
    if (popoverHeight > 0) {
      if (preferredBelowTop <= maxTop) {
        top = preferredBelowTop;
      } else if (preferredAboveTop >= margin) {
        top = preferredAboveTop;
      } else {
        top = Math.min(preferredBelowTop, maxTop);
      }
    }
    top = Math.max(margin, top);

    popover.style.left = `${Math.round(left)}px`;
    popover.style.top = `${Math.round(top)}px`;
  };

  const closeHistoryTimestampPopover = ({ restoreFocus = false } = {}) => {
    if (!openHistoryTimestampPopover) {
      return;
    }

    const { trigger, popover, homeParent } = openHistoryTimestampPopover;
    if (trigger instanceof HTMLElement) {
      trigger.setAttribute('aria-expanded', 'false');
      if (restoreFocus && typeof trigger.focus === 'function') {
        trigger.focus();
      }
    }
    if (popover instanceof HTMLElement) {
      popover.hidden = true;
      popover.style.left = '';
      popover.style.top = '';
      popover.style.right = '';
      popover.style.position = '';

      if (homeParent instanceof Node && homeParent.isConnected) {
        homeParent.appendChild(popover);
      } else if (trigger instanceof HTMLElement && trigger.parentElement instanceof Node) {
        trigger.parentElement.appendChild(popover);
      }
    }

    openHistoryTimestampPopover = null;
  };

  const openHistoryTimestampPopoverFor = (trigger, popover) => {
    if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    if (
      openHistoryTimestampPopover
      && (openHistoryTimestampPopover.trigger !== trigger || openHistoryTimestampPopover.popover !== popover)
    ) {
      closeHistoryTimestampPopover({ restoreFocus: false });
    }

    const homeParent = popover.parentElement;
    const portalParent = trigger.closest('dialog[open]') || document.body;
    if (popover.parentElement !== portalParent) {
      portalParent.appendChild(popover);
    }

    popover.hidden = false;
    positionHistoryTimestampPopover(trigger, popover);
    window.requestAnimationFrame(() => {
      positionHistoryTimestampPopover(trigger, popover);
    });
    trigger.setAttribute('aria-expanded', 'true');
    openHistoryTimestampPopover = { trigger, popover, homeParent };
  };

  const bindHistoryTimestampPopover = (container, trigger, popover) => {
    if (!(container instanceof HTMLElement) || !(trigger instanceof HTMLButtonElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      if (!popover.hidden && openHistoryTimestampPopover?.trigger === trigger) {
        closeHistoryTimestampPopover({ restoreFocus: false });
      } else {
        openHistoryTimestampPopoverFor(trigger, popover);
      }
    });

    trigger.addEventListener('mouseenter', () => {
      openHistoryTimestampPopoverFor(trigger, popover);
    });

    trigger.addEventListener('focus', () => {
      openHistoryTimestampPopoverFor(trigger, popover);
    });

    trigger.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (!popover.hidden && openHistoryTimestampPopover?.trigger === trigger) {
          closeHistoryTimestampPopover({ restoreFocus: false });
        } else {
          openHistoryTimestampPopoverFor(trigger, popover);
        }
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closeHistoryTimestampPopover({ restoreFocus: true });
      }
    });

    trigger.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && popover.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    trigger.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && popover.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    popover.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && trigger.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    popover.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && (trigger.contains(nextTarget) || popover.contains(nextTarget))) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    if (!container.dataset.historyTimestampPopoverGlobalBound) {
      document.addEventListener('pointerdown', (event) => {
        if (!openHistoryTimestampPopover) {
          return;
        }

        const target = event.target;
        if (!(target instanceof Node)) {
          return;
        }

        if (
          openHistoryTimestampPopover.trigger.contains(target)
          || openHistoryTimestampPopover.popover.contains(target)
        ) {
          return;
        }

        closeHistoryTimestampPopover({ restoreFocus: false });
      });

      document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !openHistoryTimestampPopover) {
          return;
        }
        closeHistoryTimestampPopover({ restoreFocus: true });
      });

      window.addEventListener('scroll', () => {
        if (!openHistoryTimestampPopover) {
          return;
        }
        positionHistoryTimestampPopover(openHistoryTimestampPopover.trigger, openHistoryTimestampPopover.popover);
      }, true);

      window.addEventListener('resize', () => {
        if (!openHistoryTimestampPopover) {
          return;
        }
        positionHistoryTimestampPopover(openHistoryTimestampPopover.trigger, openHistoryTimestampPopover.popover);
      });

      container.dataset.historyTimestampPopoverGlobalBound = '1';
    }
  };

  const enhanceInviteHistoryTimestampCells = () => {
    if (!(elements.membersInviteHistoryGridContainer instanceof HTMLElement)) {
      return;
    }

    const gridEl = elements.membersInviteHistoryGridContainer.querySelector('[data-grid="organizations-invite-history-grid"]');
    if (!(gridEl instanceof HTMLElement)) {
      return;
    }

    const headerSort = gridEl.querySelector('.datagrid_sort[data-column="resolved_at"]');
    const headerCell = headerSort instanceof HTMLElement ? headerSort.closest('.datagrid_heading') : null;
    const headerId = headerCell instanceof HTMLElement ? String(headerCell.id || '') : '';

    let timestampCells = [];
    if (headerId !== '') {
      timestampCells = Array.from(gridEl.querySelectorAll(`.datagrid_item[aria-labelledby="${headerId}"]`));
    }

    if (timestampCells.length === 0) {
      timestampCells = Array.from(gridEl.querySelectorAll('.datagrid_row .datagrid_item:nth-child(4)'));
    }

    timestampCells.forEach((cell, index) => {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      if (cell.closest('.datagrid_row_empty')) {
        return;
      }

      cell.classList.add('organizations_history_timestamp_cell');

      if (cell.dataset.timePopoverBound === '1') {
        return;
      }

      const rawValue = String(cell.textContent || '').trim();
      if (rawValue === '') {
        return;
      }

      const parsedDate = parseHistoryTimestampValue(rawValue);
      const displayText = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
      const rowId = String(cell.closest('.datagrid_row')?.getAttribute('data-id') || `row_${index}`);
      const safeRowId = rowId.replace(/[^a-zA-Z0-9_-]/g, '_');
      const popoverId = `organizations_history_timestamp_popover_${safeRowId}_${index}`;

      const field = document.createElement('span');
      field.className = 'organizations_history_timestamp_field';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'organizations_history_timestamp_trigger';
      trigger.textContent = displayText;
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-controls', popoverId);
      trigger.setAttribute('aria-expanded', 'false');

      const popover = document.createElement('div');
      popover.id = popoverId;
      popover.className = 'organizations_history_timestamp_popover';
      popover.hidden = true;
      popover.setAttribute('role', 'dialog');
      popover.setAttribute('aria-label', 'Timestamp details');

      const rows = [
        {
          label: 'Local',
          value: formatTimestampInTimeZone(parsedDate, viewerTimeZone),
        },
        {
          label: 'Server',
          value: formatTimestampInTimeZone(parsedDate, SERVER_TIMEZONE),
        },
        {
          label: 'UTC',
          value: formatTimestampInTimeZone(parsedDate, 'UTC'),
        },
      ];

      if (!(parsedDate instanceof Date) || Number.isNaN(parsedDate.getTime())) {
        rows[1].value = rawValue;
      }

      rows.forEach((row) => {
        const rowEl = document.createElement('span');
        rowEl.className = 'organizations_history_timestamp_popover_row';

        const labelEl = document.createElement('span');
        labelEl.className = 'organizations_history_timestamp_popover_label';
        labelEl.textContent = `${row.label}:`;

        const valueEl = document.createElement('span');
        valueEl.className = 'organizations_history_timestamp_popover_value';
        valueEl.textContent = row.value;

        rowEl.appendChild(labelEl);
        rowEl.appendChild(valueEl);
        popover.appendChild(rowEl);
      });

      field.appendChild(trigger);
      field.appendChild(popover);

      cell.textContent = '';
      cell.appendChild(field);
      cell.dataset.timePopoverBound = '1';

      bindHistoryTimestampPopover(elements.membersInviteHistoryGridContainer, trigger, popover);
    });
  };

  const enhanceMembersJoinedTimestampCells = () => {
    if (!(elements.membersGridContainer instanceof HTMLElement)) {
      return;
    }

    const gridEl = elements.membersGridContainer.querySelector('[data-grid="organization-members"]');
    if (!(gridEl instanceof HTMLElement)) {
      return;
    }

    const headerSort = gridEl.querySelector('.datagrid_sort[data-column="joined_at"]');
    const headerCell = headerSort instanceof HTMLElement ? headerSort.closest('.datagrid_heading') : null;
    const headerId = headerCell instanceof HTMLElement ? String(headerCell.id || '') : '';

    let timestampCells = [];
    if (headerId !== '') {
      timestampCells = Array.from(gridEl.querySelectorAll(`.datagrid_item[aria-labelledby="${headerId}"]`));
    }

    if (timestampCells.length === 0) {
      timestampCells = Array.from(gridEl.querySelectorAll('.datagrid_row .datagrid_item:nth-child(5)'));
    }

    timestampCells.forEach((cell, index) => {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      if (cell.closest('.datagrid_row_empty')) {
        return;
      }

      cell.classList.add('organizations_history_timestamp_cell');

      if (cell.dataset.timePopoverBound === '1') {
        return;
      }

      const rawValue = String(cell.textContent || '').trim();
      if (rawValue === '') {
        return;
      }

      const parsedDate = parseHistoryTimestampValue(rawValue);
      const displayText = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
      const rowId = String(cell.closest('.datagrid_row')?.getAttribute('data-id') || `member_${index}`);
      const safeRowId = rowId.replace(/[^a-zA-Z0-9_-]/g, '_');
      const popoverId = `organizations_members_joined_popover_${safeRowId}_${index}`;

      const field = document.createElement('span');
      field.className = 'organizations_history_timestamp_field';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'organizations_history_timestamp_trigger';
      trigger.textContent = displayText;
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-controls', popoverId);
      trigger.setAttribute('aria-expanded', 'false');

      const popover = document.createElement('div');
      popover.id = popoverId;
      popover.className = 'organizations_history_timestamp_popover';
      popover.hidden = true;
      popover.setAttribute('role', 'dialog');
      popover.setAttribute('aria-label', 'Joined timestamp details');

      const rows = [
        {
          label: 'Local',
          value: formatTimestampInTimeZone(parsedDate, viewerTimeZone),
        },
        {
          label: 'Server',
          value: formatTimestampInTimeZone(parsedDate, SERVER_TIMEZONE),
        },
        {
          label: 'UTC',
          value: formatTimestampInTimeZone(parsedDate, 'UTC'),
        },
      ];

      if (!(parsedDate instanceof Date) || Number.isNaN(parsedDate.getTime())) {
        rows[1].value = rawValue;
      }

      rows.forEach((row) => {
        const rowEl = document.createElement('span');
        rowEl.className = 'organizations_history_timestamp_popover_row';

        const labelEl = document.createElement('span');
        labelEl.className = 'organizations_history_timestamp_popover_label';
        labelEl.textContent = `${row.label}:`;

        const valueEl = document.createElement('span');
        valueEl.className = 'organizations_history_timestamp_popover_value';
        valueEl.textContent = row.value;

        rowEl.appendChild(labelEl);
        rowEl.appendChild(valueEl);
        popover.appendChild(rowEl);
      });

      field.appendChild(trigger);
      field.appendChild(popover);

      cell.textContent = '';
      cell.appendChild(field);
      cell.dataset.timePopoverBound = '1';

      bindHistoryTimestampPopover(elements.membersGridContainer, trigger, popover);
    });
  };

  const deriveInviteRoleLabel = (invite) => {
    const explicitRole = String(invite?.role || '').trim().toLowerCase();
    if (explicitRole !== '') {
      return T[explicitRole] || T.member || 'member';
    }

    const scopes = Array.isArray(invite?.scopes)
      ? invite.scopes.map((scope) => String(scope || '').trim()).filter((scope) => scope !== '')
      : [];

    if (scopes.includes('access.manage') || scopes.includes('org.settings.write')) {
      return T.manager || 'manager';
    }
    if (scopes.includes('sites.write') || (scopes.includes('work.write') && scopes.includes('work.scope.org'))) {
      return T.contributor || 'contributor';
    }
    if (scopes.includes('work.self.write') || (scopes.includes('work.write') && scopes.includes('work.scope.self'))) {
      return T.member || 'member';
    }
    if (scopes.length > 0) {
      return T.viewer || 'viewer';
    }

    return T.member || 'member';
  };

  const renderInvites = (invites) => {
    const inviteTargets = [elements.invitesList, elements.membersInvitesList]
      .filter((target) => target instanceof HTMLElement);

    if (inviteTargets.length === 0) {
      return;
    }

    if (!Array.isArray(invites) || invites.length === 0) {
      inviteTargets.forEach((target) => setStackMessage(target, T.noInvites));
      announceInvitesStatus('Invites list loaded. No invites found.');
      return;
    }

    const invitesMarkup = invites.map((invite) => {
      const inviteId = String(invite.invite_id || '');
      const email = String(invite.invitee_email || T.unknown);
      const status = String(invite.status || T.pending);
      const roleLabel = deriveInviteRoleLabel(invite);
      const timestamp = formatInviteTimestamp(invite.created_at || '');
      const canRevoke = status === 'pending';

      return `
        <div class="organizations_stack_row organizations_stack_row_compact">
          <div class="organizations_stack_text">
            <span class="organizations_invite_compact_line">
              <strong>${email}</strong>
              <span class="organizations_invite_compact_meta">[${roleLabel}]</span>
              <span class="organizations_invite_compact_meta">[${timestamp}]</span>
            </span>
          </div>
          ${canRevoke ? `<button type="button" class="btn btn_delete" data-org-action="revoke-invite" data-invite-id="${inviteId}">${T.revoke}</button>` : ''}
        </div>
      `;
    }).join('');

    inviteTargets.forEach((target) => {
      target.classList.remove('organizations_empty');
      Guardian.setHTML(target, invitesMarkup);
    });

    announceInvitesStatus(`Invites list loaded. ${invites.length} invite${invites.length === 1 ? '' : 's'} shown.`);
  };

  const inviteHistoryGridEndpoint = (orgId) => {
    return `/api/v1/organizations/${encodeURIComponent(orgId)}/invites/history/grid`;
  };

  const ensureInviteHistoryGridManager = (orgId) => {
    if (
      state.inviteHistoryGridManager
      && state.inviteHistoryGridOrgId === orgId
    ) {
      return state.inviteHistoryGridManager;
    }

    if (state.inviteHistoryGridManager && typeof state.inviteHistoryGridManager.destroy === 'function') {
      state.inviteHistoryGridManager.destroy();
    }

    state.inviteHistoryGridManager = createDataGrid({
      id: 'organizations-invite-history-grid',
      containerId: 'organizations-invite-history-grid-host',
      endpoint: inviteHistoryGridEndpoint(orgId),
    });
    state.inviteHistoryGridOrgId = orgId;

    return state.inviteHistoryGridManager;
  };

  const loadOrganizationInviteHistoryGrid = async (organizationId) => {
    const organization = findOrganization(organizationId);
    if (organization && !canManageOrganizationAccess(organization)) {
      setDatagridMessage(elements.membersInviteHistoryGridContainer, ACCESS_MANAGE_WARNING);
      announceInvitesStatus(ACCESS_MANAGE_WARNING);
      return;
    }

    const manager = ensureInviteHistoryGridManager(organizationId);
    if (!manager) {
      throw new Error('Unable to initialize invite history grid manager.');
    }

    try {
      await manager.reload();
      enhanceInviteHistoryTimestampCells();
    } catch (error) {
      PW.error(error);
      setDatagridMessage(elements.membersInviteHistoryGridContainer, T.manageAccessUnavailable);
    }
  };

  const renderAccessRequests = (requests) => {
    if (!elements.accessRequestsList) {
      return;
    }

    if (!Array.isArray(requests) || requests.length === 0) {
      setStackMessage(elements.accessRequestsList, T.noAccessRequests);
      announceAccessRequestsStatus('Access requests loaded. No requests found.');
      return;
    }

    elements.accessRequestsList.classList.remove('organizations_empty');
    Guardian.setHTML(elements.accessRequestsList, requests.map((request) => {
      const requestId = String(request.request_id || '');
      const requester = String(request.requester_contact_email || request.requester_uuid || T.unknown);
      const status = String(request.status || T.pending);
      const createdAt = String(request.created_at || '');
      const canAct = status === 'pending';

      return `
        <div class="organizations_stack_row organizations_stack_row_hint">
          <div class="organizations_stack_text">
            <strong>${requester}</strong>
            <span>${status}${createdAt ? ` | ${createdAt}` : ''}${requestId ? ` | ${requestId}` : ''}</span>
          </div>
          ${canAct ? `
            <div class="organizations_actions_row">
              <button type="button" class="btn btn_secondary" data-org-action="approve-access-request" data-request-id="${requestId}">Approve</button>
              <button type="button" class="btn btn_delete" data-org-action="reject-access-request" data-request-id="${requestId}">Reject</button>
            </div>
          ` : ''}
        </div>
      `;
    }).join(''));
    announceAccessRequestsStatus(`Access requests loaded. ${requests.length} request${requests.length === 1 ? '' : 's'} shown.`);
  };

  const formatLiveRequestCreatedAt = (rawValue) => {
    const text = String(rawValue || '').trim();
    if (text === '') {
      return '';
    }

    const parsed = new Date(text);
    if (Number.isNaN(parsed.getTime())) {
      return text;
    }

    return parsed.toLocaleString();
  };

  const normalizeLiveRequestItems = (items) => {
    if (!Array.isArray(items)) {
      return [];
    }

    return items.map((item) => {
      const organizationId = String(item?.organization_id || '').trim();
      const requestId = String(item?.request_id || '').trim();
      const status = String(item?.status || 'pending').trim().toLowerCase();
      const requester = String(item?.requester_contact_email || item?.requester_uuid || T.unknown).trim();
      const organizationName = String(item?.organization_name || item?.organization_id || T.unknown).trim();
      const createdAt = String(item?.created_at || '').trim();

      return {
        organizationId,
        requestId,
        status,
        requester,
        organizationName,
        createdAt,
      };
    }).filter((item) => item.organizationId !== '' && item.requestId !== '' && item.status === 'pending');
  };

  const renderLiveRequestsPanel = (items) => {
    if (!(elements.liveRequestsList instanceof HTMLElement)) {
      return;
    }

    const normalizedItems = normalizeLiveRequestItems(items);
    if (normalizedItems.length === 0) {
      setLiveRequestsNotificationState(0);
      setStackMessage(elements.liveRequestsList, 'No pending access requests.');
      announceLiveRequestsStatus('Live requests loaded. No pending requests found.');
      return;
    }

    const markup = normalizedItems.map((item) => {
      const createdAtLabel = formatLiveRequestCreatedAt(item.createdAt);
      return `
        <div class="organizations_stack_row organizations_stack_row_hint">
          <div class="organizations_stack_text">
            <strong>${escapeHtml(item.requester)}</strong>
            <span>${escapeHtml(item.organizationName)}${createdAtLabel !== '' ? ` | ${escapeHtml(createdAtLabel)}` : ''}</span>
          </div>
          <div class="organizations_actions_row">
            <button type="button" class="btn btn_secondary" data-live-request-action="approve" data-live-org-id="${escapeHtml(item.organizationId)}" data-live-request-id="${escapeHtml(item.requestId)}">Approve</button>
            <button type="button" class="btn btn_delete" data-live-request-action="reject" data-live-org-id="${escapeHtml(item.organizationId)}" data-live-request-id="${escapeHtml(item.requestId)}">Reject</button>
          </div>
        </div>
      `;
    }).join('');

    elements.liveRequestsList.classList.remove('organizations_empty');
    Guardian.setHTML(elements.liveRequestsList, markup);
    setLiveRequestsNotificationState(normalizedItems.length);
    announceLiveRequestsStatus(`Live requests loaded. ${normalizedItems.length} pending request${normalizedItems.length === 1 ? '' : 's'} shown.`);
  };

  const fetchLiveRequestsSnapshot = async () => {
    const params = new URLSearchParams({
      channel: 'organization_requests_live',
    });

    if (state.liveRequestsSignature !== '') {
      params.set('since_signature', state.liveRequestsSignature);
    }

    const response = await fetch(`/ws/?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Live requests channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Live requests payload invalid.'));
    }

    return {
      items: Array.isArray(payload.pending_requests) ? payload.pending_requests : [],
      signature: String(payload.latest_signature || ''),
    };
  };

  const syncLiveRequestsPanel = async (notifyOnNew = false) => {
    if (!(elements.liveRequestsList instanceof HTMLElement)) {
      return;
    }

    const snapshot = await fetchLiveRequestsSnapshot();
    const items = normalizeLiveRequestItems(snapshot.items);

    const incomingIds = new Set();
    items.forEach((item) => {
      incomingIds.add(item.requestId);
    });

    if (notifyOnNew && state.liveRequestsReady && (hasActivePremiumSubscription || isElevatedStaffUser)) {
      let newCount = 0;
      incomingIds.forEach((requestId) => {
        if (!state.liveRequestsKnownIds.has(requestId)) {
          newCount += 1;
        }
      });

      if (newCount > 0) {
        showOrganizationsToast(`New access request${newCount === 1 ? '' : 's'} received${newCount > 1 ? ` (${newCount})` : ''}.`, 'save', 5000, true);
      }
    }

    state.liveRequestsKnownIds = incomingIds;
    state.liveRequestsReady = true;
    state.liveRequestsSignature = snapshot.signature;
    renderLiveRequestsPanel(items);
  };

  const stopLiveRequestsPolling = () => {
    if (state.liveRequestsIntervalId !== null) {
      window.clearInterval(state.liveRequestsIntervalId);
      state.liveRequestsIntervalId = null;
    }
  };

  const startLiveRequestsPolling = () => {
    stopLiveRequestsPolling();

    if (!(elements.liveRequestsList instanceof HTMLElement)) {
      return;
    }

    state.liveRequestsReady = false;
    state.liveRequestsSignature = '';
    state.liveRequestsKnownIds = new Set();

    syncLiveRequestsPanel(false).catch((error) => {
      PW.error(error);
      setStackMessage(elements.liveRequestsList, T.manageAccessUnavailable);
      announceLiveRequestsStatus('Live requests failed to load.');
    });

    state.liveRequestsIntervalId = window.setInterval(() => {
      syncLiveRequestsPanel(true).catch((error) => {
        PW.error(error);
        if (elements.liveRequestsList instanceof HTMLElement) {
          setStackMessage(elements.liveRequestsList, T.manageAccessUnavailable);
          announceLiveRequestsStatus('Live requests temporarily unavailable.');
        }
      });
    }, 5000);
  };

  const renderRelationships = (relationships) => {
    if (!(elements.transferTarget instanceof HTMLInputElement)) {
      return;
    }

    if (!(elements.transferTargetList instanceof HTMLDataListElement)) {
      return;
    }

    const previous = normalizeTransferLookupName(elements.transferTarget.value);
    const previousSelectedUUID = state.transferSelectedUUID;
    elements.transferTargetList.replaceChildren();
    state.transferCandidates = [];
    if (elements.transferTargetUUID instanceof HTMLInputElement) {
      elements.transferTargetUUID.value = '';
    }

    let count = 0;
    (Array.isArray(relationships) ? relationships : []).forEach((relationship) => {
      const userUUID = String(relationship.user_uuid || relationship.uuid || '').trim();
      const role = String(relationship.role || 'member').toLowerCase();
      const status = String(relationship.status || '').toLowerCase();
      if (userUUID === '' || role === 'owner' || status !== 'active') {
        return;
      }

      const displayName = deriveTransferCandidateDisplay(relationship);
      if (displayName === '') {
        return;
      }

      const option = document.createElement('option');
      option.value = displayName;
      elements.transferTargetList.appendChild(option);

      state.transferCandidates.push({
        userUUID,
        displayName,
        lookupKey: normalizeTransferLookupName(displayName),
        email: String(relationship.email || '').trim(),
        roleLabel: T[String(role || '').toLowerCase()] || T.member,
        statusLabel: status,
      });
      count += 1;
    });

    if (previousSelectedUUID !== '') {
      const selected = getTransferCandidateByUUID(previousSelectedUUID);
      if (selected) {
        applyTransferSelection(selected, false);
      } else {
        clearTransferSelection(true);
      }
    } else {
      renderTransferSelectedMember();
      setTransferInputLocked(false);
    }

    if (previousSelectedUUID === '' && previous !== '') {
      const match = state.transferCandidates.find((candidate) => candidate.lookupKey === previous);
      if (match) {
        elements.transferTarget.value = match.displayName;
        if (elements.transferTargetUUID instanceof HTMLInputElement) {
          elements.transferTargetUUID.value = match.userUUID;
        }
      } else {
        elements.transferTarget.value = '';
      }
    }

    if (elements.transferNotice) {
      if (count === 0) {
        elements.transferNotice.textContent = T.noRelationships;
      } else {
        elements.transferNotice.textContent = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_TRANSFER_SELECT_MEMBER')); ?>';
      }
    }
  };

  const auditGridEndpoint = (orgId) => {
    return `/api/v1/organizations/${encodeURIComponent(orgId)}/audit/grid`;
  };

  const freeAuditGridEndpoint = (orgId) => {
    return `/api/v1/organizations/${encodeURIComponent(orgId)}/audit/member/grid`;
  };

  const ensureAuditGridManager = (orgId) => {
    if (
      state.auditGridManager
      && state.auditGridOrgId === orgId
    ) {
      return state.auditGridManager;
    }

    if (state.auditGridManager && typeof state.auditGridManager.destroy === 'function') {
      state.auditGridManager.destroy();
    }

    state.auditGridManager = createDataGrid({
      id: 'organizations-audit-grid',
      containerId: 'organizations-audit-grid-host',
      endpoint: auditGridEndpoint(orgId),
    });
    state.auditGridOrgId = orgId;

    return state.auditGridManager;
  };

  const ensureFreeAuditGridManager = (orgId) => {
    if (
      state.freeAuditGridManager
      && state.freeAuditGridOrgId === orgId
    ) {
      return state.freeAuditGridManager;
    }

    if (state.freeAuditGridManager && typeof state.freeAuditGridManager.destroy === 'function') {
      state.freeAuditGridManager.destroy();
    }

    state.freeAuditGridManager = createDataGrid({
      id: 'organizations-free-audit-grid',
      containerId: 'organizations-free-audit-grid-host',
      endpoint: freeAuditGridEndpoint(orgId),
    });
    state.freeAuditGridOrgId = orgId;

    return state.freeAuditGridManager;
  };

  const SIGNAL_EVENT_LABELS = {
    'access.requested': T.signalAccessRequestReceived,
    'access.request.approved': T.signalAccessRequestApproved,
    'access.request.rejected': T.signalAccessRequestRejected,
    'invite.accepted': T.signalInviteAccepted,
    'invite.sent': T.signalInviteSent,
    'invite.revoked': T.signalInviteRevoked,
    'relationship.revoked': T.signalAccessRevoked,
    'relationship.withdrawn': T.signalMemberLeftOrganization,
    'ownership.transferred': T.signalOwnershipTransferred,
    'settings.updated': T.signalSettingsUpdated,
    'site.linked': T.signalSiteLinked,
  };

  const isSignalEventType = (eventType) => {
    return Object.prototype.hasOwnProperty.call(SIGNAL_EVENT_LABELS, String(eventType || ''));
  };

  const fetchOrganizationAuditEvents = async (organizationId) => {
    const payload = await apiRequest(`/api/v1/organizations/${encodeURIComponent(organizationId)}/audit`);
    return Array.isArray(payload.events) ? payload.events : [];
  };

  const fetchRealtimeAuditDelta = async (organizationId, sinceEventId = '') => {
    const params = new URLSearchParams({
      channel: 'organization_audit',
      organization_id: String(organizationId || ''),
    });
    if (sinceEventId !== '') {
      params.set('since_event_id', sinceEventId);
    }

    const response = await fetch(`/ws/?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Realtime audit channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Realtime audit payload invalid.'));
    }

    return {
      events: Array.isArray(payload.events) ? payload.events : [],
      latestEventId: typeof payload.latest_event_id === 'string' ? payload.latest_event_id : '',
    };
  };

  const notifyAuditSignals = (events) => {
    const signals = (Array.isArray(events) ? events : []).filter((event) => {
      const type = String(event?.event_type || '');
      const actor = String(event?.actor_uuid || '');
      return isSignalEventType(type) && actor !== '' && actor !== currentUserUUID;
    });

    if (signals.length === 0) {
      return;
    }

    const first = signals[0];
    const type = String(first?.event_type || '');
    const label = SIGNAL_EVENT_LABELS[type] || 'Organization security signal';
    const suffix = signals.length > 1 ? ` (+${signals.length - 1} more)` : '';
    PC.showToast(`${label}${suffix}`, 'save', 4500, true);
  };

  const shouldRefreshAccessPanels = (events) => {
    const refreshTypes = new Set([
      'invite.accepted',
      'invite.revoked',
      'invite.sent',
      'access.request.approved',
      'access.request.rejected',
      'relationship.revoked',
      'relationship.withdrawn',
      'ownership.transferred',
      'access.requested',
    ]);

    return (Array.isArray(events) ? events : []).some((event) => {
      const type = String(event?.event_type || '');
      return refreshTypes.has(type);
    });
  };

  const stopRealtimeAuditPolling = () => {
    if (state.auditRealtimeIntervalId !== null) {
      window.clearInterval(state.auditRealtimeIntervalId);
      state.auditRealtimeIntervalId = null;
    }
  };

  const pollRealtimeAudit = async (organizationId) => {
    if (organizationId === '' || state.selectedOrganizationId !== organizationId) {
      return;
    }

    try {
      if (!state.auditRealtimeReady) {
        const bootstrap = await fetchRealtimeAuditDelta(organizationId, '');
        state.auditRealtimeTopEventId = bootstrap.latestEventId;
        state.auditRealtimeReady = true;
        await loadOrganizationAudit(organizationId);
        return;
      }

      const delta = await fetchRealtimeAuditDelta(organizationId, state.auditRealtimeTopEventId);
      if (delta.events.length === 0) {
        return;
      }

      state.auditRealtimeTopEventId = delta.latestEventId || state.auditRealtimeTopEventId;
      notifyAuditSignals(delta.events);

      await loadOrganizationAudit(organizationId);

      if (shouldRefreshAccessPanels(delta.events)) {
        loadOrganizationInvites(organizationId).catch((error) => PW.error(error));
        loadOrganizationInviteHistoryGrid(organizationId).catch((error) => PW.error(error));
        loadOrganizationAccessRequests(organizationId).catch((error) => PW.error(error));
        loadOrganizationRelationships(organizationId).catch((error) => PW.error(error));
      }
    } catch (error) {
      PW.warn('Realtime channel fallback to timeline poll', error);

      const events = await fetchOrganizationAuditEvents(organizationId);
      const topEventId = events.length > 0 ? String(events[0].event_id || '') : '';
      if (!state.auditRealtimeReady) {
        state.auditRealtimeTopEventId = topEventId;
        state.auditRealtimeReady = true;
        await loadOrganizationAudit(organizationId);
        return;
      }
      if (topEventId === '' || topEventId === state.auditRealtimeTopEventId) {
        return;
      }
      const newEvents = [];
      for (const event of events) {
        const eventId = String(event?.event_id || '');
        if (eventId === state.auditRealtimeTopEventId) {
          break;
        }
        newEvents.push(event);
      }

      state.auditRealtimeTopEventId = topEventId;
      notifyAuditSignals(newEvents);
      await loadOrganizationAudit(organizationId);

      if (shouldRefreshAccessPanels(newEvents)) {
        loadOrganizationInvites(organizationId).catch((error) => PW.error(error));
        loadOrganizationInviteHistoryGrid(organizationId).catch((error) => PW.error(error));
        loadOrganizationAccessRequests(organizationId).catch((error) => PW.error(error));
        loadOrganizationRelationships(organizationId).catch((error) => PW.error(error));
      }
    }
  };

  const startRealtimeAuditPolling = (organizationId) => {
    stopRealtimeAuditPolling();
    state.auditRealtimeReady = false;
    state.auditRealtimeTopEventId = '';

    if (organizationId === '') {
      return;
    }

    pollRealtimeAudit(organizationId).catch((error) => PW.error(error));
    state.auditRealtimeIntervalId = window.setInterval(() => {
      pollRealtimeAudit(organizationId).catch((error) => PW.error(error));
    }, 5000);
  };

  const renderDiscovery = (payload) => {
    if (!elements.discoveryResults) {
      return;
    }

    const userSites = Array.isArray(payload?.user_sites) ? payload.user_sites : [];
    const matchCandidates = Array.isArray(payload?.match_candidates) ? payload.match_candidates : [];
    const rows = [];

    userSites.forEach((site) => {
      if (String(site.organization_id || '') !== '') {
        return;
      }

      rows.push(`
        <div class="organizations_stack_row">
          <div class="organizations_stack_text">
            <strong>${String(site.name || T.unknown)}</strong>
            <span>Site without organization</span>
          </div>
          <button type="button" class="btn btn_secondary" data-org-action="link-site" data-site-id="${String(site.site_id || '')}" data-site-owner-uuid="${String(site.site_owner_uuid || '')}">${T.linkSite}</button>
        </div>
      `);
    });

    matchCandidates.forEach((candidate) => {
      rows.push(`
        <div class="organizations_stack_row organizations_stack_row_hint">
          <div class="organizations_stack_text">
            <strong>${String(candidate.candidate_type || 'candidate')}</strong>
            <span>${String(candidate.reason || '')}</span>
          </div>
        </div>
      `);
    });

    if (rows.length === 0) {
      setStackMessage(elements.discoveryResults, T.noDiscovery);
      announceDiscoveryStatus('Discovery results loaded. No recommendations found.');
      return;
    }

    elements.discoveryResults.classList.remove('organizations_empty');
    Guardian.setHTML(elements.discoveryResults, rows.join(''));
    announceDiscoveryStatus(`Discovery results loaded. ${rows.length} item${rows.length === 1 ? '' : 's'} shown.`);
  };

  const loadOrganizationInvites = async (organizationId) => {
    const organization = findOrganization(organizationId);
    if (organization && !canUsePremiumOrgFeatures(organization)) {
      if (elements.invitesList) {
        setStackMessage(elements.invitesList, T.premiumAdminLockedDetailed);
      }
      if (elements.membersInvitesList) {
        setStackMessage(elements.membersInvitesList, T.premiumAdminLockedDetailed);
      }
      if (elements.membersInviteHistoryGridContainer) {
        setDatagridMessage(elements.membersInviteHistoryGridContainer, T.premiumAdminLockedDetailed);
      }
      announceInvitesStatus('Invites are locked behind Premium Admin preview for this organization.');
      return;
    }

    if (organization && !canManageOrganizationAccess(organization)) {
      if (elements.invitesList) {
        setStackMessage(elements.invitesList, ACCESS_MANAGE_WARNING);
      }
      if (elements.membersInvitesList) {
        setStackMessage(elements.membersInvitesList, ACCESS_MANAGE_WARNING);
      }
      if (elements.membersInviteHistoryGridContainer) {
        setDatagridMessage(elements.membersInviteHistoryGridContainer, ACCESS_MANAGE_WARNING);
      }
      announceInvitesStatus(ACCESS_MANAGE_WARNING);
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/organizations/${encodeURIComponent(organizationId)}/invites`);
      renderInvites(payload.invites || []);
    } catch (error) {
      PW.error(error);
      if (elements.invitesList) {
        setStackMessage(elements.invitesList, T.manageAccessUnavailable);
      }
      if (elements.membersInvitesList) {
        setStackMessage(elements.membersInvitesList, T.manageAccessUnavailable);
      }
      announceInvitesStatus('Invites list failed to load.');
    }
  };

  const loadOrganizationAccessRequests = async (organizationId) => {
    const organization = findOrganization(organizationId);
    if (organization && !canUsePremiumOrgFeatures(organization)) {
      if (elements.accessRequestsList) {
        setStackMessage(elements.accessRequestsList, T.premiumAdminLockedDetailed);
      }
      announceAccessRequestsStatus('Access requests are locked behind Premium Admin preview for this organization.');
      return;
    }

    if (organization && !canManageOrganizationAccess(organization)) {
      if (elements.accessRequestsList) {
        setStackMessage(elements.accessRequestsList, ACCESS_MANAGE_WARNING);
      }
      announceAccessRequestsStatus(ACCESS_MANAGE_WARNING);
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/organizations/${encodeURIComponent(organizationId)}/access/requests`);
      renderAccessRequests(payload.requests || []);
    } catch (error) {
      PW.error(error);
      if (elements.accessRequestsList) {
        setStackMessage(elements.accessRequestsList, T.manageAccessUnavailable);
      }
      announceAccessRequestsStatus('Access requests list failed to load.');
    }
  };

  const loadOrganizationRelationships = async (organizationId) => {
    const organization = findOrganization(organizationId);
    if (organization && !canUsePremiumOrgFeatures(organization)) {
      renderRelationships([]);
      if (elements.transferNotice) {
        elements.transferNotice.textContent = T.premiumAdminLockedDetailed;
      }
      return;
    }

    if (organization && !canManageOrganizationAccess(organization)) {
      renderRelationships([]);
      if (elements.transferNotice) {
        elements.transferNotice.textContent = ACCESS_MANAGE_WARNING;
      }
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/organizations/${encodeURIComponent(organizationId)}/relationships`);
      const transferMembers = Array.isArray(payload.members) ? payload.members : (payload.relationships || []);
      renderRelationships(transferMembers);
    } catch (error) {
      PW.error(error);
      renderRelationships([]);
      if (elements.transferNotice) {
        elements.transferNotice.textContent = T.manageAccessUnavailable;
      }
    }
  };

  const loadOrganizationAudit = async (organizationId) => {
    const manager = ensureAuditGridManager(organizationId);
    if (!manager) {
      throw new Error('Unable to initialize audit grid manager.');
    }

    try {
      await manager.reload();
    } catch (error) {
      PW.error(error);
      setDatagridMessage(elements.auditGridContainer, T.loadAuditFailed);
      announceAuditStatus('Audit timeline failed to load.');
    }
  };

  const loadFreeProfileAudit = async (organizationId) => {
    const orgId = String(organizationId || '').trim();
    if (orgId === '' || !(elements.freeAuditGridContainer instanceof HTMLElement)) {
      return;
    }

    const manager = ensureFreeAuditGridManager(orgId);
    if (!manager) {
      throw new Error('Unable to initialize free profile audit grid manager.');
    }

    setDatagridMessage(elements.freeAuditGridContainer, T.loading);
    announceFreeAuditStatus('Loading profile-related audit timeline...');

    try {
      await manager.reload();
      announceFreeAuditStatus('Profile audit timeline loaded.');
    } catch (error) {
      PW.error(error);
      setDatagridMessage(elements.freeAuditGridContainer, T.loadAuditFailed);
      announceFreeAuditStatus('Profile audit timeline failed to load.');
    }
  };

  const loadOrganizationSettings = async (organizationId) => {
    const payload = await apiRequest(`/api/v1/organizations/${encodeURIComponent(organizationId)}/settings`);
    const organization = findOrganization(organizationId);
    hydrateSettings(payload, organization);
    return payload;
  };

  const loadOrganizationDetailViews = async (organizationId) => {
    setStackMessage(elements.invitesList, T.loading);
    if (elements.membersInvitesList) {
      setStackMessage(elements.membersInvitesList, T.loading);
    }
    if (elements.membersInviteHistoryGridContainer) {
      setDatagridMessage(elements.membersInviteHistoryGridContainer, T.loading);
    }
    announceInvitesStatus('Loading invites list...');
    if (elements.accessRequestsList) {
      setStackMessage(elements.accessRequestsList, T.loading);
    }
    announceAccessRequestsStatus('Loading access requests...');
    setStackMessage(elements.discoveryResults, T.noDiscovery);
    announceDiscoveryStatus('Discovery auto-refresh is enabled every 1 minute via live socket.');
    setDatagridMessage(elements.auditGridContainer, T.loading);
    announceAuditStatus('Loading audit timeline...');
    renderRelationships([]);

    await loadOrganizationSettings(organizationId);

    const organization = findOrganization(organizationId);
    if (organization) {
      populateOrgDetails(organization);
    }
    if (organization && !canUsePremiumOrgFeatures(organization)) {
      setStackMessage(elements.invitesList, T.premiumAdminLockedDetailed);
      if (elements.membersInvitesList) {
        setStackMessage(elements.membersInvitesList, T.premiumAdminLockedDetailed);
      }
      if (elements.membersInviteHistoryGridContainer) {
        setDatagridMessage(elements.membersInviteHistoryGridContainer, T.premiumAdminLockedDetailed);
      }
      if (elements.accessRequestsList) {
        setStackMessage(elements.accessRequestsList, T.premiumAdminLockedDetailed);
      }
      setStackMessage(elements.discoveryResults, T.premiumAdminLockedDetailed);
      setDatagridMessage(elements.auditGridContainer, T.premiumAdminLockedDetailed);
      announceAuditStatus('Audit timeline is locked behind Premium Admin preview for this organization.');
      if (elements.transferNotice) {
        elements.transferNotice.textContent = T.premiumAdminLockedDetailed;
      }
      return;
    }

    await Promise.allSettled([
      loadOrganizationInvites(organizationId),
      loadOrganizationInviteHistoryGrid(organizationId),
      loadOrganizationAccessRequests(organizationId),
      loadOrganizationRelationships(organizationId),
      loadOrganizationAudit(organizationId),
    ]);
  };

  const openOrganizationDialog = async (organizationId) => {
    const organization = findOrganization(organizationId);
    if (!organization) {
      return;
    }

    state.selectedOrganizationId = organizationId;
    resetMembersImportFlow();
    if (elements.membersImportSummary instanceof HTMLElement) {
      elements.membersImportSummary.textContent = '';
      elements.membersImportSummary.classList.add('organizations_empty');
    }
    setMembersImportStatus('');
    setEditorMeta(organization);
    const membersPanelOnOpen = document.getElementById('organizations_tab_members_panel');
    if (membersPanelOnOpen) {
      delete membersPanelOnOpen.dataset.ready;
    }
    if (elements.preview) {
      elements.preview.textContent = T.loadingDetails;
    }
    closeContactImagePopover();
    openDialog();

    markOrganizationNotificationsRead(organizationId).catch(() => {});

    try {
      await loadOrganizationDetailViews(organizationId);
      await handleDiscovery(false);
      if (state.discoveryIntervalId !== null) {
        window.clearInterval(state.discoveryIntervalId);
        state.discoveryIntervalId = null;
      }
      state.discoveryIntervalId = window.setInterval(() => {
        handleDiscovery(false).catch((error) => PW.error(error));
      }, 60000);
      startRealtimeAuditPolling(organizationId);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.loadDefaultsFailed, 'error', 7000, true);
    }
  };

  const refreshIndex = async (preferredOrganizationId = '', reopenDialog = false) => {
    try {
      await loadOrganizations();
      await loadGrid();
      loadPersonalOrganizationPanel();
      if (elements.liveRequestsList instanceof HTMLElement) {
        await syncLiveRequestsPanel(false);
      }

      if (preferredOrganizationId !== '' && reopenDialog) {
        await openOrganizationDialog(preferredOrganizationId);
        return;
      }

      if (state.selectedOrganizationId !== '' && findOrganization(state.selectedOrganizationId) === null) {
        state.selectedOrganizationId = '';
        closeDialog();
      }
    } catch (error) {
      PW.error(error);
      PC.showToast(T.loadOrgsFailed, 'error', 7000, true);
    }
  };

  const handleSearchOrganizations = async (event) => {
    event.preventDefault();
    const query = elements.searchInput instanceof HTMLInputElement
      ? String(elements.searchInput.value || '').trimStart()
      : '';

    if (elements.searchInput instanceof HTMLInputElement) {
      elements.searchInput.value = query;
    }

    state.grid.search = query;
    state.grid.page = '1';
    await loadGrid({ search: query, page: '1' });
    setDiscoveryPanelStatus(query === '' ? 'Search cleared.' : `Search applied: ${query}`);
  };

  const handleRequestJoinOrganization = async (event) => {
    event.preventDefault();

    const rawLookup = elements.requestEmail instanceof HTMLInputElement
      ? String(elements.requestEmail.value || '').trim()
      : '';
    const contactEmail = extractLookupEmail(rawLookup);

    if (contactEmail === '') {
      PC.showToast('Provide a valid email or select an organization result.', 'error', 5000, true);
      return;
    }

    const accessLevel = state.requestAccessLevel || 'full';

    try {
      const payload = await postForm('/api/v1/organizations/access/request', {
        owner_email: contactEmail,
        access_level: accessLevel,
      });

      if (elements.requestEmail instanceof HTMLInputElement) {
        elements.requestEmail.value = '';
      }

      const requestId = String(payload?.request_id || '');
      const statusText = requestId === ''
        ? T.requestJoinPending
        : `Access request submitted (${requestId}).`;

      setDiscoveryPanelStatus(statusText);
      PC.showToast(statusText, 'save', 7000, true);
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.requestJoinFailed;
      setDiscoveryPanelStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };

  const handleSaveOrganization = async () => {
    if (state.selectedOrganizationId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    await saveOrganizationEditorSettings('manual', true);
  };

  const handleBootstrapOrganizationDek = async () => {
    if (state.selectedOrganizationId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (elements.bootstrapDekButton instanceof HTMLButtonElement) {
      elements.bootstrapDekButton.disabled = true;
    }

    try {
      const payload = await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/encryption/bootstrap`, {
        segment: 'current_period',
        version: '1',
      });

      const bootstrappedCount = Number(payload && payload.bootstrapped_count ? payload.bootstrapped_count : 0);
      const failedCount = Number(payload && payload.failed_count ? payload.failed_count : 0);
      const message = failedCount > 0
        ? `${T.orgDekBootstrapDone} ${bootstrappedCount} member(s) bootstrapped, ${failedCount} failed.`
        : `${T.orgDekBootstrapDone} ${bootstrappedCount} member(s) bootstrapped.`;

      announceLiveToast(message, 'save');
      PC.showToast(message, failedCount > 0 ? 'error' : 'save', failedCount > 0 ? 9000 : 7000, true);
      await refreshIndex(state.selectedOrganizationId, true);
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message
        ? error.message
        : T.orgDekBootstrapFailed;
      announceLiveToast(message, 'error');
      PC.showToast(message, 'error', 9000, true);
    } finally {
      const selectedOrg = getSelectedOrganization();
      const locked = !!selectedOrg && !canUsePremiumOrgFeatures(selectedOrg);
      if (elements.bootstrapDekButton instanceof HTMLButtonElement) {
        elements.bootstrapDekButton.disabled = locked;
      }
    }
  };

  const handleSendInvite = async () => {
    if (state.selectedOrganizationId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    const email = elements.inviteEmail instanceof HTMLInputElement ? elements.inviteEmail.value.trim() : '';
    const scopes = getSelectedInviteScopes();

    if (email === '') {
      PC.showToast(T.enterInviteEmail, 'error', 5000, true);
      return;
    }

    if (scopes.length === 0) {
      announceScopeSelectionStatus('required');
      PC.showToast(T.selectScope, 'error', 5000, true);
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/invites/send`, {
        email,
        scopes,
      });

      if (elements.inviteEmail instanceof HTMLInputElement) {
        elements.inviteEmail.value = '';
      }
      document.querySelectorAll('#organizations_scope_grid .organizations_scope').forEach((input) => {
        if (input instanceof HTMLInputElement) {
          input.checked = false;
        }
      });
      announceScopeSelectionStatus('cleared');

      PC.showToast(T.inviteSent, 'save', 5000, true);
      await loadOrganizationInvites(state.selectedOrganizationId);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.inviteSendFailed, 'error', 7000, true);
    }
  };

  const handleTransferOwnership = async () => {
    if (state.selectedOrganizationId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    syncTransferTargetFromLookup();
    const targetUUID = elements.transferTargetUUID instanceof HTMLInputElement
      ? String(elements.transferTargetUUID.value || '').trim()
      : '';
    if (targetUUID === '') {
      PC.showToast(T.selectTransferTarget, 'error', 5000, true);
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/ownership/transfer`, {
        target_user_uuid: targetUUID,
      });
      PC.showToast(T.ownershipTransferred, 'save', 6000, true);
      await refreshIndex(state.selectedOrganizationId, true);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.ownershipTransferFailed, 'error', 7000, true);
    }
  };

  const handleLeaveOrganization = async () => {
    if (state.selectedOrganizationId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/leave`, {});
      PC.showToast(T.withdrawn, 'save', 5000, true);
      closeDialog();
      await refreshIndex();
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.withdrawFailed, 'error', 7000, true);
    }
  };

  const handleRemoveOrganizationFromGrid = async (organizationId) => {
    if (organizationId === '') {
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(organizationId)}/leave`, {});
      PC.showToast(T.withdrawn, 'save', 5000, true);

      if (state.selectedOrganizationId === organizationId) {
        state.selectedOrganizationId = '';
        closeDialog();
      }

      await refreshIndex();
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.removeFailed, 'error', 7000, true);
    }
  };

  const fetchDiscoverySnapshot = async () => {
    const params = new URLSearchParams({
      channel: 'organization_discovery',
    });

    if (state.discoverySignature !== '') {
      params.set('since_signature', state.discoverySignature);
    }

    const response = await fetch(`/ws/?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Discovery channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Discovery payload invalid.'));
    }

    return payload;
  };

  const handleDiscovery = async (showToast = true) => {
    if (blockPremiumActionWhenLocked()) {
      setStackMessage(elements.discoveryResults, T.premiumAdminLockedDetailed);
      announceDiscoveryStatus('Discovery is locked behind Premium Admin preview for this organization.');
      return;
    }

    try {
      if (showToast) {
        PC.showToast(T.discoveryRunning, 'save', 5000, true);
      }
      announceDiscoveryStatus('Running organization discovery...');
      const payload = await fetchDiscoverySnapshot();
      if (!payload.unchanged) {
        renderDiscovery(payload);
      }
      state.discoverySignature = String(payload.latest_signature || '');
      if (showToast) {
        PC.showToast(T.discoveryComplete, 'save', 5000, true);
      }
    } catch (error) {
      PW.error(error);
      setStackMessage(elements.discoveryResults, T.discoveryUnavailable);
      announceDiscoveryStatus('Discovery failed to load results.');
      if (showToast) {
        PC.showToast(error instanceof Error && error.message ? error.message : T.discoveryFailed, 'error', 7000, true);
      }
    }
  };

  const handleLinkAction = async (action, dataset) => {
    if (state.selectedOrganizationId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    try {
      if (action === 'link-site') {
        await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/sites/link`, {
          site_id: String(dataset.siteId || ''),
          site_owner_uuid: String(dataset.siteOwnerUuid || ''),
        });
        PC.showToast(T.siteLinked, 'save', 5000, true);
      }

      await handleDiscovery();
    } catch (error) {
      PW.error(error);
      PC.showToast(
        T.siteLinkFailed,
        'error',
        7000,
        true
      );
    }
  };

  const handleRevokeInvite = async (inviteId) => {
    if (state.selectedOrganizationId === '' || inviteId === '') {
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/invites/revoke`, {
        invite_id: inviteId,
      });
      PC.showToast(T.inviteRevoked, 'save', 4000, true);
      await Promise.allSettled([
        loadOrganizationInvites(state.selectedOrganizationId),
        loadOrganizationInviteHistoryGrid(state.selectedOrganizationId),
        loadOrganizationAudit(state.selectedOrganizationId),
      ]);
    } catch (error) {
      PW.error(error);
      PC.showToast(T.inviteRevokeFailed, 'error', 7000, true);
    }
  };

  const handleApproveAccessRequest = async (requestId) => {
    if (state.selectedOrganizationId === '' || requestId === '') {
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    const consentContext = await promptMembershipConsent('Approve access request');
    if (consentContext === null) {
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/access/requests/approve`, {
        request_id: requestId,
        ...consentContext,
      });
      PC.showToast(T.accessRequestApproved, 'save', 5000, true);
      await Promise.allSettled([
        loadOrganizationAccessRequests(state.selectedOrganizationId),
        loadOrganizationRelationships(state.selectedOrganizationId),
        loadOrganizationInviteHistoryGrid(state.selectedOrganizationId),
      ]);
      await syncLiveRequestsPanel(false);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.accessRequestActionFailed, 'error', 7000, true);
    }
  };

  const handleRejectAccessRequest = async (requestId) => {
    if (state.selectedOrganizationId === '' || requestId === '') {
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(state.selectedOrganizationId)}/access/requests/reject`, {
        request_id: requestId,
      });
      PC.showToast(T.accessRequestRejected, 'save', 5000, true);
      await Promise.allSettled([
        loadOrganizationAccessRequests(state.selectedOrganizationId),
        loadOrganizationInviteHistoryGrid(state.selectedOrganizationId),
      ]);
      await syncLiveRequestsPanel(false);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.accessRequestActionFailed, 'error', 7000, true);
    }
  };

  const handleLiveRequestAction = async (organizationId, requestId, action) => {
    const orgId = String(organizationId || '').trim();
    const reqId = String(requestId || '').trim();
    const normalizedAction = String(action || '').trim().toLowerCase();

    if (orgId === '' || reqId === '' || !['approve', 'reject'].includes(normalizedAction)) {
      return;
    }

    const org = findOrganization(orgId);
    if (org && !canManageOrganizationAccess(org)) {
      showAccessManagementDeniedWarning();
      showOrganizationsToast(ACCESS_MANAGE_WARNING, 'error', 7000, true);
      return;
    }

    let consentContext = {};
    if (normalizedAction === 'approve') {
      const consentResult = await promptMembershipConsent('Approve access request');
      if (consentResult === null) {
        return;
      }
      consentContext = consentResult;
    }

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(orgId)}/access/requests/${encodeURIComponent(normalizedAction)}`, {
        request_id: reqId,
        ...consentContext,
      });

      showOrganizationsToast(normalizedAction === 'approve' ? T.accessRequestApproved : T.accessRequestRejected, 'save', 4200, true);

      if (state.selectedOrganizationId === orgId) {
        await Promise.allSettled([
          loadOrganizationAccessRequests(orgId),
          loadOrganizationRelationships(orgId),
          loadOrganizationInviteHistoryGrid(orgId),
        ]);
      }

      await syncLiveRequestsPanel(false);
    } catch (error) {
      PW.error(error);
      showOrganizationsToast(error instanceof Error && error.message ? error.message : T.accessRequestActionFailed, 'error', 7000, true);
    }
  };

  const handleGridClick = async (event) => {
    const rowAction = event.target.closest('.datagrid_action[data-action]');
    if (rowAction && elements.gridContainer?.contains(rowAction)) {
      const action = String(rowAction.dataset.action || '');
      const organizationId = String(rowAction.dataset.id || '');
      if (action === 'remove') {
        if (!(rowAction instanceof HTMLButtonElement)) {
          return;
        }

        const isConfirmed = rowAction.dataset.confirm === '1' && state.inlineDeleteConfirmOrgId === organizationId;
        if (!isConfirmed) {
          armInlineDeleteConfirm(rowAction, organizationId);
          return;
        }

        resetInlineDeleteConfirm();
        await handleRemoveOrganizationFromGrid(organizationId);
      }
      return;
    }

    const sortButton = event.target.closest('.datagrid_sort');
    if (sortButton && elements.gridContainer?.contains(sortButton)) {
      const column = String(sortButton.dataset.column || 'name');
      const nextDirection = state.grid.sort === column && state.grid.direction === 'asc' ? 'desc' : 'asc';
      await loadGrid({ sort: column, direction: nextDirection, page: '1' });
      return;
    }

    const actionButton = event.target.closest('[data-org-action]');
    if (actionButton) {
      const action = String(actionButton.dataset.orgAction || '');
      if (action === 'revoke-invite') {
        await handleRevokeInvite(String(actionButton.dataset.inviteId || ''));
      }
      if (action === 'approve-access-request') {
        await handleApproveAccessRequest(String(actionButton.dataset.requestId || ''));
      }
      if (action === 'reject-access-request') {
        await handleRejectAccessRequest(String(actionButton.dataset.requestId || ''));
      }
      if (action === 'link-site') {
        await handleLinkAction(action, actionButton.dataset);
      }
      return;
    }

    const row = event.target.closest('.datagrid_row');
    if (!row || !elements.gridContainer?.contains(row)) {
      return;
    }

    const organizationId = String(row.dataset.id || '');
    if (organizationId !== '') {
      await openOrganizationDialog(organizationId);
    }
  };

  const handleGridInput = (event) => {
    const searchInput = event.target.closest('.datagrid_search');
    if (!searchInput) {
      return;
    }

    const search = String(searchInput.value || '').trim();

    if (state.searchDebounceId !== null) {
      window.clearTimeout(state.searchDebounceId);
    }

    if (elements.gridContainer?.contains(searchInput)) {
      state.grid.search = search;
      state.grid.page = '1';
      state.searchDebounceId = window.setTimeout(() => {
        loadGrid({ search, page: '1' })
          .then(() => searchInput.focus())
          .catch((error) => PW.error(error));
      }, 250);
      return;
    }

    const managerGridContainers = [
      { container: elements.auditGridContainer, managerKey: 'auditGridManager' },
      { container: elements.freeAuditGridContainer, managerKey: 'freeAuditGridManager' },
      { container: elements.membersInviteHistoryGridContainer, managerKey: 'inviteHistoryGridManager' },
      { container: elements.membersGridContainer, managerKey: 'membersGridManager' },
    ];

    for (const { container, managerKey } of managerGridContainers) {
      if (container instanceof HTMLElement && container.contains(searchInput)) {
        const manager = state[managerKey];
        if (manager && typeof manager.setSearch === 'function') {
          const refocusHandler = (e) => {
            if (container.contains(searchInput)) {
              searchInput.focus();
            }
            document.removeEventListener('paycal:datagrid-reloaded', refocusHandler);
          };
          state.searchDebounceId = window.setTimeout(() => {
            manager.setSearch(search);
            document.addEventListener('paycal:datagrid-reloaded', refocusHandler);
          }, 250);
        }
        return;
      }
    }
  };

  const handleGridKeydown = async (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }

    if (event.target.closest('.datagrid_actions')) {
      return;
    }

    const row = event.target.closest('.datagrid_row');
    if (!row || !elements.gridContainer?.contains(row)) {
      return;
    }

    event.preventDefault();
    const organizationId = String(row?.dataset.id || '');
    if (organizationId !== '') {
      await openOrganizationDialog(organizationId);
    }
  };

  const closeMembershipConsentDialog = () => {
    if (elements.membershipConsentDialog instanceof HTMLDialogElement && elements.membershipConsentDialog.open) {
      elements.membershipConsentDialog.close('cancel');
    }
  };

  const promptMembershipConsent = async (actionLabel) => {
    if (!(elements.membershipConsentDialog instanceof HTMLDialogElement)
      || !(elements.membershipConsentForm instanceof HTMLFormElement)
      || !(elements.membershipConsentAcknowledge instanceof HTMLInputElement)
      || !(elements.membershipConsentDisclaimer instanceof HTMLTextAreaElement)) {
      return {
        consent_acknowledged: '1',
        consent_version: 'v1',
        disclaimer_text: T.membershipConsentDefaultDisclaimer,
      };
    }

    elements.membershipConsentAcknowledge.checked = false;
    elements.membershipConsentDisclaimer.value = '';
    if (elements.membershipConsentAction instanceof HTMLElement) {
      elements.membershipConsentAction.textContent = String(actionLabel || T.membershipConsentIntro);
    }
    if (elements.membershipConsentError instanceof HTMLElement) {
      elements.membershipConsentError.textContent = '';
      elements.membershipConsentError.classList.add('hidden');
    }

    return await new Promise((resolve) => {
      let settled = false;

      const settle = (value) => {
        if (settled) {
          return;
        }
        settled = true;
        cleanup();
        resolve(value);
      };

      const onSubmit = (event) => {
        event.preventDefault();
        if (!elements.membershipConsentAcknowledge.checked) {
          if (elements.membershipConsentError instanceof HTMLElement) {
            elements.membershipConsentError.textContent = T.membershipConsentAckRequired;
            elements.membershipConsentError.classList.remove('hidden');
          }
          return;
        }

        const disclaimerInput = String(elements.membershipConsentDisclaimer.value || '').trim();
        settle({
          consent_acknowledged: '1',
          consent_version: 'v1',
          disclaimer_text: disclaimerInput === '' ? T.membershipConsentDefaultDisclaimer : disclaimerInput,
        });

        if (elements.membershipConsentDialog instanceof HTMLDialogElement && elements.membershipConsentDialog.open) {
          elements.membershipConsentDialog.close('confirm');
        }
      };

      const onCancelClick = (event) => {
        event.preventDefault();
        closeMembershipConsentDialog();
      };

      const onDialogClick = (event) => {
        if (event.target === elements.membershipConsentDialog) {
          closeMembershipConsentDialog();
        }
      };

      const onDialogClose = () => {
        if (!settled) {
          settle(null);
        }
      };

      const cleanup = () => {
        elements.membershipConsentForm?.removeEventListener('submit', onSubmit);
        elements.membershipConsentCancel?.removeEventListener('click', onCancelClick);
        elements.membershipConsentClose?.removeEventListener('click', onCancelClick);
        elements.membershipConsentDialog?.removeEventListener('click', onDialogClick);
        elements.membershipConsentDialog?.removeEventListener('close', onDialogClose);
      };

      elements.membershipConsentForm.addEventListener('submit', onSubmit);
      elements.membershipConsentCancel?.addEventListener('click', onCancelClick);
      elements.membershipConsentClose?.addEventListener('click', onCancelClick);
      elements.membershipConsentDialog.addEventListener('click', onDialogClick);
      elements.membershipConsentDialog.addEventListener('close', onDialogClose);

      elements.membershipConsentDialog.showModal();
      elements.membershipConsentAcknowledge.focus();
    });
  };

  const setFieldErrorState = (input, errorId, message) => {
    const errorElement = document.getElementById(errorId);
    if (input instanceof HTMLElement) {
      input.classList.toggle('input_error', message !== '');
      if (message !== '') {
        input.setAttribute('aria-invalid', 'true');
      } else {
        input.removeAttribute('aria-invalid');
      }
    }
    if (errorElement) {
      errorElement.textContent = message;
    }
  };

  const clearFieldErrorStates = (pairs) => {
    pairs.forEach(([inputId, errorId]) => {
      setFieldErrorState(document.getElementById(inputId), errorId, '');
    });
  };

  const clearFieldInvalidStates = (ids) => {
    ids.forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove('input_error');
        el.removeAttribute('aria-invalid');
      }
    });
  };

  const bindProfileEditDetails = () => {
    const editDetailsForm = document.getElementById('edit_details_form');
    const editDetailsPhone = document.getElementById('edit_details_phone');
    const editDetailsEmail = document.getElementById('edit_details_email');

    if (!(editDetailsForm instanceof HTMLFormElement)) {
      return;
    }

    if (editDetailsEmail instanceof HTMLInputElement && editDetailsEmail.readOnly) {
      const originalEmailValue = String(editDetailsEmail.value || '');

      editDetailsEmail.addEventListener('mouseenter', () => {
        editDetailsEmail.value = 'Change Email';
      });

      editDetailsEmail.addEventListener('mouseleave', () => {
        editDetailsEmail.value = originalEmailValue;
      });

      editDetailsEmail.addEventListener('click', () => {
        editDetailsEmail.value = originalEmailValue;
        resetChangeEmailModal();
        PC.openModal('modal_change_email', 'Change Email');
      });
    }

    const buildSettingsPayloadSignature = () => {
      const formData = new FormData(editDetailsForm);
      const pairs = [];
      for (const [key, value] of formData.entries()) {
        if (key === 'csrf_token') {
          continue;
        }
        pairs.push(`${key}:${String(value)}`);
      }
      pairs.sort();
      return pairs.join('|');
    };

    let lastSubmittedSignature = buildSettingsPayloadSignature();

    const validationPairs = [
      ['edit_details_full_name', 'edit_details_full_name_error'],
      ['edit_details_phone', 'edit_details_phone_error'],
      ['edit_details_province', 'edit_details_province_error'],
      ['edit_details_address_line1', 'edit_details_address_line1_error'],
      ['edit_details_address_city', 'edit_details_address_city_error'],
      ['edit_details_address_postal', 'edit_details_address_postal_error'],
    ];

    const clearValidationState = () => {
      clearFieldErrorStates(validationPairs);
    };

    const validateForm = () => {
      clearValidationState();

      const fullNameInput = document.getElementById('edit_details_full_name');
      const phoneInput = document.getElementById('edit_details_phone');
      const provinceInput = document.getElementById('edit_details_province');

      let firstInvalidField = null;
      const markInvalid = (input, errorId, message) => {
        setFieldErrorState(input, errorId, message);
        if (!firstInvalidField && input instanceof HTMLElement) {
          firstInvalidField = input;
        }
      };

      const fullName = String(fullNameInput?.value || '').trim();
      if (fullName.length < 2) {
        markInvalid(fullNameInput, 'edit_details_full_name_error', 'Enter your full name.');
      }

      const phone = String(phoneInput?.value || '').trim();
      if (phone.length > 0 && !/^\(\d{3}\) \d{3}-\d{4}$/.test(phone)) {
        markInvalid(phoneInput, 'edit_details_phone_error', 'Use phone format (123) 456-7890.');
      }

      const province = String(provinceInput?.value || '').trim();
      if (province.length !== 2) {
        markInvalid(provinceInput, 'edit_details_province_error', 'Select a province.');
      }

      if (firstInvalidField) {
        PC.showToast('Please correct the highlighted fields and try again.', 'error');
        firstInvalidField.focus();
        return false;
      }

      return true;
    };

    if (editDetailsPhone instanceof HTMLInputElement) {
      PC.formatPhoneNumber(editDetailsPhone);
      editDetailsPhone.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement) {
          PC.formatPhoneNumber(event.target);
        }
      });
      editDetailsPhone.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement) {
          PC.formatPhoneNumber(event.target);
        }
      });
    }

    editDetailsForm.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }

      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      // Allow finder inputs to keep their own Enter behavior for selection.
      if (target.id === 'organizations_personal_timezone_search' || target.id === 'organizations_personal_currency_search') {
        return;
      }

      if (target instanceof HTMLTextAreaElement) {
        return;
      }

      event.preventDefault();
      editDetailsForm.dispatchEvent(new Event('submit'));
    });

    editDetailsForm.addEventListener('submit', (event) => {
      event.preventDefault();

      const payloadSignature = buildSettingsPayloadSignature();
      if (payloadSignature === lastSubmittedSignature) {
        return;
      }

      if (!validateForm()) {
        return;
      }

      PC.showToast('<?php echo addslashes(org_js_index_i18n('UPDATING_INFO')); ?>...', 'working');

      const formData = new FormData(editDetailsForm);
      PC.updateResource('account/info', formData).then(() => {
        lastSubmittedSignature = payloadSignature;
        clearValidationState();
        PC.showToast('<?php echo addslashes(org_js_index_i18n('INFO_UPDATED')); ?>', 'save');
      }).catch((error) => {
        PC.showToast('Unable to save account details right now. Please try again.', 'error');
        PW.error(error);
      });
    });

    // Auto-submit on field change with debounce
    let saveTimeout = null;
    const autoSaveFields = [
      'edit_details_full_name',
      'edit_details_phone',
      'edit_details_province',
      'edit_details_address_line1',
      'edit_details_address_city',
      'edit_details_address_postal'
    ];

    autoSaveFields.forEach((fieldId) => {
      const field = document.getElementById(fieldId);
      if (field) {
        const submitForm = () => {
          clearTimeout(saveTimeout);
          saveTimeout = setTimeout(() => {
            editDetailsForm.dispatchEvent(new Event('submit'));
          }, 800);
        };

        field.addEventListener('change', submitForm);
      }
    });
  };

  /* ── Change Email modal flow ── */

  const normalizeVerificationCode = (value) => String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);

  const updateChangeEmailVerifyState = () => {
    const verifyBtn = document.getElementById('change_email_verify_btn');
    const oldCodeInput = document.getElementById('change_email_old_code');
    const newCodeInput = document.getElementById('change_email_new_code');
    if (!verifyBtn || !oldCodeInput || !newCodeInput) {
      return;
    }

    const oldCode = normalizeVerificationCode(oldCodeInput.value);
    const newCode = normalizeVerificationCode(newCodeInput.value);
    const canVerify = oldCode.length >= 6 && newCode.length >= 6;

    verifyBtn.disabled = !canVerify;
    verifyBtn.setAttribute('aria-disabled', canVerify ? 'false' : 'true');
  };

  const toggleChangeEmailStep = (showStep2) => {
    const step1 = document.getElementById('change_email_step1_section');
    const step2 = document.getElementById('change_email_step2_section');
    const startBtn = document.getElementById('change_email_start_btn');
    const verifyBtn = document.getElementById('change_email_verify_btn');
    const resendBtn = document.getElementById('change_email_resend_btn');
    const prevBtn = document.getElementById('change_email_prev_btn');

    if (step1) step1.hidden = !!showStep2;
    if (step2) step2.hidden = !showStep2;
    if (startBtn) startBtn.hidden = !!showStep2;
    if (verifyBtn) verifyBtn.hidden = !showStep2;
    if (resendBtn) resendBtn.hidden = !showStep2;
    if (prevBtn) prevBtn.textContent = showStep2 ? 'Previous' : 'Cancel';

    updateChangeEmailVerifyState();
  };

  const resetChangeEmailModal = () => {
    document.getElementById('change_email_form')?.reset();
    const status = document.getElementById('change_email_status');
    const verifyStatus = document.getElementById('change_email_verify_status');
    const txn = document.getElementById('change_email_txn_id');
    const expiry = document.getElementById('change_email_expiry_timer');
    const oldHint = document.getElementById('old_email_hint');
    const newHint = document.getElementById('new_email_hint');
    if (status) status.textContent = '';
    if (verifyStatus) verifyStatus.textContent = '';
    if (txn) txn.value = '';
    if (expiry) expiry.textContent = '';
    if (oldHint) oldHint.textContent = '';
    if (newHint) newHint.textContent = '';
    clearFieldInvalidStates([
      'change_email_new_email',
      'change_email_confirm_email',
      'change_email_old_code',
      'change_email_new_code',
    ]);
    clearFieldErrorStates([
      ['change_email_new_email', 'change_email_new_email_error'],
      ['change_email_confirm_email', 'change_email_confirm_email_error'],
      ['change_email_old_code', 'change_email_old_code_error'],
      ['change_email_new_code', 'change_email_new_code_error'],
    ]);
    toggleChangeEmailStep(false);
  };

  const attachChangeEmailCodeInputHandlers = () => {
    ['change_email_old_code', 'change_email_new_code'].forEach((id) => {
      const input = document.getElementById(id);
      if (!input) {
        return;
      }

      const syncInput = () => {
        const normalized = normalizeVerificationCode(input.value);
        if (input.value !== normalized) {
          input.value = normalized;
        }
        const errorId = id === 'change_email_old_code' ? 'change_email_old_code_error' : 'change_email_new_code_error';
        setFieldErrorState(input, errorId, '');
        updateChangeEmailVerifyState();
      };

      input.addEventListener('input', syncInput);
      input.addEventListener('blur', syncInput);
    });
  };

  const parseChangeEmailApiResponse = async (response) => {
    const raw = await response.text();
    let data = null;
    try {
      data = JSON.parse(raw);
    } catch (_error) {
      data = null;
    }
    return { data, raw };
  };

  const CHANGE_EMAIL_I18N = {
    enterBothEmails: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_EMAILS')); ?>',
    enterNewEmail: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_ENTER_NEW_EMAIL')); ?>',
    confirmNewEmail: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_CONFIRM_NEW_EMAIL')); ?>',
    emailsNoMatch: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_EMAILS_NO_MATCH')); ?>',
    emailsMustMatch: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_EMAILS_MUST_MATCH')); ?>',
    working: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_WORKING')); ?>',
    codesSent: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_CODES_SENT')); ?>',
    requestFailedPrefix: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_REQUEST_FAILED_PREFIX')); ?>',
    enterBothCodes: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_CODES')); ?>',
    enterValid6CharCode: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_ENTER_VALID_6_CHAR_CODE')); ?>',
    emailUpdated: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_EMAIL_UPDATED')); ?>',
    sessionExpired: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_SESSION_EXPIRED')); ?>',
  };

  const bindChangeEmail = () => {
    const hasChangeEmailUi = Boolean(
      document.getElementById('change_email_prev_btn')
      && document.getElementById('change_email_start_btn')
      && document.getElementById('change_email_verify_btn')
      && document.getElementById('change_email_resend_btn')
    );
    if (!hasChangeEmailUi) {
      return;
    }

    attachChangeEmailCodeInputHandlers();

    PC.addClickAndEnterListener('change_email_prev_btn', (e) => {
      e.preventDefault();
      const step2 = document.getElementById('change_email_step2_section');
      if (step2 && !step2.hidden) {
        toggleChangeEmailStep(false);
        return;
      }

      resetChangeEmailModal();
      PC.closeModal('modal_change_email', 'Change Email');
    });

    PC.addClickAndEnterListener('change_email_start_btn', async (e) => {
      e.preventDefault();
      const newEmailInput = document.getElementById('change_email_new_email');
      const confirmEmailInput = document.getElementById('change_email_confirm_email');
      const newEmail = String(newEmailInput?.value || '').trim();
      const confirmEmail = String(confirmEmailInput?.value || '').trim();
      const statusEl = document.getElementById('change_email_status');

      setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
      setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');

      if (!newEmail || !confirmEmail) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothEmails;
        if (!newEmail) {
          setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.enterNewEmail);
        }
        if (!confirmEmail) {
          setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.confirmNewEmail);
        }
        (newEmail ? confirmEmailInput : newEmailInput)?.focus();
        return;
      }
      if (newEmail !== confirmEmail) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailsNoMatch;
        setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
        setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
        confirmEmailInput?.focus();
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const response = await fetch('/api/v1/account/change-email/start', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ new_email: newEmail }),
        });
        const { data, raw } = await parseChangeEmailApiResponse(response);

        if (response.ok && data && data.status === 'success') {
          setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
          setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');
          const txn = document.getElementById('change_email_txn_id');
          const oldHint = document.getElementById('old_email_hint');
          const newHint = document.getElementById('new_email_hint');
          const expiry = document.getElementById('change_email_expiry_timer');

          if (txn) txn.value = data.txn_id || '';
          if (oldHint) oldHint.textContent = data.old_email_hint || '';
          if (newHint) newHint.textContent = data.new_email_hint || '';
          if (expiry) expiry.textContent = `Codes expire in ${data.expires_in_minutes} minutes`;
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;

          toggleChangeEmailStep(true);
          setTimeout(() => document.getElementById('change_email_old_code')?.focus(), 50);
        } else {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          if (statusEl) statusEl.textContent = apiMessage || `Failed to send codes. ${fallback}`;
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || 'unknown error')}`;
        PW.error(error);
      }
    });

    PC.addClickAndEnterListener('change_email_verify_btn', async (e) => {
      e.preventDefault();
      const oldCodeInput = document.getElementById('change_email_old_code');
      const newCodeInput = document.getElementById('change_email_new_code');
      const txnId = String(document.getElementById('change_email_txn_id')?.value || '').trim();
      const oldCode = normalizeVerificationCode(oldCodeInput?.value || '');
      const newCode = normalizeVerificationCode(newCodeInput?.value || '');
      const statusEl = document.getElementById('change_email_verify_status');

      setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
      setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');

      if (!txnId || oldCode.length !== 6 || newCode.length !== 6) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothCodes;
        if (oldCode.length !== 6) {
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
        }
        if (newCode.length !== 6) {
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
        }
        (oldCode.length !== 6 ? oldCodeInput : newCodeInput)?.focus();
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const response = await fetch('/api/v1/account/change-email/verify', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ txn_id: txnId, old_code: oldCode, new_code: newCode }),
        });
        const { data, raw } = await parseChangeEmailApiResponse(response);

        if (response.ok && data && data.status === 'success') {
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailUpdated;
          setTimeout(() => {
            PC.closeModal('modal_change_email', 'Change Email');
            location.reload();
          }, 1000);
        } else if (statusEl) {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          const errorText = apiMessage || `Verification failed. ${fallback}`;
          statusEl.textContent = errorText;
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', errorText);
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', errorText);
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || 'unknown error')}`;
        PW.error(error);
      }
    });

    PC.addClickAndEnterListener('change_email_resend_btn', async (e) => {
      e.preventDefault();
      const txnId = String(document.getElementById('change_email_txn_id')?.value || '').trim();
      const statusEl = document.getElementById('change_email_verify_status');
      if (!txnId) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.sessionExpired;
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const response = await fetch('/api/v1/account/change-email/resend', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ txn_id: txnId }),
        });
        const { data, raw } = await parseChangeEmailApiResponse(response);
        if (response.ok && data && data.status === 'success') {
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;
        } else if (statusEl) {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          statusEl.textContent = apiMessage || `Failed to resend codes. ${fallback}`;
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || 'unknown error')}`;
        PW.error(error);
      }
    });
  };

  const bindDangerZone = () => {
    const deleteDataPill = document.getElementById('danger_delete_data_pill');
    const deleteDataPhraseInput = document.getElementById('danger_delete_data_phrase');
    const deleteDataConfirm = document.getElementById('danger_delete_data_confirm');

    const deleteAccountPill = document.getElementById('danger_delete_account_pill');
    const deleteAccountForm = document.getElementById('danger_delete_account_form');
    const deleteAccountPhraseInput = document.getElementById('danger_delete_account_phrase');
    const deleteAccountConfirm = document.getElementById('danger_delete_account_confirm');

    const dangerStatus = document.getElementById('danger_zone_status');

    if (!deleteDataPill && !deleteAccountPill) {
      return;
    }

    const updateDeleteDataConfirmState = () => {
      if (!(deleteDataPhraseInput instanceof HTMLInputElement) || !(deleteDataConfirm instanceof HTMLButtonElement)) {
        return;
      }

      const phrase = String(deleteDataPhraseInput.value || '').toUpperCase();
      deleteDataPhraseInput.value = phrase;
      deleteDataConfirm.disabled = phrase.trim() !== 'DELETE ALL DATA';
    };

    const updateDeleteAccountConfirmState = () => {
      if (!(deleteAccountPhraseInput instanceof HTMLInputElement) || !(deleteAccountConfirm instanceof HTMLButtonElement)) {
        return;
      }

      const phrase = String(deleteAccountPhraseInput.value || '').toUpperCase();
      deleteAccountPhraseInput.value = phrase;
      deleteAccountConfirm.disabled = phrase.trim() !== 'DELETE MY ACCOUNT';
    };

    deleteDataPhraseInput?.addEventListener('input', updateDeleteDataConfirmState);
    deleteAccountPhraseInput?.addEventListener('input', updateDeleteAccountConfirmState);

    updateDeleteDataConfirmState();
    updateDeleteAccountConfirmState();

    deleteAccountForm?.addEventListener('submit', (event) => {
      if (!(deleteAccountPhraseInput instanceof HTMLInputElement)) {
        return;
      }

      const phrase = String(deleteAccountPhraseInput.value || '').trim().toUpperCase();
      if (phrase !== 'DELETE MY ACCOUNT') {
        event.preventDefault();
        if (dangerStatus) {
          dangerStatus.textContent = 'Type DELETE MY ACCOUNT exactly to confirm account deletion.';
        }
        deleteAccountPhraseInput.focus();
        deleteAccountPhraseInput.select();
      }
    });

    deleteDataConfirm?.addEventListener('click', async () => {
      if (!(deleteDataConfirm instanceof HTMLButtonElement) || !(deleteDataPhraseInput instanceof HTMLInputElement)) {
        return;
      }

      const phrase = String(deleteDataPhraseInput.value || '').trim().toUpperCase();
      if (phrase !== 'DELETE ALL DATA') {
        if (dangerStatus) {
          dangerStatus.textContent = 'Type DELETE ALL DATA exactly to confirm data deletion.';
        }
        deleteDataPhraseInput.focus();
        deleteDataPhraseInput.select();
        return;
      }

      deleteDataConfirm.disabled = true;
      if (dangerStatus) {
        dangerStatus.textContent = 'Deleting all data...';
      }

      try {
        const formData = new FormData();
        formData.append('confirm_phrase', phrase);
        const settingsCsrfToken = String((document.getElementById('settings_csrf_token')?.value || '')).trim();
        if (settingsCsrfToken !== '') {
          formData.append('csrf_token', settingsCsrfToken);
        }

        const response = await fetch('/api/v1/account/data/delete/', {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (!response.ok || payload.status !== 'success') {
          throw new Error(payload.message || 'Unable to delete data right now.');
        }

        if (dangerStatus) {
          dangerStatus.textContent = 'All account data has been deleted.';
        }
        if (deleteDataPill instanceof HTMLElement) {
          deleteDataPill.hidden = true;
        }
      } catch (error) {
        if (dangerStatus) {
          dangerStatus.textContent = error instanceof Error ? error.message : 'Unable to delete data right now.';
        }
      } finally {
        updateDeleteDataConfirmState();
      }
    });

    updateDeleteDataConfirmState();
    updateDeleteAccountConfirmState();
  };

  const formatActivityTimestamp = (unixSeconds) => {
    const value = Number(unixSeconds || 0);
    if (!Number.isFinite(value) || value <= 0) {
      return 'Unknown';
    }

    return new Date(value * 1000).toLocaleString();
  };

  const createAccountActivityTimestampField = (unixSeconds, idSeed) => {
    const value = Number(unixSeconds || 0);
    if (!Number.isFinite(value) || value <= 0) {
      const fallback = document.createElement('span');
      fallback.textContent = T.unknown;
      return fallback;
    }

    const parsedDate = new Date(value * 1000);
    if (Number.isNaN(parsedDate.getTime())) {
      const fallback = document.createElement('span');
      fallback.textContent = T.unknown;
      return fallback;
    }

    const safeIdSeed = String(idSeed || 'activity')
      .replace(/[^a-zA-Z0-9_-]/g, '_')
      .slice(0, 64);
    const popoverId = `account_activity_timestamp_popover_${safeIdSeed}`;

    const field = document.createElement('span');
    field.className = 'account_activity_timestamp_field';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'account_activity_timestamp_trigger';
    trigger.textContent = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', popoverId);
    trigger.setAttribute('aria-expanded', 'false');

    const popover = document.createElement('div');
    popover.id = popoverId;
    popover.className = 'account_activity_timestamp_popover';
    popover.hidden = true;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', 'Timestamp details');

    const rows = [
      { label: 'Local', value: formatTimestampInTimeZone(parsedDate, viewerTimeZone) },
      { label: 'Server', value: formatTimestampInTimeZone(parsedDate, SERVER_TIMEZONE) },
      { label: 'UTC', value: formatTimestampInTimeZone(parsedDate, 'UTC') },
    ];

    rows.forEach((row) => {
      const rowEl = document.createElement('span');
      rowEl.className = 'account_activity_timestamp_popover_row';

      const labelEl = document.createElement('span');
      labelEl.className = 'account_activity_timestamp_popover_label';
      labelEl.textContent = `${row.label}:`;

      const valueEl = document.createElement('span');
      valueEl.className = 'account_activity_timestamp_popover_value';
      valueEl.textContent = row.value;

      rowEl.appendChild(labelEl);
      rowEl.appendChild(valueEl);
      popover.appendChild(rowEl);
    });

    field.appendChild(trigger);
    field.appendChild(popover);

    if (elements.accountActivityPanel instanceof HTMLElement) {
      bindHistoryTimestampPopover(elements.accountActivityPanel, trigger, popover);
    }

    return field;
  };

  const renderActivityDefinitionList = (target, rows) => {
    if (!(target instanceof HTMLElement)) {
      return;
    }

    target.textContent = '';
    rows.forEach((row, index) => {
      const dt = document.createElement('dt');
      dt.textContent = String(row.label || '');
      const dd = document.createElement('dd');
      if (row.timestampValue !== undefined) {
        dd.appendChild(createAccountActivityTimestampField(row.timestampValue, `${row.label || 'timestamp'}_${index}`));
      } else {
        dd.textContent = String(row.value || '');
      }
      target.appendChild(dt);
      target.appendChild(dd);
    });
  };

  const renderActivitySessions = (sessions) => {
    if (!(elements.accountActivitySessions instanceof HTMLElement)) {
      return;
    }

    elements.accountActivitySessions.textContent = '';

    if (!Array.isArray(sessions) || sessions.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'help_text';
      empty.textContent = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_NO_ACTIVE_SESSIONS')); ?>';
      elements.accountActivitySessions.appendChild(empty);
      return;
    }

    sessions.forEach((session) => {
      const item = document.createElement('article');
      item.className = 'account_activity_session_item';
      if (session && session.is_current === true) {
        item.classList.add('account_activity_session_item_current');
      }

      const title = document.createElement('strong');
      title.textContent = session && session.is_current === true
        ? `Current session (${String(session.session_fingerprint || 'unknown')})`
        : `Session ${String(session?.session_fingerprint || 'unknown')}`;

      const meta = document.createElement('div');
      meta.className = 'account_activity_session_meta';

      const makeMetaSegment = (label, valueNodeOrText) => {
        const segment = document.createElement('span');
        segment.className = 'account_activity_session_meta_segment';

        const labelEl = document.createElement('span');
        labelEl.className = 'account_activity_session_meta_label';
        labelEl.textContent = `${label}: `;
        segment.appendChild(labelEl);

        if (valueNodeOrText instanceof Node) {
          segment.appendChild(valueNodeOrText);
        } else {
          const valueEl = document.createElement('span');
          valueEl.textContent = String(valueNodeOrText || 'Unknown');
          segment.appendChild(valueEl);
        }

        return segment;
      };

      meta.appendChild(makeMetaSegment('Last activity', createAccountActivityTimestampField(session?.last_activity, `${session?.session_fingerprint || 'session'}_last_activity`)));
      meta.appendChild(makeMetaSegment('Signed in', createAccountActivityTimestampField(session?.created_at, `${session?.session_fingerprint || 'session'}_signed_in`)));
      meta.appendChild(makeMetaSegment('IP', String(session?.last_ip || 'unknown')));
      meta.appendChild(makeMetaSegment('Auth', String(session?.auth_method || 'unknown')));
      meta.appendChild(makeMetaSegment('TTL', `${String(session?.ttl_seconds || 0)}s`));

      item.appendChild(title);
      item.appendChild(meta);
      elements.accountActivitySessions.appendChild(item);
    });
  };

  const loadAccountActivity = async () => {
    if (!(elements.accountActivityStatus instanceof HTMLElement)) {
      return;
    }

    elements.accountActivityStatus.textContent = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_LOADING_ACCOUNT_ACTIVITY')); ?>';

    try {
      const response = await fetch('/api/v1/user/account/activity', {
        method: 'GET',
        credentials: 'same-origin',
        headers: buildHeaders(),
      });

      if (!response.ok) {
        throw new Error(`Failed to load account activity (${response.status})`);
      }

      const payload = await response.json();
      const data = payload?.data && typeof payload.data === 'object' ? payload.data : {};
      const currentLogin = data?.current_login && typeof data.current_login === 'object' ? data.current_login : {};
      const browser = data?.browser && typeof data.browser === 'object' ? data.browser : {};
      const sessionData = data?.session_data && typeof data.session_data === 'object' ? data.session_data : {};
      const sessions = Array.isArray(sessionData.sessions) ? sessionData.sessions : [];

      renderActivityDefinitionList(elements.accountActivityLoginDetails, [
        { label: 'IP Address', value: String(currentLogin.ip_address || 'unknown') },
        { label: 'Signed In', timestampValue: currentLogin.signed_in_at },
        { label: 'Last Activity', timestampValue: currentLogin.last_activity_at },
        { label: 'Authentication Method', value: String(currentLogin.auth_method || 'unknown') },
        { label: 'Authentication Strength', value: String(currentLogin.auth_strength || 'unknown') },
        { label: 'Session Fingerprint', value: String(currentLogin.session_fingerprint || 'unknown') },
      ]);

      renderActivityDefinitionList(elements.accountActivityBrowserDetails, [
        { label: 'Browser', value: `${String(browser.browser_name || 'Unknown')} ${String(browser.browser_version || '').trim()}`.trim() },
        { label: 'Operating System', value: String(browser.os_name || 'Unknown') },
        { label: 'Device Type', value: String(browser.device_type || 'Unknown') },
        { label: 'Platform', value: String(browser.platform || 'Unknown') },
        { label: 'Language', value: String(browser.language || 'Unknown') },
        { label: 'User Agent', value: String(browser.user_agent || 'Unknown') },
      ]);

      renderActivitySessions(sessions);

      elements.accountActivityStatus.textContent = `Loaded ${sessions.length} active session${sessions.length === 1 ? '' : 's'}.`;
    } catch (error) {
      PW.error(error);
      elements.accountActivityStatus.textContent = error instanceof Error
        ? error.message
        : 'Unable to load account activity right now.';
    }
  };

  const bindEvents = () => {
    elements.searchForm?.addEventListener('submit', (event) => {
      handleSearchOrganizations(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.loadOrgsFailed;
        setDiscoveryPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    elements.requestJoinForm?.addEventListener('submit', (event) => {
      handleRequestJoinOrganization(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.requestJoinFailed;
        setDiscoveryPanelStatus(message);
        setBrowserPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    elements.browserSearchForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      const query = elements.browserSearchInput instanceof HTMLInputElement
        ? String(elements.browserSearchInput.value || '')
        : '';
      runBrowserSearch(query).catch((error) => {
        PW.error(error);
        setBrowserPanelStatus('Organization search failed. Try again.');
      });
    });
    elements.browserSearchInput?.addEventListener('input', () => {
      if (state.browserSearchDebounceId !== null) {
        window.clearTimeout(state.browserSearchDebounceId);
      }

      const query = elements.browserSearchInput instanceof HTMLInputElement
        ? String(elements.browserSearchInput.value || '')
        : '';

      state.browserSearchDebounceId = window.setTimeout(() => {
        runBrowserSearch(query).catch((error) => {
          PW.error(error);
          setBrowserPanelStatus('Organization search failed. Try again.');
        });
        state.browserSearchDebounceId = null;
      }, ORG_BROWSER_SEARCH_DEBOUNCE_MS);
    });
    const handleBrowserGridAction = (event) => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-browser-action="connect"]')
        : null;
      if (!(target instanceof HTMLButtonElement)) {
        return;
      }

      const email = String(target.dataset.email || '').trim();
      const organizationName = String(target.dataset.orgName || '').trim();
      const ownerName = String(target.dataset.ownerName || '').trim();

      connectToOrganizationFromBrowser(email, organizationName, ownerName).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.requestJoinFailed;
        setBrowserPanelStatus(message);
        setDiscoveryPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    };
    elements.browserGrid?.addEventListener('click', handleBrowserGridAction);
    elements.browserRecentGrid?.addEventListener('click', handleBrowserGridAction);
    elements.currentInfoLink?.addEventListener('click', () => {
      openCurrentOrganizationDetailsDialog();
    });
    elements.currentRevokeButton?.addEventListener('click', () => {
      handleRevokeCurrentOrganizationAccess().catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.withdrawFailed;
        setCurrentOrganizationStatus(message, 'error');
        PC.showToast(message, 'error', 7000, true);
      });
    });
    document.querySelectorAll('[data-dialog-close="organizations_current_details_dialog"]').forEach((button) => {
      button.addEventListener('click', () => {
        closeCurrentOrganizationDetailsDialog();
      });
    });
    elements.memberForm?.addEventListener('submit', (event) => {
      handleMemberPersonalOrganization(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.inviteSendFailed;
        setDiscoveryPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    bindAccessLookupInput(elements.requestEmail, elements.requestLookupDatalist);
    document.querySelectorAll('.organizations_access_level_pillbox .pill').forEach((pill) => {
      pill.addEventListener('click', (event) => {
        event.preventDefault();
        const button = event.currentTarget;
        if (!(button instanceof HTMLButtonElement)) {
          return;
        }
        const accessLevel = String(button.dataset.accessLevel || '').trim();
        if (accessLevel === '') {
          return;
        }
        state.requestAccessLevel = accessLevel;
        document.querySelectorAll('.organizations_access_level_pillbox .pill').forEach((p) => {
          p.classList.toggle('pill_selected', p === button);
        });
      });
    });
    elements.bootstrapDekButton?.addEventListener('click', () => {
      handleBootstrapOrganizationDek().catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message
          ? error.message
          : T.orgDekBootstrapFailed;
        announceLiveToast(message, 'error');
        PC.showToast(message, 'error', 9000, true);
      });
    });
    elements.saveButton?.addEventListener('click', handleSaveOrganization);
    elements.inviteSend?.addEventListener('click', handleSendInvite);
    elements.scopeGrid?.addEventListener('change', (event) => {
      const input = event.target;
      if (input instanceof HTMLInputElement && input.classList.contains('organizations_scope')) {
        announceScopeSelectionStatus('updated');
      }
    });
    elements.invitesReload?.addEventListener('click', () => {
      if (state.selectedOrganizationId !== '') {
        loadOrganizationInvites(state.selectedOrganizationId).catch((error) => {
          PW.error(error);
          if (elements.invitesList instanceof HTMLElement) {
            setStackMessage(elements.invitesList, T.loadInvitesFailed);
          }
          PC.showToast(T.loadInvitesFailed, 'error', 7000, true);
        });
      }
    });
    elements.liveRequestsList?.addEventListener('click', (event) => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-live-request-action]')
        : null;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const action = String(target.dataset.liveRequestAction || '');
      const organizationId = String(target.dataset.liveOrgId || '');
      const requestId = String(target.dataset.liveRequestId || '');
      handleLiveRequestAction(organizationId, requestId, action).catch((error) => {
        PW.error(error);
        announceLiveRequestsStatus('Unable to update access request.');
        PC.showToast(T.accessRequestActionFailed, 'error', 7000, true);
      });
    });
    elements.relationshipsReload?.addEventListener('click', () => {
      if (state.selectedOrganizationId !== '') {
        loadOrganizationRelationships(state.selectedOrganizationId).catch((error) => {
          PW.error(error);
          const message = error instanceof Error && error.message ? error.message : T.manageAccessUnavailable;
          setCurrentOrganizationStatus(message, 'error');
          PC.showToast(message, 'error', 7000, true);
        });
      }
    });
    elements.transferTarget?.addEventListener('input', () => {
      if (elements.transferTargetUUID instanceof HTMLInputElement) {
        elements.transferTargetUUID.value = '';
      }
      syncTransferTargetFromLookup();
    });
    elements.transferTarget?.addEventListener('change', () => {
      syncTransferTargetFromLookup();
      if (elements.transferTargetUUID instanceof HTMLInputElement && elements.transferTargetUUID.value === '') {
        PC.showToast('Select a current member from the list to transfer ownership.', 'error', 4500, true);
      }
    });
    elements.transferConfirmation?.addEventListener('input', () => {
      syncTransferConfirmation();
    });
    elements.transferConfirmation?.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }

      const typed = event.currentTarget instanceof HTMLInputElement
        ? String(event.currentTarget.value || '')
        : '';
      const normalizedTyped = typed
        .toUpperCase()
        .replace(/[^A-Z\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
      const ready = normalizedTyped === 'TRANSFER ORGANIZATION'
        && state.transferSelectedUUID !== ''
        && elements.transferButton instanceof HTMLButtonElement
        && !elements.transferButton.disabled;
      if (!ready) {
        return;
      }

      event.preventDefault();
      handleTransferOwnership();
    });
    elements.transferSelectedMember?.addEventListener('click', (event) => {
      const actionTarget = event.target instanceof Element
        ? event.target.closest('[data-transfer-selection-action="deselect"]')
        : null;
      if (!(actionTarget instanceof HTMLButtonElement)) {
        return;
      }

      clearTransferSelection(true);
      if (elements.transferTarget instanceof HTMLInputElement) {
        elements.transferTarget.focus();
      }
    });
    elements.transferButton?.addEventListener('click', handleTransferOwnership);
    elements.leaveButton?.addEventListener('click', handleLeaveOrganization);
    elements.auditReload?.addEventListener('click', () => {
      if (state.selectedOrganizationId !== '') {
        loadOrganizationAudit(state.selectedOrganizationId).catch((error) => {
          PW.error(error);
          PC.showToast(T.loadAuditFailed, 'error', 7000, true);
        });
      }
    });


    /**
     * Audit grid row-click handler: opens popover with full event details
     */
    let auditDetailsPopoverState = null;
    let auditEventDetailsMap = {}; // Map of grid IDs to event details

    const closeAuditDetailsPopover = () => {
      if (auditDetailsPopoverState) {
        const { popover, trigger } = auditDetailsPopoverState;
        popover.hidden = true;
        if (trigger instanceof HTMLElement) {
          trigger.setAttribute('aria-expanded', 'false');
        }
        auditDetailsPopoverState = null;
      }
    };

    const openAuditDetailsPopoverFor = (trigger, popover) => {
      if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
        return;
      }

      if (auditDetailsPopoverState && auditDetailsPopoverState.popover !== popover) {
        closeAuditDetailsPopover();
      }

      const portalParent = trigger.closest('dialog[open]') || document.body;
      if (popover.parentElement !== portalParent) {
        portalParent.appendChild(popover);
      }

      popover.hidden = false;
      positionHistoryTimestampPopover(trigger, popover);
      window.requestAnimationFrame(() => {
        positionHistoryTimestampPopover(trigger, popover);
      });
      trigger.setAttribute('aria-expanded', 'true');
      auditDetailsPopoverState = { trigger, popover };
    };

    const handleAuditGridRowClick = (event) => {
      const row = event.target instanceof Element
        ? event.target.closest('[role="row"].datagrid_row')
        : null;
      if (!(row instanceof HTMLElement)) {
        return;
      }

      // Find the grid container and get the grid ID
      const gridContainer = row.closest('[data-grid]');
      if (!(gridContainer instanceof HTMLElement)) {
        return;
      }

      const gridId = String(gridContainer.dataset.grid || '');
      const rowId = String(row.dataset.id || '');

      if (gridId === '' || rowId === '') {
        return;
      }

      // Look up event details from the embedded script tag
      const detailsScript = document.getElementById(gridId + '_event_details');
      if (!(detailsScript instanceof HTMLElement)) {
        return;
      }

      let allEventDetails = {};
      try {
        const rawJson = String(detailsScript.dataset.eventDetailsJson || '{}');
        allEventDetails = JSON.parse(rawJson);
      } catch {
        allEventDetails = {};
      }

      const eventDetails = allEventDetails[rowId] || {};
      const detailsJson = String(eventDetails.event_details_json || '{}');
      let detailsMap = {};
      try {
        detailsMap = JSON.parse(detailsJson);
      } catch {
        detailsMap = {};
      }

      const eventType = String(eventDetails.event_type || '').trim();
      const actor = String(eventDetails.actor || '').trim();
      const target = String(eventDetails.target || '').trim();
      const timestamp = String(eventDetails.created_at || '').trim();

      console.log('Event details found:', { eventType, actor, target, timestamp, detailsMap });

      // Build details HTML using CSS classes (no inline styles)
      let detailsHtml = '<div class="organizations_audit_details_popover_container">';
      detailsHtml += `<div class="organizations_audit_details_popover_field"><strong>Event:</strong> ${Guardian.sanitizedText(eventType)}</div>`;
      detailsHtml += `<div class="organizations_audit_details_popover_field"><strong>Actor:</strong> ${Guardian.sanitizedText(actor)}</div>`;
      detailsHtml += `<div class="organizations_audit_details_popover_field"><strong>Target:</strong> ${Guardian.sanitizedText(target)}</div>`;
      detailsHtml += `<div class="organizations_audit_details_popover_field"><strong>Timestamp:</strong> ${Guardian.sanitizedText(timestamp)}</div>`;
      
      if (Object.keys(detailsMap).length > 0) {
        detailsHtml += '<div class="organizations_audit_details_popover_divider"><strong>Details:</strong></div>';
        for (const [key, value] of Object.entries(detailsMap)) {
          detailsHtml += `<div class="organizations_audit_details_popover_details_item"><strong>${Guardian.sanitizedText(String(key))}:</strong> ${Guardian.sanitizedText(String(value))}</div>`;
        }
      }
      detailsHtml += '</div>';

      // Create or reuse popover
      let popover = document.getElementById('organizations_audit_details_popover');
      if (!popover) {
        popover = document.createElement('div');
        popover.id = 'organizations_audit_details_popover';
        popover.className = 'organizations_history_timestamp_popover';
        popover.setAttribute('role', 'tooltip');
        document.body.appendChild(popover);
      }

      popover.innerHTML = detailsHtml;
      openAuditDetailsPopoverFor(row, popover);
    };

    // Close popover on body click outside
    document.addEventListener('click', (event) => {
      if (auditDetailsPopoverState && event.target instanceof Element) {
        const popover = auditDetailsPopoverState.popover;
        if (!popover.contains(event.target) && !auditDetailsPopoverState.trigger?.contains(event.target)) {
          closeAuditDetailsPopover();
        }
      }
    }, true);

    elements.auditGridContainer?.addEventListener('click', handleAuditGridRowClick);
    elements.freeAuditGridContainer?.addEventListener('click', handleAuditGridRowClick);

    elements.discoveryRun?.addEventListener('click', handleDiscovery);
    elements.personalPayFrequency?.addEventListener('change', () => {
      syncPersonalFrequency();
      schedulePersonalPreviewRender();
      schedulePersonalAutoSave(180, 'frequency');
    });
    elements.personalPayAnchor?.addEventListener('change', () => {
      schedulePersonalPreviewRender();
      schedulePersonalAutoSave(180, 'anchor');
    });
    Array.from(elements.personalEditingGraceDayRadios).forEach((radio) => {
      radio.addEventListener('change', handlePersonalGraceDaysChange);
    });
    elements.personalEditingGraceDays?.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target.closest('label[for]') : null;
      if (!(target instanceof HTMLLabelElement)) {
        return;
      }

      const radioId = String(target.getAttribute('for') || '');
      const radio = radioId !== '' ? document.getElementById(radioId) : null;
      if (!(radio instanceof HTMLInputElement) || radio.checked) {
        return;
      }

      event.preventDefault();
      radio.checked = true;
      radio.dispatchEvent(new Event('change', { bubbles: true }));
    });
    initCurrencyFinder('organizations_personal_currency_search', 'organizations_personal_currency', 'organizations_personal_currency_listbox', 'organizations_personal_currency_finder');
    initCurrencyFinder('organizations_editor_currency_search', 'organizations_editor_currency', 'organizations_editor_currency_listbox', 'organizations_editor_currency_finder');
    initTimezoneFinder('organizations_personal_timezone_search', 'organizations_personal_timezone', 'organizations_personal_timezone_listbox', 'organizations_personal_timezone_finder');
    initTimezoneFinder('organizations_editor_timezone_search', 'organizations_editor_timezone', 'organizations_editor_timezone_listbox', 'organizations_editor_timezone_finder');
    [elements.personalName, elements.personalDefaultWage, elements.personalTimezone, elements.personalCurrency].forEach((input) => {
      input?.addEventListener('change', () => {
        schedulePersonalAutoSave(180, 'details');
      });
    });
    if (elements.personalDefaultWage instanceof HTMLInputElement) {
      let personalWageInputDebounceId = null;
      elements.personalDefaultWage.addEventListener('input', () => {
        if (personalWageInputDebounceId !== null) {
          window.clearTimeout(personalWageInputDebounceId);
        }

        personalWageInputDebounceId = window.setTimeout(() => {
          schedulePersonalAutoSave(180, 'details');
          personalWageInputDebounceId = null;
        }, 500);
      });
    }
    elements.personalPreview?.addEventListener('click', handlePersonalPreviewInteraction);
    elements.personalPreview?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handlePersonalPreviewInteraction(event);
      }
    });
    elements.payFrequency?.addEventListener('change', () => {
      renderPreview();
      scheduleEditorAutoSave(240, 'pay-frequency');
    });
    Array.from(elements.editorEditingGraceDayRadios).forEach((radio) => {
      radio.addEventListener('change', () => {
        renderPreview();
        scheduleEditorAutoSave(240, 'grace-days');
      });
    });
    EDITOR_AUTOSAVE_SOURCE_IDS.forEach((fieldId) => {
      const field = document.getElementById(fieldId);
      if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
        return;
      }

      if (field instanceof HTMLInputElement && fieldId.endsWith('_phone')) {
        applyPhoneInputFormatting(field);
      }

      field.addEventListener('change', () => {
        if (!guardSensitiveEditorFieldChange(fieldId)) {
          return;
        }
        if (fieldId === 'organizations_editor_enforce_contact_domain' || fieldId === 'organizations_editor_allowed_contact_domains') {
          updateDomainPolicyStatus();
        }
        scheduleEditorAutoSave(420, `editor:${fieldId}`);
      });

      if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
        field.addEventListener('input', () => {
          if (field instanceof HTMLInputElement) {
            if (fieldId.endsWith('_phone')) {
              applyPhoneInputFormatting(field);
            }
            syncContactAvatarPreview(field);
          }
          if (fieldId === 'organizations_editor_enforce_contact_domain' || fieldId === 'organizations_editor_allowed_contact_domains') {
            updateDomainPolicyStatus();
          }
          scheduleEditorAutoSave(700, `editor:${fieldId}`);
        });
      }
    });

    updateDomainPolicyStatus();

    elements.contactCardAdd?.addEventListener('click', () => {
      state.customContactCards.push({
        id: uid(),
        name: '',
        email: '',
        phone: '',
        role: '',
        image_url: '',
      });
      syncCustomCardsHiddenInput();
      renderCustomContactCards();
      scheduleEditorAutoSave(220, 'custom-contact-add');
    });

    elements.customCardsContainer?.addEventListener('input', (event) => {
      const field = event.target;
      if (!(field instanceof HTMLInputElement) || !field.classList.contains('organizations_contact_custom_input')) {
        return;
      }

      const cardId = String(field.dataset.customCardId || '');
      const fieldName = String(field.dataset.customField || '');
      if (cardId === '' || fieldName === '') {
        return;
      }

      if (fieldName === 'phone') {
        applyPhoneInputFormatting(field);
      }

      upsertCustomCardField(cardId, fieldName, field.value);
      scheduleEditorAutoSave(520, `custom-contact:${fieldName}`);
    });

    formatPhoneInputsWithin(elements.dialog ?? document);

    document.querySelectorAll('.organizations_contact_image_input').forEach((field) => {
      if (field instanceof HTMLInputElement) {
        syncContactAvatarPreview(field);
      }
    });

    elements.dialog?.addEventListener('click', (event) => {
      const menuToggle = event.target instanceof Element
        ? event.target.closest('.organizations_contact_card_menu_toggle')
        : null;
      if (menuToggle instanceof HTMLButtonElement) {
        const menu = menuToggle.closest('.organizations_contact_card_menu');
        if (menu instanceof HTMLElement) {
          const shouldOpen = !menu.classList.contains('is_open');
          closeAllContactCardMenus(shouldOpen ? menu : null);
          setContactCardMenuOpen(menu, shouldOpen);
        }
        return;
      }

      const deleteButton = event.target instanceof Element
        ? event.target.closest('.organizations_contact_card_menu_delete')
        : null;
      if (deleteButton instanceof HTMLButtonElement) {
        handleContactCardDeleteAction(deleteButton);
        return;
      }

      const avatar = event.target instanceof Element
        ? event.target.closest('.organizations_contact_card_avatar')
        : null;
      if (!(avatar instanceof HTMLImageElement)) {
        return;
      }

      const targetField = getImageFieldForAvatar(avatar);
      if (!(targetField instanceof HTMLInputElement)) {
        return;
      }

      openContactImagePopover(targetField, avatar);
    });

    elements.dialog?.addEventListener('error', (event) => {
      const target = event.target;
      if (!(target instanceof HTMLImageElement) || !target.classList.contains('organizations_contact_card_avatar')) {
        return;
      }
      if (target.src !== CONTACT_AVATAR_PLACEHOLDER_SRC) {
        target.src = CONTACT_AVATAR_PLACEHOLDER_SRC;
      }
      target.alt = '';
      target.setAttribute('role', 'presentation');
    }, true);

    elements.contactImageDropzone?.addEventListener('click', () => {
      elements.contactImageFile?.click();
    });

    elements.contactImageDropzone?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        elements.contactImageFile?.click();
      }
    });

    elements.contactImageDropzone?.addEventListener('dragover', (event) => {
      event.preventDefault();
      elements.contactImageDropzone?.classList.add('is_dragover');
    });

    elements.contactImageDropzone?.addEventListener('dragleave', () => {
      elements.contactImageDropzone?.classList.remove('is_dragover');
    });

    elements.contactImageDropzone?.addEventListener('drop', (event) => {
      event.preventDefault();
      elements.contactImageDropzone?.classList.remove('is_dragover');
      const files = event.dataTransfer?.files;
      handleContactImageFiles(files).catch((error) => PW.error(error));
    });

    elements.contactImageFile?.addEventListener('change', () => {
      const files = elements.contactImageFile?.files;
      handleContactImageFiles(files).catch((error) => PW.error(error));
      if (elements.contactImageFile instanceof HTMLInputElement) {
        elements.contactImageFile.value = '';
      }
    });

    elements.contactImageClear?.addEventListener('click', () => {
      applyContactImageValue('');
    });

    elements.contactImageCancel?.addEventListener('click', () => {
      closeContactImagePopover();
    });

    document.addEventListener('mousedown', (event) => {
      const target = event.target;
      if (target instanceof Element && !target.closest('.organizations_contact_card_menu')) {
        closeAllContactCardMenus();
      }

      if (!(elements.contactImagePopover instanceof HTMLElement) || elements.contactImagePopover.classList.contains('hidden')) {
        return;
      }

      if (!(target instanceof Element)) {
        return;
      }

      const insidePopover = elements.contactImagePopover.contains(target);
      const onAvatar = target.closest('.organizations_contact_card_avatar');
      if (!insidePopover && !onAvatar) {
        closeContactImagePopover();
      }
    });

    elements.preview?.addEventListener('click', handleEditorPreviewInteraction);
    elements.preview?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handleEditorPreviewInteraction(event);
      }
    });
    elements.closeButton?.addEventListener('click', closeDialog);
    elements.dialog?.addEventListener('click', (event) => {
      if (event.target === elements.dialog) {
        closeDialog();
      }
    });

    document.addEventListener('click', (event) => {
      handleGridClick(event).catch((error) => PW.error(error));
    });
    document.addEventListener('input', handleGridInput);
    document.addEventListener('keydown', (event) => {
      handleGridKeydown(event).catch((error) => PW.error(error));
    });

    // Create Organization Dialog
    if (elements.createButton instanceof HTMLButtonElement) {
      elements.createButton.addEventListener('click', () => {
        if (elements.createDialog instanceof HTMLDialogElement && !elements.createDialog.open) {
          elements.createDialog.showModal();
          elements.createName?.focus();
        }
      });
    }

    if (elements.definitionsHelpButton instanceof HTMLButtonElement) {
      elements.definitionsHelpButton.addEventListener('click', () => {
        if (elements.definitionsDialog instanceof HTMLDialogElement && !elements.definitionsDialog.open) {
          elements.definitionsDialog.showModal();
          elements.definitionsCloseButton?.focus();
        }
      });
    }

    if (elements.definitionsCloseButton instanceof HTMLButtonElement) {
      elements.definitionsCloseButton.addEventListener('click', () => {
        if (elements.definitionsDialog instanceof HTMLDialogElement && elements.definitionsDialog.open) {
          elements.definitionsDialog.close();
        }
      });
    }

    if (elements.definitionsDialog instanceof HTMLDialogElement) {
      elements.definitionsDialog.addEventListener('click', (event) => {
        if (event.target === elements.definitionsDialog) {
          elements.definitionsDialog.close();
        }
      });

      elements.definitionsDialog.addEventListener('close', () => {
        elements.definitionsHelpButton?.focus();
      });
    }

    if (elements.createForm instanceof HTMLFormElement) {
      elements.createForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const nameValue = String(elements.createName?.value || '').trim();
        if (nameValue === '') {
          if (elements.createNameError instanceof HTMLElement) {
            elements.createNameError.textContent = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_CREATE_NAME_REQUIRED')); ?>';
            elements.createNameError.classList.remove('hidden');
          }
          elements.createName?.focus();
          return;
        }

        if (elements.createSubmit instanceof HTMLButtonElement) {
          elements.createSubmit.disabled = true;
        }

        try {
          const response = await fetch('/api/v1/organizations/create', {
            method: 'POST',
            headers: buildHeaders(),
            body: new URLSearchParams({
              name: nameValue,
              csrf_token: String(elements.csrfToken?.value || ''),
            }),
          });

          const payload = await response.json();
          if (payload.status !== 'success') {
            throw new Error(payload.message || T.createFailed);
          }

          PC.showToast(T.created, 'success', 3000);
          if (elements.createDialog instanceof HTMLDialogElement && elements.createDialog.open) {
            elements.createDialog.close();
          }
          elements.createForm?.reset();
          await refreshIndex(payload.organization_id || '', true);
        } catch (error) {
          PC.showToast(error instanceof Error ? error.message : T.createFailed, 'error', 5000, true);
          if (elements.createStatus instanceof HTMLElement) {
            elements.createStatus.textContent = error instanceof Error ? error.message : T.createFailed;
          }
        } finally {
          if (elements.createSubmit instanceof HTMLButtonElement) {
            elements.createSubmit.disabled = false;
          }
        }
      });
    }

    bindProfileEditDetails();
    bindChangeEmail();
    bindDangerZone();
  };

  const initializeProfileBilling = async () => {
    const upgradeBtn = document.getElementById('billing_upgrade_btn');
    const routeGateDialog = document.getElementById('organizations_route_gate_dialog');
    const routeGateCloseBtn = document.getElementById('organizations_route_gate_close_btn');
    const routeGateCloseX = document.getElementById('organizations_route_gate_close_x');
    const routeGateBillingBtn = document.getElementById('organizations_route_gate_billing_btn');
    const billingPanel = document.getElementById('panel-billing');

    const params = new URLSearchParams(window.location.search);

    const clearOrganizationsRouteIntent = () => {
      if (!params.has('from_organizations')) {
        return;
      }

      params.delete('from_organizations');
      const nextQuery = params.toString();
      const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}${window.location.hash}`;
      window.history.replaceState({}, document.title, nextUrl);
    };

    const closeRouteGateDialog = () => {
      if (routeGateDialog instanceof HTMLDialogElement && routeGateDialog.open) {
        routeGateDialog.close();
      }
    };

    if (routeGateCloseBtn instanceof HTMLButtonElement) {
      routeGateCloseBtn.addEventListener('click', closeRouteGateDialog);
    }

    if (routeGateCloseX instanceof HTMLButtonElement) {
      routeGateCloseX.addEventListener('click', closeRouteGateDialog);
    }

    if (routeGateDialog instanceof HTMLDialogElement) {
      routeGateDialog.addEventListener('click', (event) => {
        if (event.target === routeGateDialog) {
          closeRouteGateDialog();
        }
      });
    }

    if (routeGateBillingBtn instanceof HTMLButtonElement) {
      routeGateBillingBtn.addEventListener('click', () => {
        closeRouteGateDialog();
        billingPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(() => {
          if (upgradeBtn instanceof HTMLButtonElement) {
            upgradeBtn.focus();
          }
        }, 180);
      });
    }

    const billingController = await initializeBillingSection({
      successUrl: '/api/v1/billing/checkout-return',
      cancelUrl: '/profile/?billing=cancel',
      returnUrl: '/profile/#panel-billing',
      onPremiumActivated: () => {
        closeRouteGateDialog();
      },
    });

    const subData = billingController.subscription;

    if (params.get('from_organizations') === '1' && !(subData && subData.is_premium)) {
      clearOrganizationsRouteIntent();
      if (routeGateDialog instanceof HTMLDialogElement && !routeGateDialog.open) {
        routeGateDialog.showModal();
      }
    }
  };

  const initializeHoverHelp = () => {
    const targets = Array.from(document.querySelectorAll('[data-hover-help]'));
    if (targets.length === 0) {
      return;
    }

    const tooltipEl = document.createElement('div');
    tooltipEl.className = 'hover_help_tooltip';
    tooltipEl.setAttribute('role', 'tooltip');
    tooltipEl.setAttribute('aria-hidden', 'true');
    document.body.appendChild(tooltipEl);

    let showTimer = null;
    let activeTarget = null;

    const clearShowTimer = () => {
      if (showTimer !== null) {
        window.clearTimeout(showTimer);
        showTimer = null;
      }
    };

    const ensureTooltipContainer = (targetEl) => {
      if (!(targetEl instanceof HTMLElement)) {
        return;
      }
      const openDialog = targetEl.closest('dialog[open]');
      const desiredParent = openDialog instanceof HTMLElement ? openDialog : document.body;
      if (tooltipEl.parentElement !== desiredParent) {
        desiredParent.appendChild(tooltipEl);
      }
    };

    const hideTooltip = () => {
      clearShowTimer();
      activeTarget = null;
      tooltipEl.classList.remove('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'true');
    };

    const positionTooltip = (targetEl) => {
      if (!targetEl) {
        return;
      }
      const margin = '1.5rem';
      tooltipEl.style.bottom = margin;
      tooltipEl.style.right = margin;
      tooltipEl.style.top = 'auto';
      tooltipEl.style.left = 'auto';
    };

    const showTooltip = (targetEl) => {
      const helpText = (targetEl?.getAttribute('data-hover-help') || '').trim();
      if (!helpText) {
        return;
      }

      activeTarget = targetEl;
      ensureTooltipContainer(targetEl);
      tooltipEl.textContent = helpText;
      tooltipEl.classList.add('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'false');
      positionTooltip(targetEl);
    };

    const scheduleShow = (targetEl) => {
      clearShowTimer();
      showTimer = window.setTimeout(() => {
        showTimer = null;
        showTooltip(targetEl);
      }, 250);
    };

    targets.forEach((targetEl) => {
      targetEl.addEventListener('mouseenter', () => scheduleShow(targetEl));
      targetEl.addEventListener('mouseleave', hideTooltip);
      targetEl.addEventListener('focus', () => scheduleShow(targetEl));
      targetEl.addEventListener('blur', hideTooltip);
      targetEl.addEventListener('mousedown', hideTooltip);
    });

    window.addEventListener('scroll', () => {
      if (activeTarget && tooltipEl.classList.contains('is-visible')) {
        positionTooltip(activeTarget);
      }
    }, true);
  };

  // ── Tab Navigation ──────────────────────────────────────────
  const initializeTabNavigation = () => {
    const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
    const tabPanels = Array.from(document.querySelectorAll('[role="tabpanel"]'));

    if (tabs.length === 0 || tabPanels.length === 0) {
      return;
    }

    const switchTab = (tabElement) => {
      const targetPanelId = tabElement.getAttribute('aria-controls');
      
      // Deactivate all tabs and hide all panels
      tabs.forEach((tab) => {
        tab.setAttribute('aria-selected', 'false');
      });
      tabPanels.forEach((panel) => {
        panel.classList.add('organizations_members_panel_hidden');
        panel.classList.remove('is-visible');
      });

      // Activate clicked tab and show corresponding panel
      tabElement.setAttribute('aria-selected', 'true');
      const targetPanel = document.getElementById(targetPanelId);
      if (targetPanel) {
        targetPanel.classList.remove('organizations_members_panel_hidden');
        targetPanel.classList.add('is-visible');
      }

      // Load Members tab data on demand when the panel becomes active
      if (targetPanelId === 'organizations_tab_members_panel') {
        Promise.all([loadMembers(), loadAccessRequests()])
          .then(() => {
            const panel = document.getElementById('organizations_tab_members_panel');
            if (panel) { panel.dataset.ready = 'members-loaded'; }
          })
          .catch((error) => debugLog('Members tab load error:', error));
      }
    };

    tabs.forEach((tab) => {
      tab.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        switchTab(tab);
      });
      tab.addEventListener('keydown', (e) => {
        const validKeys = ['Enter', ' '];
        if (validKeys.includes(e.key)) {
          e.preventDefault();
          e.stopPropagation();
          switchTab(tab);
        }
      });
    });
  };

  // ── Member List Management ──────────────────────────────────
  const initializeMemberTabs = () => {
    const inviteForm = document.getElementById('organizations_members_invite_form');

    if (inviteForm) {
      inviteForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await handleInviteSubmit();
      });
    }

    elements.membersImportPrepare?.addEventListener('click', async () => {
      await handleMembersImportPrepare();
    });
    elements.membersImportSendCode?.addEventListener('click', async () => {
      await handleMembersImportSendCode();
    });
    elements.membersImportVerify?.addEventListener('click', async () => {
      await handleMembersImportVerifyCode();
    });
    elements.membersImportCommit?.addEventListener('click', async () => {
      await handleMembersImportCommit();
    });

    if (elements.membersRoleFilter) {
      elements.membersRoleFilter.addEventListener('change', async () => {
        await loadMembers();
      });
    }

    if (elements.membersGridContainer) {
      elements.membersGridContainer.addEventListener('click', (event) => {
        const actionBtn = event.target.closest('.datagrid_action');
        if (!actionBtn || !elements.membersGridContainer.contains(actionBtn)) {
          return;
        }

        event.preventDefault();
        const action = String(actionBtn.dataset.action || '');
        const memberUuid = String(actionBtn.dataset.id || '');
        if (memberUuid === '') {
          return;
        }

        if (action === 'change-role') {
          showChangeRoleDialog(memberUuid);
          return;
        }

        if (action === 'revoke') {
          showConfirmRevokeDialog(memberUuid);
        }
      });
    }

    const decorateAuditGridTimestamps = (gridId) => {
      const grid = document.getElementById(gridId);
      if (!grid) {
        return;
      }

      // Fetch event details from DOM store
      const gridHostId = gridId.replace(/-grid$/, '-grid-host');
      const gridHost = document.getElementById(gridHostId) || grid.parentElement;
      let detailsStore = gridHost ? gridHost.querySelector(`[id$="_event_details"]`) : null;
      if (!detailsStore) {
        detailsStore = document.getElementById(`${gridId}_event_details`);
      }
      if (!detailsStore) {
        return;
      }

      const eventDetailsJson = detailsStore.getAttribute('data-event-details-json') || '{}';
      let eventDetailsMap = {};
      try {
        eventDetailsMap = JSON.parse(eventDetailsJson);
      } catch {
        return;
      }

      const rows = grid.querySelectorAll('.datagrid_row');
      rows.forEach((row, index) => {
        const rowId = String(row.dataset.id || '');
        const rawEventDetails = eventDetailsMap[rowId];
        if (!rawEventDetails) {
          return;
        }

        const createdAtRaw = String(rawEventDetails.created_at_raw || '').trim();
        if (createdAtRaw === '') {
          return;
        }

        const timestampCells = row.querySelectorAll('.datagrid_content');
        if (timestampCells.length === 0) {
          return;
        }

        const firstCell = timestampCells[0];
        const textContent = String(firstCell.textContent || '').trim();

        const parsedDate = parseHistoryTimestampValue(createdAtRaw);
        if (!parsedDate) {
          return;
        }

        const displayText = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
        const popoverId = `organizations_audit_timestamp_popover_${String(rowId).replace(/[^a-zA-Z0-9_-]/g, '_')}_${index}`;

        const field = document.createElement('span');
        field.className = 'organizations_history_timestamp_field';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'organizations_history_timestamp_trigger';
        trigger.textContent = displayText;
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-controls', popoverId);
        trigger.setAttribute('aria-expanded', 'false');

        const popover = document.createElement('div');
        popover.id = popoverId;
        popover.className = 'organizations_history_timestamp_popover';
        popover.hidden = true;
        popover.setAttribute('role', 'dialog');
        popover.setAttribute('aria-label', 'Timestamp details');

        const rows = [
          {
            label: 'Local',
            value: formatTimestampInTimeZone(parsedDate, viewerTimeZone),
          },
          {
            label: 'Server',
            value: formatTimestampInTimeZone(parsedDate, SERVER_TIMEZONE),
          },
          {
            label: 'UTC',
            value: formatTimestampInTimeZone(parsedDate, 'UTC'),
          },
        ];

        rows.forEach((row) => {
          const rowEl = document.createElement('span');
          rowEl.className = 'organizations_history_timestamp_popover_row';

          const labelEl = document.createElement('span');
          labelEl.className = 'organizations_history_timestamp_popover_label';
          labelEl.textContent = `${row.label}:`;

          const valueEl = document.createElement('span');
          valueEl.className = 'organizations_history_timestamp_popover_value';
          valueEl.textContent = row.value;

          rowEl.appendChild(labelEl);
          rowEl.appendChild(valueEl);
          popover.appendChild(rowEl);
        });

        field.appendChild(trigger);
        field.appendChild(popover);

        firstCell.innerHTML = '';
        firstCell.appendChild(field);

        trigger.addEventListener('click', (e) => {
          e.preventDefault();
          openHistoryTimestampPopoverFor(trigger, popover);
        });
      });
    };

    document.addEventListener('paycal:datagrid-reloaded', (event) => {
      const detail = event?.detail || {};
      const gridId = String(detail.gridId || '');
      if (gridId === 'organizations-audit-grid') {
        if (!elements.auditStatus) {
          return;
        }

        const rowCount = Number(detail.rowCount || 0);
        elements.auditStatus.textContent = `Audit grid updated. ${rowCount} event${rowCount === 1 ? '' : 's'} shown.`;
        decorateAuditGridTimestamps('organizations-audit-grid');
        return;
      }

      if (gridId === 'organizations-free-audit-grid') {
        decorateAuditGridTimestamps('organizations-free-audit-grid');
        return;
      }

      if (gridId === 'organizations-free-audit-grid') {
        decorateAuditGridTimestamps('organizations-free-audit-grid', auditEventDetailsMap);
        return;
      }

      if (gridId !== 'organizations-members-grid') {
        return;
      }

      if (!elements.membersGridStatus) {
        return;
      }

      const stateInfo = detail.state || {};
      const rowCount = Number(detail.rowCount || 0);
      const order = stateInfo.sort ? `${stateInfo.sort} ${stateInfo.direction || 'asc'}` : 'default order';
      const search = stateInfo.search ? `search ${stateInfo.search}` : 'no search filter';
      const page = stateInfo.page || 1;
      const role = elements.membersRoleFilter?.value || '';
      const roleText = role === '' ? 'all roles' : `role ${role}`;
      elements.membersGridStatus.textContent = `Members grid updated. ${rowCount} result${rowCount === 1 ? '' : 's'}. ${order}. ${search}. ${roleText}. Page ${page}.`;
    });

    // Load initial member data
    loadMembers().catch((error) => {
      debugLog('Failed to load members:', error);
      const statusEl = document.getElementById('organizations_members_grid_sr_status');
      if (statusEl) {
        statusEl.textContent = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_FAILED_LOAD_MEMBERS_GRID')); ?>';
      }
    });

    // Load initial access requests
    loadAccessRequests().catch((error) => {
      debugLog('Failed to load access requests:', error);
      const statusEl = document.getElementById('organizations_access_requests_sr_status');
      if (statusEl) {
        statusEl.textContent = '<?php echo addslashes(org_js_index_i18n('ORGANIZATIONS_FAILED_LOAD_ACCESS_REQUESTS')); ?>';
      }
    });
  };

  const currentOrganizationId = () => {
    const orgIdFromInput = document.getElementById('organizations_editor_org_id')?.value || '';
    return orgIdFromInput || state.selectedOrganizationId || '';
  };

  const membersGridEndpoint = (orgId) => {
    const roleFilter = elements.membersRoleFilter?.value || '';
    const params = new URLSearchParams();
    if (roleFilter !== '') {
      params.set('role', roleFilter);
    }

    const qs = params.toString();
    return `/api/v1/organizations/${encodeURIComponent(orgId)}/members/grid${qs !== '' ? `?${qs}` : ''}`;
  };

  const ensureMembersGridManager = (orgId) => {
    const roleFilter = elements.membersRoleFilter?.value || '';

    if (
      state.membersGridManager
      && state.membersGridOrgId === orgId
      && state.membersGridRoleFilter === roleFilter
    ) {
      return state.membersGridManager;
    }

    if (state.membersGridManager && typeof state.membersGridManager.destroy === 'function') {
      state.membersGridManager.destroy();
    }

    state.membersGridManager = createDataGrid({
      id: 'organizations-members-grid',
      endpoint: membersGridEndpoint(orgId),
    });
    state.membersGridOrgId = orgId;
    state.membersGridRoleFilter = roleFilter;

    return state.membersGridManager;
  };

  const loadMembers = async () => {
    const currentOrgId = currentOrganizationId();
    if (currentOrgId === '') {
      debugLog('No org ID found in DOM');
      return;
    }

    const organization = findOrganization(currentOrgId);
    if (organization && !canManageOrganizationAccess(organization)) {
      if (elements.membersGridContainer instanceof HTMLElement) {
        setDatagridMessage(elements.membersGridContainer, ACCESS_MANAGE_WARNING);
      }
      if (elements.membersGridStatus instanceof HTMLElement) {
        elements.membersGridStatus.textContent = ACCESS_MANAGE_WARNING;
      }
      return;
    }

    const manager = ensureMembersGridManager(currentOrgId);
    if (!manager) {
      throw new Error('Unable to initialize members grid manager.');
    }

    await manager.reload();
    enhanceMembersJoinedTimestampCells();
  };

  const loadAccessRequests = async () => {
    const currentOrgId = currentOrganizationId();
    if (currentOrgId === '') {
      debugLog('No org ID found in DOM');
      return;
    }

    const organization = findOrganization(currentOrgId);
    if (organization && !canManageOrganizationAccess(organization)) {
      const requestsList = document.getElementById('organizations_members_requests_list');
      if (requestsList instanceof HTMLElement) {
        setStackMessage(requestsList, ACCESS_MANAGE_WARNING);
      }
      const statusEl = document.getElementById('organizations_access_requests_sr_status');
      if (statusEl instanceof HTMLElement) {
        statusEl.textContent = ACCESS_MANAGE_WARNING;
      }
      return;
    }

    try {
      const response = await fetch(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/access/requests`, {
        method: 'GET',
        headers: buildHeaders(),
        credentials: 'include',
      });

      if (!response.ok) {
        throw new Error(`Failed to load access requests: ${response.status}`);
      }

      const payload = await response.json();
      const requests = Array.isArray(payload?.data?.requests)
        ? payload.data.requests
        : (Array.isArray(payload?.requests) ? payload.requests : []);
      renderMembersTabAccessRequests(requests);
    } catch (error) {
      debugLog('Error loading access requests:', error);
    }
  };

  const renderMembersTabAccessRequests = (requests) => {
    const requestsList = document.getElementById('organizations_members_requests_list');
    if (!requestsList) {
      return;
    }

    if (requests.length === 0) {
      Guardian.setHTML(requestsList, '<p>No pending access requests.</p>');
      requestsList.classList.add('organizations_empty');
      return;
    }

    requestsList.classList.remove('organizations_empty');
    Guardian.setHTML(requestsList, requests.map((request) => {
      const requesterContact = String(request.requester_contact_email || request.email || '');
      const requestDate = request.created_at
        ? new Date(String(request.created_at)).toLocaleDateString()
        : '';
      const requestId = String(request.request_id || request.id || '');

      return `
      <div class="organizations_access_request_row">
        <div class="organizations_request_info">
          <div>${requesterContact}</div>
          <div class="organizations_request_email">Requested: ${requestDate}</div>
        </div>
        <div class="organizations_request_actions">
          <button type="button" class="btn btn_success btn_sm access_request_action" data-action="approve" data-request-id="${requestId}">Approve</button>
          <button type="button" class="btn btn_secondary btn_sm access_request_action" data-action="reject" data-request-id="${requestId}">Reject</button>
        </div>
      </div>
    `;
    }).join(''));

    const actionBtns = requestsList.querySelectorAll('.access_request_action');
    actionBtns.forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const action = btn.getAttribute('data-action');
        const requestId = btn.getAttribute('data-request-id');
        await handleAccessRequestAction(requestId, action);
      });
    });
  };

  const handleAccessRequestAction = async (requestId, action) => {
    const currentOrgId = currentOrganizationId();
    if (!currentOrgId || !requestId || !action) {
      return;
    }

    const organization = findOrganization(currentOrgId);
    if (organization && !canManageOrganizationAccess(organization)) {
      showAccessManagementDeniedWarning();
      return;
    }

    try {
      const endpoint = `/api/v1/organizations/${encodeURIComponent(currentOrgId)}/access/requests/${encodeURIComponent(action)}`;
      const body = new URLSearchParams();
      body.set('request_id', requestId);
      const csrfToken = getCsrfToken();
      if (csrfToken !== '') {
        body.set('csrf_token', csrfToken);
      }

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: buildHeaders(),
        body,
        credentials: 'include',
      });

      if (!response.ok) {
        throw new Error(`Failed to ${action} request: ${response.status}`);
      }

      const statusMsg = action === 'approve' ? T.accessRequestApproved : T.accessRequestRejected;
      setGridMessage(statusMsg);

      // Reload both lists
      await Promise.all([
        loadMembers(),
        loadAccessRequests(),
        loadOrganizationInvites(currentOrgId),
        loadOrganizationInviteHistoryGrid(currentOrgId),
      ]);
      await syncLiveRequestsPanel(false);
      const membersPanel = document.getElementById('organizations_tab_members_panel');
      if (membersPanel) { membersPanel.dataset.ready = 'members-loaded'; }
    } catch (error) {
      debugLog(`Error handling access request ${action}:`, error);
      setGridMessage(T.accessRequestActionFailed);
    }
  };

  const defaultMembersImportScopes = () => ['payperiod.read', 'sites.read', 'work.read'];

  const resetMembersImportFlow = () => {
    state.membersImport.importId = '';
    state.membersImport.challengeId = '';
    state.membersImport.verified = false;

    if (elements.membersImportSendCode instanceof HTMLButtonElement) {
      elements.membersImportSendCode.disabled = true;
    }
    if (elements.membersImportCode instanceof HTMLInputElement) {
      elements.membersImportCode.disabled = true;
      elements.membersImportCode.value = '';
    }
    if (elements.membersImportVerify instanceof HTMLButtonElement) {
      elements.membersImportVerify.disabled = true;
    }
    if (elements.membersImportCommit instanceof HTMLButtonElement) {
      elements.membersImportCommit.disabled = true;
    }
  };

  const setMembersImportStatus = (message, type = 'neutral') => {
    if (!(elements.membersImportStatus instanceof HTMLElement)) {
      return;
    }

    elements.membersImportStatus.textContent = String(message || '');
    elements.membersImportStatus.classList.remove('error', 'success', 'is-visible');

    if (type === 'error') {
      elements.membersImportStatus.classList.add('error');
    } else if (type === 'success') {
      elements.membersImportStatus.classList.add('success');
    }

    if (String(message || '') !== '') {
      elements.membersImportStatus.classList.add('is-visible');
    }
  };

  const renderMembersImportSummary = (summaryData = {}) => {
    if (!(elements.membersImportSummary instanceof HTMLElement)) {
      return;
    }

    const metricRows = [
      ['Input', Number(summaryData.input_count || 0)],
      ['Accepted', Number(summaryData.accepted_count || 0)],
      ['Invalid', Number(summaryData.invalid_count || 0)],
      ['Duplicates', Number(summaryData.duplicate_count || 0)],
      ['Wrong Domain', Number(summaryData.wrong_domain_count || 0)],
      ['Already Member', Number(summaryData.already_member_count || 0)],
      ['Already Invited', Number(summaryData.already_invited_count || 0)],
    ];

    const escapeText = (value) => {
      if (Guardian && typeof Guardian.sanitizedText === 'function') {
        return Guardian.sanitizedText(String(value ?? ''));
      }

      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    };

    const metricsMarkup = metricRows
      .map(([label, value]) => `
        <div class="organizations_members_import_metric">
          <span class="organizations_members_import_metric_label">${escapeText(label)}</span>
          <strong class="organizations_members_import_metric_value">${escapeText(value)}</strong>
        </div>
      `)
      .join('');

    elements.membersImportSummary.classList.remove('organizations_empty');
    Guardian.setHTML(elements.membersImportSummary, `
      <h5 class="organizations_members_import_summary_title">Import Summary</h5>
      <div class="organizations_members_import_metrics">${metricsMarkup}</div>
      <p class="organizations_members_import_summary_hint">Flow: parse emails, verify authority code, then send invites.</p>
    `);
  };

  const handleMembersImportPrepare = async () => {
    const currentOrgId = currentOrganizationId();
    if (currentOrgId === '') {
      setMembersImportStatus('Select an organization first.', 'error');
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      setMembersImportStatus(ACCESS_MANAGE_WARNING, 'error');
      return;
    }

    if (!(elements.membersImportEmails instanceof HTMLTextAreaElement)) {
      return;
    }

    const emails = String(elements.membersImportEmails.value || '').trim();
    if (emails === '') {
      setMembersImportStatus('Paste one or more email addresses to import.', 'error');
      return;
    }

    const emailChunks = emails
      .split(/[\s,;]+/)
      .map((chunk) => chunk.trim())
      .filter((chunk) => chunk !== '');

    if (emailChunks.length === 0) {
      setMembersImportStatus('No importable email entries were detected in the textarea.', 'error');
      return;
    }

    resetMembersImportFlow();
    setMembersImportStatus('Preparing import preview...', 'neutral');

    try {
      const payload = await postForm(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/invites/import/prepare`, {
        emails: emailChunks.join('\n'),
        emails_chunks: emailChunks,
        scopes: defaultMembersImportScopes(),
      });

      const importId = String(payload.import_id || '');
      if (importId === '') {
        throw new Error('Bulk import preparation did not return an import session id.');
      }

      state.membersImport.importId = importId;
      renderMembersImportSummary(payload.summary || {});

      const acceptedCount = Number(payload?.summary?.accepted_count || 0);

      if (elements.membersImportSendCode instanceof HTMLButtonElement) {
        elements.membersImportSendCode.disabled = acceptedCount <= 0;
      }

      const domain = String(payload.authority_domain || '');
      setMembersImportStatus(
        acceptedCount > 0
          ? `Prepared ${acceptedCount} invite${acceptedCount === 1 ? '' : 's'}. Domain enforced: @${domain}`
          : 'No eligible emails after validation. Fix the list and prepare again.',
        acceptedCount > 0 ? 'success' : 'error'
      );
    } catch (error) {
      const errorData = error && typeof error === 'object' && 'data' in error ? error.data : null;
      const malformedFields = errorData && typeof errorData === 'object' && Array.isArray(errorData.malformed_fields)
        ? errorData.malformed_fields
        : [];
      const malformedSuffix = malformedFields.length > 0
        ? ` [Malformed fields: ${malformedFields.join(', ')}]`
        : '';
      setMembersImportStatus(
        `${error instanceof Error ? error.message : 'Unable to prepare bulk import.'}${malformedSuffix}`,
        'error'
      );
    }
  };

  const handleMembersImportSendCode = async () => {
    const currentOrgId = currentOrganizationId();
    const importId = String(state.membersImport.importId || '');

    if (currentOrgId === '' || importId === '') {
      setMembersImportStatus('Prepare import before requesting a verification code.', 'error');
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      setMembersImportStatus(ACCESS_MANAGE_WARNING, 'error');
      return;
    }

    setMembersImportStatus('Sending verification code to the verified org admin/owner email...', 'neutral');

    try {
      const payload = await postForm(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/invites/import/challenge/start`, {
        import_id: importId,
      });

      const challengeId = String(payload.challenge_id || '');
      if (challengeId === '') {
        throw new Error('Challenge id missing from verification response.');
      }

      state.membersImport.challengeId = challengeId;

      if (elements.membersImportCode instanceof HTMLInputElement) {
        elements.membersImportCode.disabled = false;
        elements.membersImportCode.value = '';
      }
      if (elements.membersImportVerify instanceof HTMLButtonElement) {
        elements.membersImportVerify.disabled = false;
      }

      setMembersImportStatus(
        `Verification code sent to ${String(payload.authority_email_hint || 'verified org email')}.`,
        'success'
      );
    } catch (error) {
      setMembersImportStatus(error instanceof Error ? error.message : 'Unable to send verification code.', 'error');
    }
  };

  const handleMembersImportVerifyCode = async () => {
    const currentOrgId = currentOrganizationId();
    const importId = String(state.membersImport.importId || '');
    const challengeId = String(state.membersImport.challengeId || '');
    const code = elements.membersImportCode instanceof HTMLInputElement
      ? String(elements.membersImportCode.value || '').trim()
      : '';

    if (currentOrgId === '' || importId === '' || challengeId === '') {
      setMembersImportStatus('Prepare import and send verification code first.', 'error');
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      setMembersImportStatus(ACCESS_MANAGE_WARNING, 'error');
      return;
    }

    if (code === '') {
      setMembersImportStatus('Enter the verification code.', 'error');
      return;
    }

    setMembersImportStatus('Verifying code...', 'neutral');

    try {
      await postForm(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/invites/import/challenge/verify`, {
        import_id: importId,
        challenge_id: challengeId,
        code,
      });

      state.membersImport.verified = true;
      if (elements.membersImportCommit instanceof HTMLButtonElement) {
        elements.membersImportCommit.disabled = false;
      }

      setMembersImportStatus('Code verified. You can now send invites.', 'success');
    } catch (error) {
      setMembersImportStatus(error instanceof Error ? error.message : 'Unable to verify code.', 'error');
    }
  };

  const handleMembersImportCommit = async () => {
    const currentOrgId = currentOrganizationId();
    const importId = String(state.membersImport.importId || '');
    const challengeId = String(state.membersImport.challengeId || '');

    if (currentOrgId === '' || importId === '' || challengeId === '' || !state.membersImport.verified) {
      setMembersImportStatus('Verification is required before importing invites.', 'error');
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      setMembersImportStatus(ACCESS_MANAGE_WARNING, 'error');
      return;
    }

    setMembersImportStatus('Importing invites...', 'neutral');

    try {
      const payload = await postForm(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/invites/import/commit`, {
        import_id: importId,
        challenge_id: challengeId,
      });

      const successCount = Number(payload.success_count || 0);
      const failureCount = Number(payload.failure_count || 0);
      setMembersImportStatus(
        `Bulk import completed. ${successCount} invite${successCount === 1 ? '' : 's'} sent, ${failureCount} failed.`,
        failureCount === 0 ? 'success' : 'error'
      );

      resetMembersImportFlow();
      if (elements.membersImportEmails instanceof HTMLTextAreaElement) {
        elements.membersImportEmails.value = '';
      }

      await Promise.all([loadMembers(), loadAccessRequests()]);
    } catch (error) {
      setMembersImportStatus(error instanceof Error ? error.message : 'Unable to complete bulk import.', 'error');
    }
  };

  const handleInviteSubmit = async () => {
    const emailInput = document.getElementById('organizations_members_invite_email');
    const statusEl = document.getElementById('organizations_members_invite_status');
    const currentOrgId = document.getElementById('organizations_editor_org_id')?.value || '';

    if (!emailInput || !statusEl || !currentOrgId) {
      return;
    }

    const organization = findOrganization(currentOrgId);
    if (organization && !canManageOrganizationAccess(organization)) {
      statusEl.textContent = ACCESS_MANAGE_WARNING;
      statusEl.classList.remove('success');
      statusEl.classList.add('error', 'is-visible');
      showAccessManagementDeniedWarning();
      return;
    }

    const email = (emailInput.value || '').trim();
    if (!email) {
      statusEl.textContent = T.enterInviteEmail || 'Please enter an email address.';
      statusEl.classList.remove('success');
      statusEl.classList.add('error', 'is-visible');
      return;
    }

    try {
      const body = new URLSearchParams();
      body.append('email', email);
      body.append('scopes[]', 'payperiod.read');
      body.append('scopes[]', 'sites.read');
      body.append('scopes[]', 'work.read');
      const csrfToken = getCsrfToken();
      if (csrfToken !== '') {
        body.set('csrf_token', csrfToken);
      }

      const response = await fetch(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/invites/send`, {
        method: 'POST',
        headers: buildHeaders(),
        body,
        credentials: 'include',
      });

      if (!response.ok) {
        throw new Error(`Failed to send invite: ${response.status}`);
      }

      statusEl.textContent = T.inviteSent || 'Invite sent successfully.';
      statusEl.classList.remove('error');
      statusEl.classList.add('success', 'is-visible');
      emailInput.value = '';

      // Reload members and requests after short delay
      setTimeout(() => {
        loadMembers().catch((e) => debugLog('Failed to reload members after invite:', e));
        loadAccessRequests().catch((e) => debugLog('Failed to reload requests after invite:', e));
        loadOrganizationInvites(currentOrgId).catch((e) => debugLog('Failed to reload invites after invite:', e));
      }, 500);
    } catch (error) {
      debugLog('Error sending invite:', error);
      statusEl.textContent = T.inviteSendFailed || 'Failed to send invite. Please try again.';
      statusEl.classList.remove('success');
      statusEl.classList.add('error', 'is-visible');
    }
  };

  const showChangeRoleDialog = (memberUuid) => {
    const currentOrgId = document.getElementById('organizations_editor_org_id')?.value || '';
    if (!currentOrgId || !memberUuid) {
      return;
    }

    const organization = findOrganization(currentOrgId);
    if (organization && !canManageOrganizationAccess(organization)) {
      showAccessManagementDeniedWarning();
      return;
    }

    const selectedRole = window.prompt('Enter new role: manager, contributor, viewer, or member', 'viewer');
    if (!selectedRole) {
      return;
    }

    const normalizedRoleInput = selectedRole.trim().toLowerCase();
    const normalizedRole = normalizedRoleInput === 'manager' ? 'coordinator' : normalizedRoleInput;
    if (!['coordinator', 'contributor', 'viewer', 'member'].includes(normalizedRole)) {
      setGridMessage('Invalid role selected.');
      return;
    }

    const body = new URLSearchParams();
    body.set('target_user_uuid', memberUuid);
    body.set('role', normalizedRole);
    const csrfToken = getCsrfToken();
    if (csrfToken !== '') {
      body.set('csrf_token', csrfToken);
    }

    fetch(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/relationships/update-role`, {
      method: 'POST',
      headers: buildHeaders(),
      body,
      credentials: 'include',
    }).then(async (response) => {
      if (!response.ok) {
        throw new Error(`Failed to update role: ${response.status}`);
      }
      setGridMessage('Member role updated.');
      await loadMembers();
    }).catch((error) => {
      debugLog('Error updating role:', error);
      setGridMessage('Unable to update member role right now.');
    });
  };

  const showConfirmRevokeDialog = (memberUuid) => {
    const currentOrgId = document.getElementById('organizations_editor_org_id')?.value || '';
    if (!currentOrgId || !memberUuid) {
      return;
    }

    const organization = findOrganization(currentOrgId);
    if (organization && !canManageOrganizationAccess(organization)) {
      showAccessManagementDeniedWarning();
      return;
    }

    const confirmed = window.confirm('Revoke this member\'s access?');
    if (!confirmed) {
      return;
    }

    const body = new URLSearchParams();
    body.set('target_user_uuid', memberUuid);
    const csrfToken = getCsrfToken();
    if (csrfToken !== '') {
      body.set('csrf_token', csrfToken);
    }

    fetch(`/api/v1/organizations/${encodeURIComponent(currentOrgId)}/relationships/revoke`, {
      method: 'POST',
      headers: buildHeaders(),
      body,
      credentials: 'include',
    }).then(async (response) => {
      if (!response.ok) {
        throw new Error(`Failed to revoke member: ${response.status}`);
      }
      setGridMessage('Member access revoked.');
      await loadMembers();
      await loadAccessRequests();
    }).catch((error) => {
      debugLog('Error revoking member:', error);
      setGridMessage('Unable to revoke member access right now.');
    });
  };

  const initialize = async () => {
    bindEvents();
    initializeOrganizationBrowser();
    initializeTabNavigation();
    initializeMemberTabs();
    initializeHoverHelp();
    renderPreview();
    schedulePersonalPreviewRender();
    announceScopeSelectionStatus('loaded');
    await initializeProfileBilling();
    await loadAccountActivity();

    const params = new URLSearchParams(window.location.search);
    const inviteToken = String(params.get('org_invite_token') || '').trim();
    if (inviteToken !== '') {
      await acceptOrganizationInviteToken(inviteToken);
    }

    await refreshIndex();
    startLiveRequestsPolling();
    startOrganizationNotificationPolling();
  };

  initialize().catch((error) => {
    PW.error(error);
    setGridMessage(T.loadOrgsFailed);
  });
})();
