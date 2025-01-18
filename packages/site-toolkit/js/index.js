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
import { LicenseManager } from './components/my-account';

domReady(() => {
	const {
		blockeraSiteToolkitLicenses: licenses = [
			{
				productTitle: '',
				productColor: '',
				productLogo: '',
				licenseId: 0,
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
				licenses={licenses}
				clientUrl={clientUrl}
				redirectUrl={redirectUrl}
				consentNonce={consentNonce}
				clientWebsite={clientWebsite}
			/>
		);

		return;
	}

	const root = createRoot(
		document.getElementById('blockera-site-toolkit-subscription-manager')
	);

	const MappedLicenses = licenses.map((license) => {
		return <LicenseManager {...license} />;
	});

	root.render(MappedLicenses);
});

export * from './components';
