// @flow

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { MixedElement } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';

/**
 * Blockera dependencies
 */
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
	gap = 40,
	maxDomains,
	justifyContent = 'space-between',
}: {
	gap?: number,
	label?: string,
	value?: string,
	children?: Array<MixedElement> | MixedElement,
	maxDomains?: number,
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
			className="license-information-row"
			gap={gap}
		>
			<Flex className="details-wrapper">
				{'undefined' !== label && (
					<p className="field-label">{label}</p>
				)}
				{'undefined' !== value && (
					<p className="field-value">
						{value}{' '}
						{maxDomains && (
							<span>{`(${maxDomains} ${__(
								'Websites',
								'blockera'
							)})`}</span>
						)}
					</p>
				)}
			</Flex>
			<Flex justifyContent="flex-end" className="actions-wrapper">
				{children}
			</Flex>
		</Flex>
	);
};

/**
 * license information component.
 *
 * @return {JSX.Element}
 */
export const LicenseInformation = ({
	plan,
	maxDomains,
	// upgradable,
	isAutoRenew: autoRenew,
	startDate,
	expiryDate,
	// renewAmount,
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
	downloads: {
		[key: string]: {
			name: string,
			enabled: boolean,
			id: string,
			file: string,
		},
	},
}): MixedElement => {
	const [isAutoRenew, setIsAutoRenew] = useState(autoRenew);
	const { blockeraaiNonce } = window;

	const showUpgradeButton = true;
	let autoRenewText = __('Next Renew', 'blockera');
	let upgradeButtonText = __('Upgrade', 'blockera');

	if (Object.values(activeWebsites)?.length < maxDomains) {
		if (!isAutoRenew && status === 'active') {
			autoRenewText = __('Expiring on', 'blockera');
		} else if (!isAutoRenew && status === 'expired') {
			autoRenewText = __('Expired on', 'blockera');
			upgradeButtonText = __('Renew & Upgrade', 'blockera');
		}
	}

	const onAutoRenewChange = () => {
		setIsAutoRenew(!isAutoRenew);

		apiFetch({
			path: 'auth/v1/license/renew',
			method: 'POST',
			headers: {
				'X-Blockera-Nonce': blockeraaiNonce,
			},
			data: {
				license_id: subscriptionId,
			},
		}).then((response) => {
			if (response.success) {
				window.location.href = response.data.checkout_url;
			}
		});
	};

	const isLifeTime = -1 !== plan.indexOf('Life');

	return (
		<>
			<Flex
				gap={20}
				direction="column"
				className="license-card-separator"
			>
				<HeaderSection
					icon={{
						icon: 'unlock',
						library: 'wp',
					}}
					title={__('License Information', 'blockera')}
				/>
				<Row
					label={__('Plan', 'blockera')}
					value={`${plan}`}
					maxDomains={maxDomains}
				>
					<Button
						className="license-button-primary"
						variant="primary"
						size="small"
						onClick={() =>
							apiFetch({
								path: 'auth/v1/license/upgrade',
								method: 'POST',
								headers: {
									'X-Blockera-Nonce': blockeraaiNonce,
								},
								data: {
									license_id: subscriptionId,
								},
							}).then((response) => console.log(response))
						}
					>
						{upgradeButtonText}
					</Button>
				</Row>
				{!isLifeTime && (
					<>
						<Row
							label={__('Started Time', 'blockera')}
							value={startDate}
						>
							<div />
						</Row>
						<Row label={autoRenewText} value={expiryDate}>
							<span className="auto-renew-label">
								{__('Auto Renew', 'blockera')}
							</span>
							<ControlContextProvider
								value={{
									name: `toggle${plan.replace(/\s+/g, '')}`,
									value: isAutoRenew,
								}}
							>
								<ToggleControl
									className="license-renew-toggle"
									labelType={'self'}
									id={`toggle${plan.replace(/\s+/g, '')}`}
									defaultValue={isAutoRenew}
									onChange={onAutoRenewChange}
								/>
							</ControlContextProvider>
						</Row>
					</>
				)}
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
