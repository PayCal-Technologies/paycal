<?php namespace PayCal\Domain; ?>
  const isAdminUser = <?php echo User::isAdmin() ? 'true' : 'false'; ?>;
  const isSuperAdminUser = <?php echo User::isSuperAdmin() ? 'true' : 'false'; ?>;
  const appEnv = '<?php echo addslashes((string) \PayCal\Domain\Config\Environment::appEnv()); ?>';
  const isDevEnvironment = appEnv === 'dev' || appEnv === 'mac' || window.location.hostname === 'dev.paycal.local';
  const isManagerUser = <?php echo User::isManager() ? 'true' : 'false'; ?>;
  const hasActivePremiumSubscription = <?php echo SubscriptionGate::hasActivePremium(User::currentUUID()) ? 'true' : 'false'; ?>;
  const hasActiveBusinessSubscription = <?php echo SubscriptionGate::hasActiveBusiness(User::currentUUID()) ? 'true' : 'false'; ?>;
  const isElevatedStaffUser = isAdminUser || isSuperAdminUser || isManagerUser;
  const currentUserUUID = '<?php echo addslashes(User::currentUUID()); ?>';
  const isDebugEnabled = window.PAYCAL_DEBUG === true;
  const debugLog = (...args) => {
    if (isDebugEnabled) {
      PW.log(...args);
    }
  };

  const T = {
    loading: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOADING')); ?>',
    none: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NONE')); ?>',
    noInvites: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_INVITES')); ?>',
    noDiscovery: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_DISCOVERY')); ?>',
    noAudit: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_AUDIT')); ?>',
    selectFirst: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SELECT_FIRST')); ?>',
    loadBusinessesFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOAD_ORGS_FAILED')); ?>',
    loadInvitesFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOAD_INVITES_FAILED')); ?>',
    loadDefaultsFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOAD_DEFAULTS_FAILED')); ?>',
    loadAuditFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOAD_AUDIT_FAILED')); ?>',
    discoveryRunning: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_RUNNING')); ?>',
    discoveryComplete: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_COMPLETE')); ?>',
    discoveryFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_FAILED')); ?>',
    inviteSent: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITE_SENT')); ?>',
    inviteSendFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITE_SEND_FAILED')); ?>',
    inviteRevoked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITE_REVOKED')); ?>',
    inviteRevokeFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITE_REVOKE_FAILED')); ?>',
    defaultsSaved: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DEFAULTS_SAVED')); ?>',
    defaultsSaveFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DEFAULTS_SAVE_FAILED')); ?>',
    created: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CREATED')); ?>',
    createFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CREATE_FAILED')); ?>',
    controlCenterSaved: '<?php echo addslashes(org_js_index_i18n('BUSINESS_DETAILS_SAVED')); ?>',
    controlCenterSaveFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_DETAILS_SAVE_FAILED')); ?>',
    controlCenterNoBusiness: '<?php echo addslashes(org_js_index_i18n('BUSINESS_DETAILS_NO_BUSINESS')); ?>',
    businessDetailsSaved: '<?php echo addslashes(org_js_index_i18n('BUSINESS_DETAILS_SAVED')); ?>',
    businessDetailsSaveFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_DETAILS_SAVE_FAILED')); ?>',
    businessDetailsNoBusiness: '<?php echo addslashes(org_js_index_i18n('BUSINESS_DETAILS_NO_BUSINESS')); ?>',
    payrollSaved: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_SAVED')); ?>',
    payrollSaveFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_SAVE_FAILED')); ?>',
    payrollNoBusiness: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_NO_BUSINESS')); ?>',
    execSummaryLede: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_EXEC_SUMMARY_LEDE')); ?>',
    execNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_EXEC_NONE')); ?>',
    enterInviteEmail: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ENTER_INVITE_EMAIL')); ?>',
    selectScope: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SELECT_SCOPE')); ?>',
    selectTransferTarget: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SELECT_TRANSFER_TARGET')); ?>',
    ownershipTransferred: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNERSHIP_TRANSFERRED')); ?>',
    ownershipTransferFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNERSHIP_TRANSFER_FAILED')); ?>',
    withdrawn: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_WITHDRAWN')); ?>',
    withdrawFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_WITHDRAW_FAILED')); ?>',
    siteLinked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SITE_LINKED')); ?>',
    siteLinkFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SITE_LINK_FAILED')); ?>',
    unknown: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_UNKNOWN')); ?>',
    pending: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PENDING')); ?>',
    active: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_STATUS_ACTIVE')); ?>',
    revoke: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REVOKE')); ?>',
    linkSite: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LINK_SITE')); ?>',
    noBusinessSites: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_NO_SITES')); ?>',
    businessSitesLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_LOAD_FAILED')); ?>',
    businessSitesCreated: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_CREATED')); ?>',
    businessSitesUnlinked: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_UNLINKED')); ?>',
    businessSitesNoBusiness: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_NO_BUSINESS')); ?>',
    sitesOwnershipPersonal: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_OWNERSHIP_PERSONAL')); ?>',
    sitesOwnershipBusiness: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_OWNERSHIP_BUSINESS')); ?>',
    sitesOwnershipShared: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_OWNERSHIP_SHARED')); ?>',
    sitesStatusArchived: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_STATUS_ARCHIVED')); ?>',
    auditActor: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_ACTOR')); ?>',
    nameMin: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NAME_MIN')); ?>',
    manageAccessUnavailable: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MANAGE_ACCESS_UNAVAILABLE')); ?>',
    memberAccessManageDenied: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_ACCESS_MANAGE_DENIED')); ?>',
    memberRoleUpdated: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_ROLE_UPDATED')); ?>',
    memberRoleUpdateFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_ROLE_UPDATE_FAILED')); ?>',
    memberRoleUpdateFailedGeneric: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_ROLE_UPDATE_FAILED_GENERIC')); ?>',
    membersSelectAll: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_SELECT_ALL')); ?>',
    membersClearSelection: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_CLEAR_SELECTION')); ?>',
    membersSelectionCountNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_SELECTION_COUNT_NONE')); ?>',
    membersSelectionCount: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_SELECTION_COUNT')); ?>',
    membersGridLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_GRID_LOADED')); ?>',
    membersGridReady: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_GRID_READY')); ?>',
    noBusinessSelected: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_BUSINESS_SELECTED')); ?>',
    membersLoading: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_LOADING')); ?>',
    membersGridInitFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_GRID_INIT_FAILED')); ?>',
    membersPendingSummary: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_SUMMARY')); ?>',
    membersPendingTypeInvite: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_TYPE_INVITE')); ?>',
    membersPendingTypeRequest: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_TYPE_REQUEST')); ?>',
    membersPendingLoading: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_LOADING')); ?>',
    membersPendingLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_LOADED')); ?>',
    membersPendingLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_LOAD_FAILED')); ?>',
    membersPendingApprove: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_APPROVE')); ?>',
    membersPendingReject: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_REJECT')); ?>',
    membersPendingMetricAria: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_PENDING_METRIC_ARIA')); ?>',
    membersOwnerRoleLocked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_OWNER_ROLE_LOCKED')); ?>',
    memberReportsLoadedFor: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REPORTS_LOADED_FOR')); ?>',
    auditNoWorkspace: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_NO_WORKSPACE')); ?>',
    auditLoading: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_LOADING')); ?>',
    auditLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_LOAD_FAILED')); ?>',
    reportsLoadingAnalytics: '<?php echo addslashes(org_js_index_i18n('BUSINESS_REPORTS_LOADING_ANALYTICS')); ?>',
    reportsAnalyticsLoadedStatus: '<?php echo addslashes(org_js_index_i18n('BUSINESS_REPORTS_ANALYTICS_LOADED_STATUS')); ?>',
    reportsInsufficientHistory: '<?php echo addslashes(org_js_index_i18n('BUSINESS_REPORTS_INSUFFICIENT_HISTORY')); ?>',
    businessExportSeconds: '<?php echo addslashes(org_js_index_i18n('BUSINESS_EXPORT_SECONDS')); ?>',
    businessExportGenerating: '<?php echo addslashes(org_js_index_i18n('BUSINESS_EXPORT_GENERATING')); ?>',
    memberReportsCooldown: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_COOLDOWN')); ?>',
    memberReportsUnsupportedOption: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_UNSUPPORTED_OPTION')); ?>',
    memberReportsConfirm: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_CONFIRM')); ?>',
    memberReportsProgress: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_PROGRESS')); ?>',
    memberReportsNoRows: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_NO_ROWS')); ?>',
    memberReportsGenerationFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_GENERATION_FAILED')); ?>',
    memberReportsGeneratedFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_GENERATED_FAILED')); ?>',
    memberReportsGeneratedSuccess: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_GENERATED_SUCCESS')); ?>',
    memberReportsGenerateSelected: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_GENERATE_SELECTED')); ?>',
    memberReportRemoveAria: '<?php echo addslashes(org_js_index_i18n('BUSINESS_MEMBERS_REPORT_REMOVE_ARIA')); ?>',
    businessGroupsNameRequired: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_NAME_REQUIRED')); ?>',
    businessGroupsSaveFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_SAVE_FAILED')); ?>',
    businessGroupsEmpty: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_EMPTY')); ?>',
    businessGroupsCreateNew: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_CREATE_NEW')); ?>',
    businessGroupsMembersAdded: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_MEMBERS_ADDED')); ?>',
    businessGroupsMembersAddedCount: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_MEMBERS_ADDED_COUNT')); ?>',
    businessGroupsMemberAddedNamed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_MEMBER_ADDED_NAMED')); ?>',
    businessGroupsMembersAddedNamed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_MEMBERS_ADDED_NAMED')); ?>',
    businessGroupsAddNoMembers: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_ADD_NO_MEMBERS')); ?>',
    businessGroupsAddFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_ADD_FAILED')); ?>',
    payrollPackageNoActiveMembers: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_NO_ACTIVE_MEMBERS')); ?>',
    payrollPackageConfirm: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_CONFIRM')); ?>',
    payrollPackageProgress: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_PROGRESS')); ?>',
    payrollPackageMemberReportFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_MEMBER_REPORT_FAILED')); ?>',
    payrollPackageGeneratedFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_GENERATED_FAILED')); ?>',
    payrollPackageGeneratedSuccess: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_GENERATED_SUCCESS')); ?>',
    payrollPackageGenerate: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_GENERATE')); ?>',
    payrollPackageGenerateFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_GENERATE_FAILED')); ?>',
    payrollPackageDownloadZipFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_DOWNLOAD_ZIP_FAILED')); ?>',
    payrollPackageReadmeTitle: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_README_TITLE')); ?>',
    payrollPackageReadmeContents: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_README_CONTENTS')); ?>',
    payrollPackageReadmePolicy: '<?php echo addslashes(org_js_index_i18n('BUSINESS_PAYROLL_PACKAGE_README_POLICY')); ?>',
    contactImageTooLong: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_TOO_LONG')); ?>',
    contactImageSaving: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_SAVING')); ?>',
    contactImageUnchanged: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_UNCHANGED')); ?>',
    contactImageProcessFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_PROCESS_FAILED')); ?>',
    transferMemberChosen: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_MEMBER_CHOSEN')); ?>',
    changeCanceled: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CHANGE_CANCELED')); ?>',
    noChangesToUpdate: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_CHANGES_TO_UPDATE')); ?>',
    updateInProgress: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_UPDATE_IN_PROGRESS')); ?>',
    businessNameFallback: '<?php echo addslashes(org_js_index_i18n('BUSINESSES')); ?>',
    businessSitesLoading: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_LOADING')); ?>',
    businessSitesGridInitFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_GRID_INIT_FAILED')); ?>',
    businessSitesLinkedLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_LINKED_LOADED')); ?>',
    businessSitesPremiumLocked: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_PREMIUM_LOCKED')); ?>',
    businessSitesLoadingLinked: '<?php echo addslashes(org_js_index_i18n('BUSINESS_SITES_LOADING_LINKED')); ?>',
    businessGroupsCreate: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_CREATE')); ?>',
    businessGroupsArchiveAction: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_ARCHIVE_ACTION')); ?>',
    businessGroupsArchiveConfirm: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_ARCHIVE_CONFIRM')); ?>',
    businessGroupsArchiveFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_ARCHIVE_FAILED')); ?>',
    businessGroupsDeleteAction: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_DELETE_ACTION')); ?>',
    businessGroupsDeleteConfirm: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_DELETE_CONFIRM')); ?>',
    businessGroupsDeleteActiveDenied: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_DELETE_ACTIVE_DENIED')); ?>',
    businessGroupsDeleteFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_DELETE_FAILED')); ?>',
    businessGroupsDeleted: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_DELETED')); ?>',
    businessGroupsRestoreAction: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_RESTORE_ACTION')); ?>',
    businessGroupsRestoreConfirm: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_RESTORE_CONFIRM')); ?>',
    businessGroupsRestoreFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_RESTORE_FAILED')); ?>',
    businessGroupsEditorTitle: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_EDITOR_TITLE')); ?>',
    businessGroupsGridInitFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_GRID_INIT_FAILED')); ?>',
    businessGroupsLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_LOAD_FAILED')); ?>',
    businessGroupsLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_LOADED')); ?>',
    businessGroupsLoading: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_LOADING')); ?>',
    businessGroupsNameRequired: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_NAME_REQUIRED')); ?>',
    businessGroupsSaveFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_SAVE_FAILED')); ?>',
    businessGroupsSaveChanges: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GROUPS_SAVE_CHANGES')); ?>',
    businessSitesRestoreAction: '<?php echo addslashes(org_js_index_i18n('SITES_RESTORE_ACTION')); ?>',
    businessSitesRestoreConfirm: '<?php echo addslashes(org_js_index_i18n('SITES_RESTORE_CONFIRM')); ?>',
    businessSitesRestoreFailed: '<?php echo addslashes(org_js_index_i18n('SITES_RESTORE_FAILED')); ?>',
    businessSitesRestored: '<?php echo addslashes(org_js_index_i18n('SITES_RESTORED')); ?>',
    payPeriodSaving: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PAY_PERIOD_SAVING')); ?>',
    payPeriodSavingStart: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PAY_PERIOD_SAVING_START')); ?>',
    profileSettingsSaving: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PROFILE_SETTINGS_SAVING')); ?>',
    payPeriodStartUpdated: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PAY_PERIOD_START_UPDATED')); ?>',
    profileSettingsSaved: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PROFILE_SETTINGS_SAVED')); ?>',
    transferConfirmType: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_CONFIRM_TYPE')); ?>',
    transferConfirmAccepted: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_CONFIRM_ACCEPTED')); ?>',
    transferConfirmMismatch: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_CONFIRM_MISMATCH')); ?>',
    transferSelectFromList: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_SELECT_FROM_LIST')); ?>',
    invalidRoleSelected: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVALID_ROLE_SELECTED')); ?>',
    inviteEmailOrResult: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITE_EMAIL_OR_RESULT')); ?>',
    correctHighlightedFields: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CORRECT_HIGHLIGHTED_FIELDS')); ?>',
    saveAccountDetailsFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SAVE_ACCOUNT_DETAILS_FAILED')); ?>',
    currencyUpdatedLabel: '<?php echo addslashes(org_js_index_i18n('SETTINGS_CURRENCY_UPDATED_LABEL')); ?>',
    timezoneUpdatedLabel: '<?php echo addslashes(org_js_index_i18n('SETTINGS_TIMEZONE_UPDATED_LABEL')); ?>',
    discoveryAutoRefresh: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_AUTO_REFRESH')); ?>',
    connectionsLoadedNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONNECTIONS_LOADED_NONE')); ?>',
    connectionsLoadedCount: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONNECTIONS_LOADED_COUNT')); ?>',
    invitesLoadedNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITES_LOADED_NONE')); ?>',
    invitesLoadedCount: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITES_LOADED_COUNT')); ?>',
    invitesPremiumLocked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITES_PREMIUM_LOCKED')); ?>',
    invitesLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITES_LOAD_FAILED')); ?>',
    accessRequestsLoadedNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUESTS_LOADED_NONE')); ?>',
    accessRequestsLoadedCount: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUESTS_LOADED_COUNT')); ?>',
    accessRequestsPremiumLocked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUESTS_PREMIUM_LOCKED')); ?>',
    accessRequestsLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUESTS_LOAD_FAILED')); ?>',
    discoveryResultsNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_RESULTS_NONE')); ?>',
    discoveryResultsCount: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_RESULTS_COUNT')); ?>',
    discoveryPremiumLocked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_PREMIUM_LOCKED')); ?>',
    discoveryLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_LOAD_FAILED')); ?>',
    freeAuditLoading: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_FREE_AUDIT_LOADING')); ?>',
    freeAuditLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_FREE_AUDIT_LOADED')); ?>',
    freeAuditLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_FREE_AUDIT_LOAD_FAILED')); ?>',
    memberRevokeConfirm: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REVOKE_CONFIRM')); ?>',
    memberRevokeSuccess: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REVOKE_SUCCESS')); ?>',
    memberRevokeFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REVOKE_FAILED')); ?>',
    memberReportsDialogTitleNamed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REPORTS_DIALOG_TITLE_NAMED')); ?>',
    memberReportsLoading: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REPORTS_LOADING')); ?>',
    memberReportsLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REPORTS_LOAD_FAILED')); ?>',
    memberReportsDenied: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_REPORTS_DENIED')); ?>',
    discoveryUnavailable: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_UNAVAILABLE')); ?>',
    inviteAccepted: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITE_ACCEPTED')); ?>',
    inviteAcceptFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INVITE_ACCEPT_FAILED')); ?>',
    previewEmpty: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PREVIEW_EMPTY')); ?>',
    previewLabel: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PREVIEW_LABEL')); ?>',
    personal: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TYPE_PERSONAL')); ?>',
    owner: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ROLE_OWNER')); ?>',
    member: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ROLE_MEMBER')); ?>',
    viewer: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ROLE_VIEWER')); ?>',
    contributor: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ROLE_CONTRIBUTOR')); ?>',
    coordinator: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ROLE_COORDINATOR')); ?>',
    manager: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ROLE_MANAGER')); ?>',
    loadingDetails: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOADING_DETAILS')); ?>',
    noConnections: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_CONNECTIONS')); ?>',
    removeConfirmPrefix: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REMOVE_CONFIRM_PREFIX')); ?>',
    removeConfirmSuffix: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REMOVE_CONFIRM_SUFFIX')); ?>',
    removeFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REMOVE_FAILED')); ?>',
    premiumAdminLocked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PREMIUM_ADMIN_LOCKED')); ?>',
    selfOrgWip: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SELF_ORG_WIP')); ?>',
    premiumAdminLockedDetailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PREMIUM_ADMIN_LOCKED_DETAILED')); ?>',
    memberInviteSent: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_INVITE_SENT')); ?>',
    memberInviteNeedsPersonalOrg: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBER_INVITE_NEEDS_PERSONAL_ORG')); ?>',
    requestJoinPending: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REQUEST_JOIN_PENDING')); ?>',
    requestJoinFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REQUEST_JOIN_FAILED')); ?>',
    noAccessRequests: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_ACCESS_REQUESTS')); ?>',
    governanceConnectionsEmpty: '<?php echo addslashes(org_js_index_i18n('BUSINESS_GOVERNANCE_CONNECTIONS_EMPTY')); ?>',
    accessRequestApproved: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_APPROVED')); ?>',
    accessRequestRejected: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_REJECTED')); ?>',
    accessRequestActionFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_ACTION_FAILED')); ?>',
    connectionLabel: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONNECTION_LABEL')); ?>',
    ownerLabel: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNER_LABEL')); ?>',
    statusLabel: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_STATUS_LABEL')); ?>',
    scopesLabel: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPES')); ?>',
    signalAccessRequestReceived: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_ACCESS_REQUEST_RECEIVED')); ?>',
    signalAccessRequestApproved: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_ACCESS_REQUEST_APPROVED')); ?>',
    signalAccessRequestRejected: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_ACCESS_REQUEST_REJECTED')); ?>',
    signalInviteAccepted: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_INVITE_ACCEPTED')); ?>',
    signalInviteSent: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_INVITE_SENT')); ?>',
    signalInviteRevoked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_INVITE_REVOKED')); ?>',
    signalAccessRevoked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_ACCESS_REVOKED')); ?>',
    signalMemberLeftBusiness: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_MEMBER_LEFT_BUSINESS')); ?>',
    signalOwnershipTransferred: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_OWNERSHIP_TRANSFERRED')); ?>',
    signalSettingsUpdated: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_SETTINGS_UPDATED')); ?>',
    signalSiteLinked: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SIGNAL_SITE_LINKED')); ?>',
    membershipConsentTitle: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_TITLE')); ?>',
    membershipConsentIntro: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_DESC')); ?>',
    membershipConsentAckRequired: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_ACK_REQUIRED')); ?>',
    membershipConsentDefaultDisclaimer: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_DEFAULT_DISCLAIMER')); ?>',
    sharedOrgSingleton: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SHARED_ORG_SINGLETON')); ?>',
    contactNameLabel: '<?php echo addslashes(org_js_index_i18n('NAME')); ?>',
    contactEmailLabel: '<?php echo addslashes(org_js_index_i18n('EMAIL')); ?>',
    contactPhoneLabel: '<?php echo addslashes(org_js_index_i18n('PHONE')); ?>',
    contactRoleLabel: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ROLE_PH')); ?>',
    businessDekBootstrapDone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BUSINESS_DEK_BOOTSTRAP_DONE')); ?>',
    businessDekBootstrapFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BUSINESS_DEK_BOOTSTRAP_FAILED')); ?>',
    gridStatusNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_GRID_STATUS_NONE')); ?>',
    gridStatusDetail: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_GRID_STATUS_DETAIL')); ?>',
    gridReasonLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_GRID_REASON_LOADED')); ?>',
    gridReasonFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_GRID_REASON_FAILED')); ?>',
    gridReasonSearch: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_GRID_REASON_SEARCH')); ?>',
    gridReasonSort: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_GRID_REASON_SORT')); ?>',
    gridReasonPage: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_GRID_REASON_PAGE')); ?>',
    datagridOrderDefault: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DATAGRID_ORDER_DEFAULT')); ?>',
    datagridSearchNone: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DATAGRID_SEARCH_NONE')); ?>',
    datagridSearchQuery: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DATAGRID_SEARCH_QUERY')); ?>',
    scopeStatusUpdated: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPE_STATUS_UPDATED')); ?>',
    scopeStatusLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPE_STATUS_LOADED')); ?>',
    scopeStatusCleared: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPE_STATUS_CLEARED')); ?>',
    auditGridUpdated: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_GRID_UPDATED')); ?>',
    membersGridStatusDetail: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_MEMBERS_GRID_STATUS_DETAIL')); ?>',
    ppPreviewStatus: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PP_PREVIEW_STATUS')); ?>',
    accountActivityLoaded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_LOADED')); ?>',
    accountActivityLoadFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_LOAD_FAILED')); ?>',
    nounBusiness: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NOUN_BUSINESS')); ?>',
    editorSubtitle: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_EDITOR_SUBTITLE')); ?>',
    valueUnknown: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_VALUE_UNKNOWN')); ?>',
    confirmTypeSharedToPersonal: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONFIRM_TYPE_SHARED_TO_PERSONAL')); ?>',
    confirmTypeMemberWarningOne: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONFIRM_TYPE_MEMBER_WARNING_ONE')); ?>',
    confirmTypeMemberWarningMany: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONFIRM_TYPE_MEMBER_WARNING_MANY')); ?>',
    confirmTypeGeneric: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONFIRM_TYPE_GENERIC')); ?>',
    confirmRole: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONFIRM_ROLE')); ?>',
    confirmStatusActivePending: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONFIRM_STATUS_ACTIVE_PENDING')); ?>',
    confirmStatusGeneric: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONFIRM_STATUS_GENERIC')); ?>',
    browserNoMatchQuery: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_NO_MATCH_QUERY')); ?>',
    browserFoundCount: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_FOUND_COUNT')); ?>',
    browserRequestAccess: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_REQUEST_ACCESS')); ?>',
    browserRequestSending: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_REQUEST_SENDING')); ?>',
    browserRequestSent: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_REQUEST_SENT')); ?>',
    browserRequestSentStatus: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_REQUEST_SENT_STATUS')); ?>',
    discoverySearchCleared: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_SEARCH_CLEARED')); ?>',
    discoverySearchApplied: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DISCOVERY_SEARCH_APPLIED')); ?>',
    accessRequestSubmittedId: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_SUBMITTED_ID')); ?>',
    auditControlTestDenied: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_DENIED')); ?>',
    auditControlTestGenerating: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_GENERATING')); ?>',
    auditControlTestRecorded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_RECORDED')); ?>',
    auditControlTestRecordedUploaded: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_RECORDED_UPLOADED')); ?>',
    auditControlTestFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUDIT_CONTROL_TEST_FAILED')); ?>',
    ppInvalidFrequency: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PP_INVALID_FREQUENCY')); ?>',
    ppInvalidLength: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PP_INVALID_LENGTH')); ?>',
    ppInvalidAnchor: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PP_INVALID_ANCHOR')); ?>',
    ppInvalidGrace: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PP_INVALID_GRACE')); ?>',
    ppSelectStartDate: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PP_SELECT_START_DATE')); ?>',
    ppInvalidStartDate: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PP_INVALID_START_DATE')); ?>',
    profilePayPeriodManagedBanner: '<?php echo addslashes(org_js_index_i18n('PROFILE_PAY_PERIOD_MANAGED_BANNER')); ?>',
    profilePayPeriodManagedHelp: '<?php echo addslashes(org_js_index_i18n('PROFILE_PAY_PERIOD_MANAGED_HELP')); ?>',
    profilePayPeriodManagedLink: '<?php echo addslashes(org_js_index_i18n('PROFILE_PAY_PERIOD_MANAGED_LINK')); ?>',
    unknownError: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_UNKNOWN_ERROR')); ?>',
    unavailable: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_UNAVAILABLE')); ?>',
    you: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_YOU')); ?>',
    businessMember: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BUSINESS_MEMBER')); ?>',
    timestampLocal: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TIMESTAMP_LOCAL')); ?>',
    timestampServer: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TIMESTAMP_SERVER')); ?>',
    timestampUtc: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TIMESTAMP_UTC')); ?>',
    timestampDetailsAria: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TIMESTAMP_DETAILS_ARIA')); ?>',
    timestampJoinedAria: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TIMESTAMP_JOINED_ARIA')); ?>',
    premiumNoticeTypeSelected: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PREMIUM_NOTICE_TYPE_SELECTED')); ?>',
    ownerSinceLabel: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNER_SINCE_LABEL')); ?>',
    accountActivityIpAddress: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_IP_ADDRESS')); ?>',
    accountActivitySignedIn: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_SIGNED_IN')); ?>',
    accountActivityLastActivity: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_LAST_ACTIVITY')); ?>',
    accountActivityAuthMethod: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_AUTH_METHOD')); ?>',
    accountActivityAuthStrength: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_AUTH_STRENGTH')); ?>',
    accountActivitySessionFingerprint: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_SESSION_FINGERPRINT')); ?>',
    accountActivityBrowser: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_BROWSER')); ?>',
    accountActivityOperatingSystem: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_OPERATING_SYSTEM')); ?>',
    accountActivityDeviceType: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_DEVICE_TYPE')); ?>',
    accountActivityPlatform: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_PLATFORM')); ?>',
    accountActivityLanguage: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_LANGUAGE')); ?>',
    accountActivityUserAgent: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_USER_AGENT')); ?>',
    accountActivityCurrentSession: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_CURRENT_SESSION')); ?>',
    accountActivitySession: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_SESSION')); ?>',
    accountActivityIp: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_IP')); ?>',
    accountActivityAuth: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_AUTH')); ?>',
    accountActivityTtl: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCOUNT_ACTIVITY_TTL')); ?>',
    deleteAccountTypePhrase: '<?php echo addslashes(org_js_index_i18n('SETTINGS_JS_DELETE_ACCOUNT_TYPE_PHRASE')); ?>',
    deleteDataTypePhrase: '<?php echo addslashes(org_js_index_i18n('SETTINGS_JS_DELETE_DATA_TYPE_PHRASE')); ?>',
    deleteDataInProgress: '<?php echo addslashes(org_js_index_i18n('SETTINGS_JS_DELETE_DATA_IN_PROGRESS')); ?>',
    deleteDataComplete: '<?php echo addslashes(org_js_index_i18n('SETTINGS_JS_DELETE_DATA_COMPLETE')); ?>',
    deleteDataFailed: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_DELETE_DATA_FAILED')); ?>',
    connectionsAddPerson: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_ADD_PERSON')); ?>',
    connectionsApprove: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_APPROVE')); ?>',
    connectionsCancelRequest: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_CANCEL_REQUEST')); ?>',
    connectionsDecline: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_DECLINE')); ?>',
    connectionsGrantsHelp: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_GRANTS_HELP')); ?>',
    connectionsManage: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_MANAGE')); ?>',
    connectionsNoAccess: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_NO_ACCESS')); ?>',
    connectionsPendingIncoming: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PENDING_INCOMING')); ?>',
    connectionsPendingOutgoing: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PENDING_OUTGOING')); ?>',
    connectionsPeopleEmpty: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PEOPLE_EMPTY')); ?>',
    connectionsPeopleLoadFailed: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PEOPLE_LOAD_FAILED')); ?>',
    connectionsPersonAccessLabel: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_ACCESS_LABEL')); ?>',
    connectionsPersonConnected: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_CONNECTED')); ?>',
    connectionsPersonConnectedToYou: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_CONNECTED_TO_YOU')); ?>',
    connectionsPersonConnectionTypeLabel: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_CONNECTION_TYPE_LABEL')); ?>',
    connectionsPersonDeclinedSummary: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_DECLINED_SUMMARY')); ?>',
    connectionsPersonEmailRequired: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_EMAIL_REQUIRED')); ?>',
    connectionsPersonLabel: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_LABEL')); ?>',
    connectionsPersonOwnerOnly: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_OWNER_ONLY')); ?>',
    connectionsPersonPermissionsLabel: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_PERMISSIONS_LABEL')); ?>',
    connectionsPersonRequestFailed: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_REQUEST_FAILED')); ?>',
    connectionsPersonRequestSent: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_REQUEST_SENT')); ?>',
    connectionsPersonRequestedLabel: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_REQUESTED_LABEL')); ?>',
    connectionsPersonRemovedSummary: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_REMOVED_SUMMARY')); ?>',
    connectionsPersonSaveSuccess: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_SAVE_SUCCESS')); ?>',
    connectionsPersonSharedByLabel: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_SHARED_BY_LABEL')); ?>',
    connectionsPersonStatusLabel: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_STATUS_LABEL')); ?>',
    connectionsPersonViewSharedWork: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_PERSON_VIEW_SHARED_WORK')); ?>',
    connectionsRemove: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_REMOVE')); ?>',
    connectionsRevokeGrantFailed: '<?php echo addslashes(org_js_index_i18n('CONNECTIONS_REVOKE_GRANT_FAILED')); ?>',
    previous: '<?php echo addslashes(org_js_index_i18n('PREVIOUS')); ?>',
    cancel: '<?php echo addslashes(org_js_index_i18n('CANCEL')); ?>',
  };

  Object.assign(PC.config, {
    GROSS: '<?php echo addslashes(org_js_index_i18n('GROSS')); ?>',
    NET: '<?php echo addslashes(org_js_index_i18n('NET')); ?>',
    DEDUCTIONS: '<?php echo addslashes(org_js_index_i18n('DEDUCTIONS')); ?>',
    EARNINGS_PIEGRAPHS_NO_VALUES: '<?php echo addslashes(org_js_index_i18n('EARNINGS_PIEGRAPHS_NO_VALUES')); ?>',
    EARNINGS_LABEL: '<?php echo addslashes(org_js_index_i18n('EARNINGS_LABEL')); ?>',
    DATE: '<?php echo addslashes(org_js_index_i18n('DATE')); ?>',
    SITE: '<?php echo addslashes(org_js_index_i18n('SITE')); ?>',
    WAGE: '<?php echo addslashes(org_js_index_i18n('WAGE')); ?>',
    HOURS: '<?php echo addslashes(org_js_index_i18n('HOURS')); ?>',
    REGULAR_HOURS: '<?php echo addslashes(org_js_index_i18n('REGULAR_HOURS')); ?>',
    OVERTIME_HOURS: '<?php echo addslashes(org_js_index_i18n('OVERTIME_HOURS')); ?>',
    LOA: '<?php echo addslashes(org_js_index_i18n('LOA')); ?>',
    TRAVEL: '<?php echo addslashes(org_js_index_i18n('TRAVEL')); ?>',
    NOT_FOUND: '<?php echo addslashes(org_js_index_i18n('NOT_FOUND')); ?>',
    EARNINGS_DAILY_GRID_INSTRUCTIONS_FOR: '<?php echo addslashes(org_js_index_i18n('EARNINGS_DAILY_GRID_INSTRUCTIONS_FOR')); ?>',
    EARNINGS_TREND_NO_DATA_STATUS: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_NO_DATA_STATUS')); ?>',
    EARNINGS_TREND_NO_DATA_DESC: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_NO_DATA_DESC')); ?>',
    EARNINGS_TREND_NO_NUMERIC_STATUS: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_NO_NUMERIC_STATUS')); ?>',
    EARNINGS_TREND_NO_NUMERIC_DESC: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_NO_NUMERIC_DESC')); ?>',
    EARNINGS_TREND_UPDATED_STATUS: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_UPDATED_STATUS')); ?>',
    EARNINGS_TREND_UPDATED_DESC: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_UPDATED_DESC')); ?>',
    EARNINGS_CHART_DATA_LOAD_FAILED: '<?php echo addslashes(org_js_index_i18n('EARNINGS_CHART_DATA_LOAD_FAILED')); ?>',
    EARNINGS_TREND_LOAD_FAILED_STATUS: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_LOAD_FAILED_STATUS')); ?>',
    EARNINGS_TREND_DIRECTION_INCREASING: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_DIRECTION_INCREASING')); ?>',
    EARNINGS_TREND_DIRECTION_DECREASING: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_DIRECTION_DECREASING')); ?>',
    EARNINGS_TREND_DIRECTION_FLAT: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_DIRECTION_FLAT')); ?>',
    EARNINGS_TREND_HOVER_TOOLTIP: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_HOVER_TOOLTIP')); ?>',
    EARNINGS_TREND_Y_AXIS_LABEL: '<?php echo addslashes(org_js_index_i18n('EARNINGS_TREND_Y_AXIS_LABEL')); ?>',
<?php
$memberForecastI18nKeys = [
  'EARNINGS_FORECAST_NO_DATA', 'EARNINGS_FORECAST_LOAD_FAILED', 'EARNINGS_FORECAST_TITLE',
  'EARNINGS_FORECAST_BADGE_ESTIMATE', 'EARNINGS_FORECAST_BADGE_NOT_CRA', 'EARNINGS_FORECAST_WORKSPACE_ARIA',
  'EARNINGS_FORECAST_LOADING', 'EARNINGS_FORECAST_NEXT_PAYCHECK', 'EARNINGS_FORECAST_NEXT_30_DAYS',
  'EARNINGS_FORECAST_YEAR_PROJECTION', 'EARNINGS_FORECAST_CARD_GROSS', 'EARNINGS_FORECAST_CARD_HOURS',
  'EARNINGS_FORECAST_CARD_CONFIDENCE', 'EARNINGS_FORECAST_SCENARIO_CONSERVATIVE', 'EARNINGS_FORECAST_SCENARIO_NORMAL',
  'EARNINGS_FORECAST_SCENARIO_OVERTIME', 'EARNINGS_FORECAST_SCENARIO_CUSTOM', 'EARNINGS_FORECAST_SCENARIOS_TITLE',
  'EARNINGS_FORECAST_ASSUMPTIONS_TITLE', 'EARNINGS_FORECAST_ASSUMPTIONS_EMPTY', 'EARNINGS_FORECAST_ASSUMP_FIELD',
  'EARNINGS_FORECAST_ASSUMP_VALUE', 'EARNINGS_FORECAST_ASSUMP_SOURCE', 'EARNINGS_FORECAST_VALUE_MISSING',
  'EARNINGS_FORECAST_SOURCE_SAVED', 'EARNINGS_FORECAST_SOURCE_SCHEDULED', 'EARNINGS_FORECAST_SOURCE_TEMPORARY',
  'EARNINGS_FORECAST_SOURCE_ESTIMATED', 'EARNINGS_FORECAST_SOURCE_MISSING', 'EARNINGS_FORECAST_CONFIDENCE_HIGH',
  'EARNINGS_FORECAST_CONFIDENCE_MEDIUM', 'EARNINGS_FORECAST_CONFIDENCE_LOW', 'EARNINGS_FORECAST_TIMELINE_TITLE',
  'EARNINGS_FORECAST_TIMELINE_SR_FMT', 'EARNINGS_FORECAST_CALC_TITLE', 'EARNINGS_FORECAST_RESET_PROFILE',
  'EARNINGS_FORECAST_RESET_SCHEDULED', 'EARNINGS_FORECAST_CALC_WAGE', 'EARNINGS_FORECAST_CALC_REG_HRS',
  'EARNINGS_FORECAST_CALC_OT_HRS', 'EARNINGS_FORECAST_CALC_LOA', 'EARNINGS_FORECAST_CALC_TRAVEL',
  'EARNINGS_FORECAST_CALC_PROVINCE', 'EARNINGS_FORECAST_CALC_PAY_FREQ', 'EARNINGS_FORECAST_CALC_ANCHOR',
  'EARNINGS_FORECAST_CALC_YTD_GROSS', 'EARNINGS_FORECAST_PAY_FREQ_WEEKLY', 'EARNINGS_FORECAST_PAY_FREQ_BIWEEKLY',
  'EARNINGS_FORECAST_PAY_FREQ_SEMIMONTHLY', 'EARNINGS_FORECAST_PAY_FREQ_MONTHLY', 'EARNINGS_FORECAST_ASSUMP_WAGE',
  'EARNINGS_FORECAST_ASSUMP_REG_HRS', 'EARNINGS_FORECAST_ASSUMP_OT_HRS', 'EARNINGS_FORECAST_ASSUMP_LOA',
  'EARNINGS_FORECAST_ASSUMP_TRAVEL', 'EARNINGS_FORECAST_ASSUMP_PROVINCE', 'EARNINGS_FORECAST_ASSUMP_PAY_FREQ',
  'EARNINGS_FORECAST_ASSUMP_ANCHOR', 'EARNINGS_FORECAST_ASSUMP_YTD_GROSS',   'EARNINGS_FORECAST_PREVIEW_FAILED',
  'EARNINGS_FORECAST_SUMMARY_UPDATED_FMT', 'EARNINGS_FORECAST_DISCLAIMER',
  'EARNINGS_FORECAST_PROGRESS_LABEL', 'EARNINGS_FORECAST_PROGRESS_CURRENT', 'EARNINGS_FORECAST_PROGRESS_FORECAST',
  'EARNINGS_DAILY_LOAD_FAILED_FMT', 'EARNINGS_EXPORT_FAILED_FMT',
];
foreach ($memberForecastI18nKeys as $memberForecastI18nKey) {
  echo "    {$memberForecastI18nKey}: '" . addslashes(org_js_index_i18n($memberForecastI18nKey)) . "',\n";
}
?>
  });

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
    businesses: [],
    inlineEditorMode: false,
    currentConnectionBusinessId: '',
    selectedBusinessId: '',
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
    profilePayPeriodManagedByBusiness: false,
    editorAutoSaveTimerId: null,
    editorSaveInFlight: false,
    editorSavePendingSource: '',
    editorLastSavedSignature: '',
    editorHydrating: false,
    editorSettingsCache: {},
    businessDetailsLastSavedSignature: '',
    businessDetailsSaveInFlight: false,
    businessDetailsSavePendingSource: '',
    payrollLastSavedSignature: '',
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
    browserSelectedResultKey: '',
    personConnections: [],
    personCapabilities: {},
    personManageConnectionId: '',
    auditRealtimeIntervalId: null,
    auditRealtimeTopEventId: '',
    auditRealtimeReady: false,
    auditGridManager: null,
    auditGridOrgId: '',
    contextMemberCount: null,
    contextSiteCount: null,
    freeAuditGridManager: null,
    freeAuditGridOrgId: '',
    execSummaryPendingCount: null,
    notificationsIntervalId: null,
    notificationsSignature: '',
    discoveryIntervalId: null,
    discoverySignature: '',
    requestAccessLevel: 'readonly',
    inviteHistoryGridManager: null,
    inviteHistoryGridOrgId: '',
    membersGridManager: null,
    membersGridOrgId: '',
    membersBulkSelectedIds: [],
    membersBulkSelectAllActive: false,
    membersBulkTotalCount: 0,
    sitesGridActiveManager: null,
    sitesGridArchivedManager: null,
    sitesGridOrgId: '',
    sitesGridManagerStatus: '',
    sitesGridStatus: 'active',
    membersImport: {
      importId: '',
      challengeId: '',
      verified: false,
    },
    transferCandidates: [],
    transferSelectedUUID: '',
    contactImagePopoverTargetFieldId: '',
    contactImagePopoverTrigger: null,
    customContactCards: [],
  };
