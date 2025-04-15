// @flow

/**
 * External dependencies
 */
import type { MixedElement } from 'react';

/**
 * Internal dependencies
 */
import { Header } from './header';
import { SubscriptionInformation } from './subscription-information';
import { LicenseInformation } from './license-information';

export const LicenseManager = ({
	type,
	productTitle,
	productColor,
	productLogo,
	productVersion,
	subscriptionId,
	updatedOn,
	plan,
	maxDomains,
	startDate,
	expiryDate,
	downloads,
	activeWebsites,
	status,
}: {
	type: 'subscription' | 'non-subscription',
	productColor: string,
	productTitle: string,
	productLogo: string,
	productVersion: string,
	updatedOn: string,
	subscriptionId: number,
	plan: string,
	maxDomains: number,
	startDate: string,
	expiryDate: string,
	downloads: {
		[key: string]: {
			name: string,
			enabled: boolean,
			id: string,
			file: string,
		},
	},
	activeWebsites: { [key: string]: string },
	status: string,
}): MixedElement => {
	return (
		<div
			className="license-manager"
			style={{
				'--blockera-product-color': productColor,
			}}
		>
			<Header
				type={type}
				status={status}
				expiryDate={expiryDate}
				logo={productLogo}
				title={productTitle}
				version={productVersion}
				updatedOn={updatedOn}
			/>

			{type === 'subscription' ? (
				<SubscriptionInformation
					subscriptionId={subscriptionId}
					plan={plan}
					version={productVersion}
					activeWebsites={activeWebsites}
					maxDomains={maxDomains}
					startDate={startDate}
					expiryDate={expiryDate}
					downloads={downloads}
					status={status}
					productColor={productColor}
				/>
			) : (
				<LicenseInformation
					subscriptionId={subscriptionId}
					plan={plan}
					version={productVersion}
					activeWebsites={activeWebsites}
					maxDomains={maxDomains}
					startDate={startDate}
					downloads={downloads}
					productColor={productColor}
				/>
			)}
		</div>
	);
};
