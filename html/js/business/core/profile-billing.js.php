  // Core module: account billing route gate and business upgrade handoff

  const initializeProfileBilling = async () => {
    const upgradeBtn = document.getElementById('billing_upgrade_business_btn')
      || document.getElementById('billing_upgrade_business_subscribed_btn')
      || document.getElementById('billing_upgrade_btn');
    const routeGateDialog = document.getElementById('businesses_route_gate_dialog');
    const routeGateBillingBtn = document.getElementById('businesses_route_gate_billing_btn');
    const billingPanel = document.getElementById('panel-billing');

    const params = new URLSearchParams(window.location.search);

    const clearBusinessesRouteIntent = () => {
      if (!params.has('from_businesses')) {
        return;
      }

      params.delete('from_businesses');
      const nextQuery = params.toString();
      const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}${window.location.hash}`;
      window.history.replaceState({}, document.title, nextUrl);
    };

    const closeRouteGateDialog = () => {
      if (routeGateDialog instanceof HTMLDialogElement && routeGateDialog.open) {
        routeGateDialog.close();
      }
    };

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
      cancelUrl: '/settings/account/?billing=cancel',
      returnUrl: '/settings/account/#panel-billing',
      onPremiumActivated: () => {
        closeRouteGateDialog();
      },
      onBusinessActivated: () => {
        closeRouteGateDialog();
      },
    });

    const subData = billingController.subscription;

    if (params.get('from_businesses') === '1' && !(subData && subData.is_business)) {
      clearBusinessesRouteIntent();
      if (routeGateDialog instanceof HTMLDialogElement && !routeGateDialog.open) {
        routeGateDialog.showModal();
      }
    }
  };
