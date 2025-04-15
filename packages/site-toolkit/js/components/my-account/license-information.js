// @flow

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { MixedElement } from 'react';

/**
 * Blockera dependencies
 */
import { Flex } from '@blockera/controls';

/**
 * Internal dependencies
 */
import { Downloads } from './downloads';
import { HeaderSection } from './header-section';
import { WebsitesManager } from './websites-manager';
import { InformationRow } from './information-row';

/**
 * Subscription information component.
 *
 * @return {JSX.Element} The rendered component.
 */
export const LicenseInformation = ({
	plan,
	version,
	downloads,
	startDate,
	maxDomains,
	subscriptionId,
	activeWebsites,
	productColor,
}: {
	plan: string,
	version: string,
	downloads: {
		[key: string]: {
			name: string,
			enabled: boolean,
			id: string,
			file: string,
		},
	},
	startDate: string,
	maxDomains: number,
	subscriptionId: number,
	activeWebsites: { [key: string]: string },
	productColor: string,
}): MixedElement => {
	return (
		<>
			<Flex gap={20} direction="column" className="license-card-section">
				<HeaderSection
					icon={{
						icon: 'unlock',
						library: 'ui',
						iconSize: 24,
					}}
					title={__('License Information', 'blockera')}
				/>

				<Flex gap={0} direction="column">
					<InformationRow
						label={__('Plan', 'blockera')}
						value={plan}
						maxDomains={maxDomains}
					/>

					<InformationRow
						label={__('Purchase Date', 'blockera')}
						value={startDate}
					/>
				</Flex>
			</Flex>

			<Downloads downloads={downloads} version={version} />

			<WebsitesManager
				subscriptionId={subscriptionId}
				activeWebsites={activeWebsites}
				maxDomains={maxDomains}
				productColor={productColor}
			/>
		</>
	);
};
