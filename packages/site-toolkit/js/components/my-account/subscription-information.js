// @flow

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { MixedElement } from 'react';
import { useState, useCallback } from '@wordpress/element';

/**
 * Blockera dependencies
 */
import { Icon } from '@blockera/icons';
import {
	Flex,
	Button,
	ControlContextProvider,
	ToggleControl,
} from '@blockera/controls';

/**
 * Internal dependencies
 */
import { Downloads } from './downloads';
import { HeaderSection } from './header-section';
import { WebsitesManager } from './websites-manager';
const Row = ({
	label,
	value,
	children,
	gap = '20',
	showUpgradeButton = false,
	justifyContent = 'space-between',
	upgradeButtonText = __('Upgrade', 'blockera'),
}: {
	gap?: string,
	label: string,
	value: string,
	children?: MixedElement,
	showUpgradeButton?: boolean,
	upgradeButtonText?: string,
	justifyContent?:
		| 'flex-start'
		| 'flex-end'
		| 'center'
		| 'space-between'
		| 'space-around'
		| 'space-evenly',
}): MixedElement => {
	return (
		<Flex
			justifyContent={justifyContent}
			className="subscription-information-row"
			gap={gap}
		>
			<p className="field-label">{label}</p>
			<p>{value}</p>
			{showUpgradeButton && (
				<Button
					className="subscription-button-primary"
					variant="primary"
					size="small"
				>
					{upgradeButtonText}
				</Button>
			)}
			{children}
		</Flex>
	);
};

/**
 * Subscription information component.
 *
 * @returns {JSX.Element}
 */
export const SubscriptionInformation = ({
	plan,
	maxDomains,
	upgradable,
	isAutoRenew: autoRenew,
	startDate,
	expiryDate,
	renewAmount,
	activeWebsites,
	downloads,
	version,
	subscriptionId,
}: {
	subscriptionId: number,
	plan: string,
	upgradable: string,
	isAutoRenew: boolean,
	startDate: string,
	expiryDate: string,
	renewAmount: string,
	maxDomains: number,
	version: string,
	activeWebsites: { [key: string]: string },
	downloads: Array<{ name: string, version: string, link: string }>,
}): MixedElement => {
	const [isAutoRenew, setIsAutoRenew] = useState(autoRenew);

	let showUpgradeButton = true;
	let autoRenewText = __('Next Renew', 'blockera');
	let upgradeButtonText = __('Upgrade', 'blockera');

	if (!isAutoRenew && status === 'active') {
		autoRenewText = __('Expiring on', 'blockera');
	} else if (!isAutoRenew && status === 'expired') {
		autoRenewText = __('Expired on', 'blockera');
		upgradeButtonText = __('Renew & Upgrade', 'blockera');
	} else if (Object.values(activeWebsites)?.length >= maxDomains) {
		showUpgradeButton = true;
		upgradeButtonText = __('Upgrade', 'blockera');
	}

	const onAutoRenewChange = useCallback(() => {
		setIsAutoRenew(!isAutoRenew);
	}, []);

	return (
		<>
			<Flex
				gap={20}
				direction="column"
				className="subscription-card-separator"
			>
				<HeaderSection
					icon={{
						icon: 'unlock',
					}}
					title={__('Subscription Information', 'blockera')}
				/>
				<Flex direction="column" justifyContent="space-between">
					<Row
						label={__('Plan', 'blockera')}
						value={`${plan} (${maxDomains}) ${__(
							'Websites',
							'blockera'
						)}`}
						showUpgradeButton={showUpgradeButton}
						upgradeButtonText={upgradeButtonText}
					/>
				</Flex>

				<Row
					justifyContent="flex-start"
					label={__('Started Time', 'blockera')}
					value={startDate}
				/>

				<Flex direction="column" justifyContent="space-between">
					<Row label={autoRenewText} value={expiryDate}>
						<ControlContextProvider
							value={{
								name: `toggle${plan.replace(/\s+/g, '')}`,
								value: isAutoRenew,
							}}
						>
							<ToggleControl
								labelType={'self'}
								label={__('Auto Renew', 'blockera')}
								id={`toggle${plan.replace(/\s+/g, '')}`}
								defaultValue={isAutoRenew}
								onChange={onAutoRenewChange}
							/>
						</ControlContextProvider>
					</Row>
				</Flex>
			</Flex>

			<Downloads downloads={downloads} version={version} />

			<WebsitesManager
				subscriptionId={subscriptionId}
				activeWebsites={activeWebsites}
				maxDomains={maxDomains}
			/>
		</>
	);
};
