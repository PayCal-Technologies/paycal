<?php declare(strict_types=1);

use PayCal\Domain\Page;
use PayCal\Domain\Render;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('contract')]
final class SidebarNavigationContractTest extends TestCase
{
  #[Test]
  public function freeUserSidebarHasNoVisibleBusinessGroup(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false);
    $businessGroup = $this->groupById($navigation, 'business');

    $this->assertFalse($businessGroup['visible']);
    $this->assertSame([], $businessGroup['links']);
  }

  #[Test]
  public function businessWorkspaceSidebarListsAllBusinessSubpages(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false, false, true);
    $businessGroup = $this->groupById($navigation, 'business');

    $this->assertTrue($businessGroup['visible']);
    $this->assertCount(7, $businessGroup['links']);

    $pages = array_column($businessGroup['links'], 'page');
    $hrefs = array_column($businessGroup['links'], 'href');
    $names = array_column($businessGroup['links'], 'name');

    $this->assertSame([
      Page::BUSINESS_DETAILS->value,
      Page::BUSINESS_MEMBERS->value,
      Page::BUSINESS_GROUPS->value,
      Page::BUSINESS_SITES->value,
      Page::BUSINESS_PAYROLL->value,
      Page::BUSINESS_REPORTS->value,
      Page::BUSINESS_AUDIT->value,
    ], $pages);

    $this->assertSame([
      '/business/details/',
      '/business/members/',
      '/business/groups/',
      '/business/sites/',
      '/business/payroll/',
      '/business/reports/',
      '/business/audit/',
    ], $hrefs);

    $this->assertSame('Details', $names[0]);
    $this->assertSame('Reports', $names[5]);
    $this->assertSame('Audit', $names[6]);
    $this->assertNotContains(Page::BUSINESS_DASHBOARD->value, $pages);
  }

  #[Test]
  public function personalAndBusinessReportsLabelsAreDistinct(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false, false, true);
    $paycalGroup = $this->groupById($navigation, 'paycal');
    $businessGroup = $this->groupById($navigation, 'business');

    $personalReports = null;
    foreach ($paycalGroup['links'] as $link) {
      if (($link['page'] ?? '') === Page::REPORTS->value) {
        $personalReports = $link;
        break;
      }
    }

    $businessReports = null;
    foreach ($businessGroup['links'] as $link) {
      if (($link['page'] ?? '') === Page::BUSINESS_REPORTS->value) {
        $businessReports = $link;
        break;
      }
    }

    $this->assertNotNull($personalReports);
    $this->assertNotNull($businessReports);
    $this->assertSame('Reports', $personalReports['arialabel']);
    $this->assertSame('Reports', $businessReports['arialabel']);
    $this->assertSame(Page::REPORTS->value, $personalReports['page']);
    $this->assertSame(Page::BUSINESS_REPORTS->value, $businessReports['page']);
    $this->assertNotSame($personalReports['href'], $businessReports['href']);
  }

  #[Test]
  public function businessWorkspaceGroupHeadingLinksToDashboard(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false, false, true);
    $businessGroup = $this->groupById($navigation, 'business');

    $this->assertSame(Page::BUSINESS_DASHBOARD->value, $businessGroup['heading']['page']);
    $this->assertSame('/business/', $businessGroup['heading']['href']);
    $this->assertSame('Business', $businessGroup['heading']['name']);
  }

  #[Test]
  public function adminWithoutPremiumStillGetsBusinessGroup(): void
  {
    $navigation = Render::buildSidebarNavigation(false, true);
    $businessGroup = $this->groupById($navigation, 'business');

    $this->assertTrue($businessGroup['visible']);
    $this->assertCount(7, $businessGroup['links']);
  }

  #[Test]
  public function legacyTeamReportsViewRedirectsToBusinessReports(): void
  {
    $reportsIndex = $this->readProjectFile('reports/index.php');

    $this->assertStringContainsString('$legacyReportsView === \'team\' || $legacyReportsView === \'group\'', $reportsIndex);
    $this->assertStringContainsString("header('Location: /business/reports/", $reportsIndex);
    $this->assertStringContainsString('302', $reportsIndex);
    $this->assertStringNotContainsString('earnings_view_tabs', $reportsIndex);
  }

  #[Test]
  public function paycalGroupIncludesPersonalNavigationOnly(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false);
    $paycalGroup = $this->groupById($navigation, 'paycal');
    $pages = array_column($paycalGroup['links'], 'page');

    $this->assertSame([Page::SITES->value, Page::REPORTS->value], $pages);
    $this->assertContains(Page::SITES->value, $pages);
    $this->assertContains(Page::REPORTS->value, $pages);
    $this->assertNotContains(Page::PROFILE->value, $pages);
    $this->assertNotContains('PAGE_SETTINGS', $pages);
    $this->assertNotContains(Page::EARNINGS->value, $pages);
    $this->assertNotContains(Page::INDEX->value, $pages);
    $this->assertNotContains(Page::BUSINESS_DASHBOARD->value, $pages);
  }

  #[Test]
  public function regularUsersCanReceiveBusinessConnectionsLink(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false);
    $paycalGroup = $this->groupById($navigation, 'paycal');
    $pages = array_column($paycalGroup['links'], 'page');

    $this->assertNotContains(Page::CONNECTIONS->value, $pages);

    $connectionsLink = Render::regularConnectionsNavLink(false);
    $this->assertSame('/connections/', $connectionsLink['href']);
    $this->assertSame('Connections', $connectionsLink['name']);
    $this->assertSame('', $connectionsLink['class']);
    $this->assertSame('pages', $connectionsLink['item_class']);
  }

  #[Test]
  public function premiumUnconnectedUsersCanHideBusinessWorkspaceAndShowBusinessConnectionsLink(): void
  {
    $navigation = Render::buildSidebarNavigation(true, false, false, false);
    $paycalGroup = $this->groupById($navigation, 'paycal');
    $businessGroup = $this->groupById($navigation, 'business');
    $paycalPages = array_column($paycalGroup['links'], 'page');

    $this->assertFalse($businessGroup['visible']);
    $this->assertNotContains(Page::CONNECTIONS->value, $paycalPages);
    $this->assertSame('Connections', Render::regularConnectionsNavLink(false)['name']);
  }

  #[Test]
  public function activeMembershipWithoutBusinessTierShowsBusinessLeafButNoBusinessWorkspace(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false, false, false);
    $businessGroup = $this->groupById($navigation, 'business');
    $paycalGroup = $this->groupById($navigation, 'paycal');
    $paycalPages = array_column($paycalGroup['links'], 'page');

    $this->assertFalse($businessGroup['visible']);
    $this->assertNotContains(Page::CONNECTIONS->value, $paycalPages);
    $this->assertSame('Connections', Render::regularConnectionsNavLink(true)['name']);
    $this->assertSame(Page::CONNECTIONS->value, Render::regularConnectionsNavLink(true)['page']);
    $this->assertSame('/connections/', Render::regularConnectionsNavLink(true)['href']);
    $this->assertSame('pages', Render::regularConnectionsNavLink(true)['item_class']);
  }

  #[Test]
  public function settingsUtilityLinkGoesToAccessibilityAndStartsBottomGroup(): void
  {
    $settingsLink = Render::settingsUtilityNavLink();

    $this->assertSame('/settings/accessibility/', $settingsLink['href']);
    $this->assertSame('e', $settingsLink['access_key']);
    $this->assertSame('pages nav_sidebar_bottom_start', $settingsLink['item_class']);
  }

  #[Test]
  public function paycalGroupHeadingLinksToCalendarHome(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false);
    $paycalGroup = $this->groupById($navigation, 'paycal');

    $this->assertSame(Page::INDEX->value, $paycalGroup['heading']['page']);
    $this->assertSame('/', $paycalGroup['heading']['href']);
    $this->assertSame('PayCal', $paycalGroup['heading']['name']);
  }

  #[Test]
  public function paycalGroupHeadingReflectsBusinessTier(): void
  {
    $premiumNavigation = Render::buildSidebarNavigation(true, false);
    $businessNavigation = Render::buildSidebarNavigation(true, false, false, true, true);

    $this->assertSame('PayCal Premium', $this->groupById($premiumNavigation, 'paycal')['heading']['name']);
    $this->assertSame('PayCal Business', $this->groupById($businessNavigation, 'paycal')['heading']['name']);
  }

  #[Test]
  public function sidebarNavigationHasPaycalAndBusinessGroupsOnly(): void
  {
    $navigation = Render::buildSidebarNavigation(true, false);

    $this->assertArrayHasKey('groups', $navigation);
    $this->assertCount(2, $navigation['groups']);
    $this->assertSame(['paycal', 'business'], array_column($navigation['groups'], 'id'));
    $this->assertArrayNotHasKey('sections', $navigation);
  }

  #[Test]
  public function businessRoutesRedirectNonBusinessUsersToPricing(): void
  {
    $layout = $this->readProjectFile('business/_layout.php');
    $dashboard = $this->readProjectFile('business/index.php');
    $businessNav = $this->readProjectFile('src/Domain/BusinessNav.php');

    $this->assertStringContainsString('BusinessNav::requirePremiumAccess()', $layout);
    $this->assertStringContainsString('BusinessNav::requirePremiumAccess()', $dashboard);
    $this->assertStringContainsString('SubscriptionGate::hasActiveBusiness($userUUID)', $businessNav);
    $this->assertStringNotContainsString('SubscriptionGate::hasActivePremium($userUUID)', $businessNav);
    $this->assertStringContainsString("header('Location: /pricing/'", $businessNav);
  }

  #[Test]
  public function businessConnectionsPageIsForRegularUserDiscovery(): void
  {
    $connectionsPage = $this->readProjectFile('connections/index.php');
    $connectionsPanel = $this->readProjectFile('business/_partials/profile_connect_panel.php');
    $connectionsJs = $this->readProjectFile('js/business/core/person-connections.js.php');

    $this->assertStringContainsString('$currentPage = Page::CONNECTIONS->value;', $connectionsPage);
    $this->assertStringContainsString('profile_connect_panel.php', $connectionsPage);
    $this->assertStringContainsString('data-business-subpage="connections"', $connectionsPage);
    $this->assertStringContainsString('CONNECTIONS_PAGE_TITLE', $connectionsPanel);
    $this->assertStringContainsString('CONNECTIONS_PEOPLE_TITLE', $connectionsPanel);
    $this->assertStringContainsString('calendar_view', $connectionsJs);
    $this->assertStringContainsString('/calendar/?user_uuid=', $connectionsJs);
    $this->assertStringContainsString('connectionsPersonViewSharedWork', $connectionsJs);
    $this->assertStringContainsString("Render::jsScript('business')", $this->readProjectFile('business/_partials/footer_shared.php'));
    $this->assertStringNotContainsString('<script>', $connectionsPage);
  }

  #[Test]
  public function businessWorkspaceGridApisRequireBusinessTierOrAdmin(): void
  {
    $controller = $this->readProjectFile('src/Controllers/BusinessDiscoveryController.php');

    $this->assertStringContainsString('private static function requireBusinessWorkspace', $controller);
    $this->assertStringContainsString('User::isAdmin()', $controller);
    $this->assertStringContainsString('SubscriptionGate::hasActiveBusiness($userUUID)', $controller);
    $this->assertStringNotContainsString("self::requireBusinessWorkspace('businesses.list')", $controller);
    $this->assertStringContainsString("self::requireBusinessWorkspace('businesses.grid')", $controller);
    $this->assertStringContainsString('Business workspace access requires PayCal Business.', $controller);
  }

  #[Test]
  public function headerUsesGroupedSidebarNavigation(): void
  {
    $header = $this->readProjectFile('header.php');

    $this->assertStringContainsString('Render::buildSidebarNavigation(', $header);
    $this->assertStringContainsString('Render::renderSidebarNavigation(', $header);
    $this->assertStringContainsString('Render::settingsUtilityNavLink()', $header);
    $this->assertStringContainsString('Render::regularConnectionsNavLink($hasActiveBusinessMembershipForNav)', $header);
    $this->assertStringContainsString('$showBusinessWorkspaceForNav = $isAdminForNav || $hasBusinessSubscriptionForNav;', $header);
    $this->assertStringContainsString('$showRegularBusinessLeafForNav = !$isAdminForNav && !$hasBusinessSubscriptionForNav;', $header);
    $this->assertStringNotContainsString('Page::BUSINESSES, Page::PROFILE', $header);

    $regularBusinessLeafOffset = strpos($header, 'Render::regularConnectionsNavLink($hasActiveBusinessMembershipForNav)');
    $settingsOffset = strpos($header, 'Render::settingsUtilityNavLink()');
    $keyboardOffset = strpos($header, "Strings::headerI18n('KEYBOARD')");
    $signoutOffset = strpos($header, 'id="call_signout_modal"');
    $this->assertNotFalse($regularBusinessLeafOffset);
    $this->assertNotFalse($settingsOffset);
    $this->assertNotFalse($keyboardOffset);
    $this->assertNotFalse($signoutOffset);
    $this->assertLessThan($settingsOffset, $regularBusinessLeafOffset);
    $this->assertLessThan($keyboardOffset, $settingsOffset);
    $this->assertLessThan($signoutOffset, $keyboardOffset);
    $this->assertStringNotContainsString('pages nav_sidebar_bottom_start"><a href="/help/"', $header);
  }

  #[Test]
  public function legacyEarningsRouteEntrypointIsRemoved(): void
  {
    $this->assertFileDoesNotExist(__DIR__ . '/../../../earnings/index.php');
  }

  #[Test]
  public function reportsNavLinkHasRAccessKey(): void
  {
    $navigation = Render::buildSidebarNavigation(false, false);
    $paycalGroup = $this->groupById($navigation, 'paycal');
    $reportsLink = null;

    foreach ($paycalGroup['links'] as $link) {
      if (($link['page'] ?? '') === Page::REPORTS->value) {
        $reportsLink = $link;
        break;
      }
    }

    $this->assertNotNull($reportsLink);
    $this->assertSame('/reports/', $reportsLink['href']);
    $this->assertSame('R', $reportsLink['access_key']);
  }

  #[Test]
  public function sidebarRendererUsesGroupHeadingAndSublinkClasses(): void
  {
    $render = $this->readProjectFile('src/Domain/Render.php');
    $navigationCss = $this->readProjectFile('css/navigation/index.php');

    $this->assertStringContainsString('nav_group_heading', $render);
    $this->assertStringContainsString('nav_sublink', $render);
    $this->assertStringContainsString('item_class', $render);
    $this->assertStringContainsString('pointer-events: auto;', $navigationCss);
    $this->assertStringContainsString('--nav-block-size: 36px;', $navigationCss);
    $this->assertStringContainsString('--nav-sidebar-group-gap: 1.3rem;', $navigationCss);
    $this->assertStringContainsString('li.nav_sidebar_bottom_start', $navigationCss);
    $this->assertStringContainsString('height: var(--nav-block-size);', $navigationCss);
    $this->assertStringContainsString('width: var(--nav-icon-size);', $navigationCss);
  }

  #[Test]
  public function sidebarGroupingLayoutAppliesInCollapsedAndExpandedStates(): void
  {
    $navigationCss = $this->readProjectFile('css/navigation/index.php');

    $this->assertStringContainsString('li.nav_group_heading:not(:first-child)', $navigationCss);
    $this->assertStringContainsString('li.nav_sidebar_bottom_start', $navigationCss);
    $this->assertStringContainsString('margin-top: auto;', $navigationCss);
    $this->assertStringContainsString('height: 100% !important;', $navigationCss);
    $this->assertStringContainsString('min-height: 100% !important;', $navigationCss);
    $this->assertStringContainsString('flex: 0 0 var(--nav-block-size) !important;', $navigationCss);
    $this->assertStringContainsString('width: var(--nav-collapsed-strip-size) !important;', $navigationCss);
    $this->assertStringNotContainsString(':not(.nav-collapsed) #page_header.nav_component--header:not(.nav_component--public) .nav_menu--primary > ul > li.nav_sidebar_bottom_start', $navigationCss);
  }

  #[Test]
  public function authenticatedHeaderProvidesMobileNavigationBar(): void
  {
    $header = $this->readProjectFile('header.php');

    $this->assertStringContainsString('id="mobile_navigation_bar"', $header);
    $this->assertStringContainsString('class="mobile_navigation_title"', $header);
    $this->assertStringContainsString('$isSidePrimaryNav && $isAuthenticated', $header);
  }

  #[Test]
  public function compactSidebarUsesFullscreenMobileOverlay(): void
  {
    $navigationCss = $this->readProjectFile('css/navigation/index.php');
    $navigationJs = $this->readProjectFile('js/navigation-toggle.js');

    $this->assertStringContainsString('--mobile-nav-bar-size', $navigationCss);
    $this->assertStringContainsString('body[data-nav-viewport-compact] .mobile_navigation_bar', $navigationCss);
    $this->assertStringContainsString('position: relative;', $navigationCss);
    $this->assertStringContainsString('width: 100vw;', $navigationCss);
    $this->assertStringContainsString('height: 100dvh;', $navigationCss);
    $this->assertStringContainsString('padding-top: 0;', $navigationCss);
    $this->assertDoesNotMatchRegularExpression(
      '/body\[data-nav-viewport-compact\][^{]+#main\s*\{[^}]*padding\s*:/',
      $navigationCss,
      'Late compact nav state must not change #main padding after first paint',
    );
    $this->assertStringContainsString("document.body.toggleAttribute('data-nav-viewport-compact', compact);", $navigationJs);
  }

  #[Test]
  public function proximitySidebarRevealRequiresHoverIntentDelay(): void
  {
    $navigationJs = $this->readProjectFile('js/navigation-toggle.js');

    $this->assertStringContainsString('DEFAULT_PROXIMITY_REVEAL_DELAY_MS = 400', $navigationJs);
    $this->assertStringContainsString('MIN_PROXIMITY_REVEAL_DELAY_MS = 200', $navigationJs);
    $this->assertStringContainsString('MAX_PROXIMITY_REVEAL_DELAY_MS = 3000', $navigationJs);
    $this->assertStringContainsString('proximityIntentTimer = setTimeout', $navigationJs);
    $this->assertStringContainsString('coreConfig?.nav_proximity_delay_ms', $navigationJs);
    $this->assertStringContainsString("document.addEventListener('mouseleave', closeProximityHover);", $navigationJs);
    $this->assertStringContainsString("window.addEventListener('blur', closeProximityHover);", $navigationJs);
  }

  /**
   * @param array{groups: array<int, array{id: string, visible: bool, heading: array<string, string>, links: array<int, array<string, string>>}>} $navigation
   * @return array{id: string, visible: bool, heading: array<string, string>, links: array<int, array<string, string>>}
   */
  private function groupById(array $navigation, string $id): array
  {
    foreach ($navigation['groups'] as $group) {
      if (($group['id'] ?? '') === $id) {
        return $group;
      }
    }

    $this->fail('Missing sidebar group: ' . $id);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
