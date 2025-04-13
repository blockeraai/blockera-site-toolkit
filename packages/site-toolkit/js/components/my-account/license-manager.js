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
	upgradable,
	isAutoRenew,
	startDate,
	expiryDate,
	renewAmount,
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
	upgradable: string,
	isAutoRenew: boolean,
	startDate: string,
	expiryDate: string,
	renewAmount: string,
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
				'--blockera-license-card-border-color': '#CFE0FF',
				'--blockera-license-information-row-border-color': '#F7F7F7',
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
					upgradable={upgradable}
					isAutoRenew={isAutoRenew}
					startDate={startDate}
					expiryDate={expiryDate}
					renewAmount={renewAmount}
					downloads={downloads}
					status={status}
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
				/>
			)}
		</div>
	);
};
