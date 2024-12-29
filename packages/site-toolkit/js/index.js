/**
 * External dependencies
 */
import type { MixedElement } from 'react';
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ConsentForm } from './components/consent';
import { SubscriptionManager } from './components/my-account';

domReady(() => {
	const {
		blockeraSiteToolkitSubscriptions: subscriptions = [
			{
				productTitle: '',
				productColor: '',
				productLogo: '',
				subscriptionId: 0,
				productVersion: '',
				updatedOn: '',
				plan: '',
				maxDomains: 0,
				upgradable: '',
				isAutoRenew: false,
				startDate: '',
				expiryDate: '',
				renewAmount: '',
				downloads: [],
				activeWebsites: [],
				status: '',
			},
		],
		clientId = '',
		clientUrl = '',
		redirectUrl = '',
		consentNonce = '',
		clientWebsite = '',
		isConsentForm = false,
	} = window;

	if (isConsentForm) {
		const root = createRoot(
			document.getElementById('blockera-site-toolkit-consent-form')
		);

		root.render(
			<ConsentForm
				clientId={clientId}
				clientUrl={clientUrl}
				redirectUrl={redirectUrl}
				consentNonce={consentNonce}
				subscriptions={subscriptions}
				clientWebsite={clientWebsite}
			/>
		);

		return;
	}

	const root = createRoot(
		document.getElementById('blockera-site-toolkit-subscription-manager')
	);

	const MappedSubscriptions = subscriptions.map((subscription) => {
		return <SubscriptionManager {...subscription} />;
	});

	root.render(MappedSubscriptions);
});

export * from './components';
