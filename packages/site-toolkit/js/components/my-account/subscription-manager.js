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

/**
 * Card component.
 *
 * @returns {JSX.Element}
 */
export const SubscriptionManager = ({
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
	downloads: Array<{ name: string, version: string, link: string }>,
	activeWebsites: { [key: string]: string },
	status: string,
}): MixedElement => {
	return (
		<div
			className="subscription-manager"
			style={{
				'--blockera-product-color': productColor,
				'--blockera-subscription-card-border-color': '#CFE0FF',
				'--blockera-subscription-information-row-border-color': '#F7F7F7',
			}}
		>
			<Header
				status={status}
				expiryDate={expiryDate}
				logo={productLogo}
				title={productTitle}
				version={productVersion}
				updatedOn={updatedOn}
			/>

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
			/>
		</div>
	);
};
