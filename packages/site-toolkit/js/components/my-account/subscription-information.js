// @flow

/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import type { MixedElement } from 'react';

/**
 * Blockera dependencies
 */
import { Flex } from '@blockera/controls';
import { Icon } from '@blockera/icons';
/**
 * Internal dependencies
 */
import { Downloads } from './downloads';
import { HeaderSection } from './header-section';
import { WebsitesManager } from './websites-manager';
import { InformationRow } from './information-row';
import type { DownloadsMap } from './types';

/**
 * Subscription information component.
 *
 * @return {JSX.Element} The rendered component.
 */
export const SubscriptionInformation = ({
	status,
	plan,
	maxDomains,
	startDate,
	expiryDate,
	activeWebsites,
	developmentWebsites,
	downloads,
	version,
	subscriptionId,
	productColor,
}: {
	status: string,
	subscriptionId: number,
	plan: string,
	startDate: string,
	expiryDate: string,
	maxDomains: number,
	version: string,
	activeWebsites: {
		[key: string]: { mode: 'production' | 'development', website: string },
	},
	developmentWebsites: {
		[key: string]: { mode: 'production' | 'development', website: string },
	},
	downloads: DownloadsMap,
	productColor: string,
}): MixedElement => {
	const isExpired = status === 'expired';

	const remainingDays = Math.ceil(
		(new Date(expiryDate).getTime() - new Date().getTime()) /
			(1000 * 60 * 60 * 24)
	);

	let statusText = status === 'active' ? __('Active', 'blockera') : status;
	let autoRenewText = __('Next Renew', 'blockera');
	let statusClassname = 'status-active';

	if (status === 'cancelled') {
		statusText = __('Cancelled', 'blockera');
		autoRenewText = '';
		statusClassname = 'status-cancelled';
	} else if (isExpired) {
		statusText = __('Expired', 'blockera');
		autoRenewText = __('Expired on', 'blockera');
		statusClassname = 'status-expired';
	} else if (remainingDays <= 30) {
		statusText = (
			<Flex gap={5} alignItems="center">
				<Icon icon="warning" library="ui" />
				{sprintf(
					/* translators: %s is the number of days remaining */
					__('Active but expires in %s days', 'blockera'),
					remainingDays
				)}
			</Flex>
		);
		autoRenewText = __('Expiring on', 'blockera');
		statusClassname = 'status-expiring';
	}

	return (
		<>
			<Flex gap={20} direction="column" className="license-card-section">
				<HeaderSection
					icon={{
						icon: 'unlock',
						library: 'ui',
						iconSize: 24,
					}}
					title={__('Subscription Information', 'blockera')}
				/>

				<Flex gap={0} direction="column">
					<InformationRow
						label={__('Plan', 'blockera')}
						value={plan}
						maxDomains={maxDomains}
					/>

					<InformationRow
						label={__('Started Date', 'blockera')}
						value={startDate}
					/>

					{autoRenewText && (
						<InformationRow
							label={autoRenewText}
							value={expiryDate}
						/>
					)}

					<InformationRow
						label={__('Status', 'blockera')}
						value={statusText}
						className={statusClassname}
					/>
				</Flex>
			</Flex>

			<Downloads downloads={downloads} version={version} />

			<WebsitesManager
				developmentWebsites={developmentWebsites}
				subscriptionId={subscriptionId}
				activeWebsites={activeWebsites}
				maxDomains={maxDomains}
				productColor={productColor}
			/>
		</>
	);
};
