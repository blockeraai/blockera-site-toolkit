// @flow

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { MixedElement } from 'react';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Blockera dependencies
 */
import { Icon } from '@blockera/icons';
import {
	Flex,
	Button,
	ControlContextProvider,
	InputControl,
} from '@blockera/controls';
import { isLocalhost } from '@blockera/utils';

/**
 * Internal dependencies
 */
import { HeaderSection } from './header-section';

const Header = (): MixedElement => (
	<>
		<span>{__('Website', 'blockera')}</span>
		<span>{__('Action', 'blockera')}</span>
	</>
);

const Row = ({
	website,
	websiteId,
	websites,
	remainingDomains,
	setRemainingDomains,
	setWebsites,
	subscriptionId,
	onChange = null,
	blockeraaiNonce,
}: {
	website: string,
	websiteId: string,
	remainingDomains?: number,
	setRemainingDomains?: Function,
	onChange?: Function,
	setWebsites?: Function,
	subscriptionId: number,
	websites?: { [key: string]: string },
	blockeraaiNonce?: string,
}): MixedElement => {
	const name = `${subscriptionId}-${website}-domain`;

	return (
		<Flex
			alignItems="center"
			justifyContent="space-between"
			className="subscription-websites"
		>
			<ControlContextProvider
				value={{
					name,
					value: website,
				}}
			>
				<InputControl
					id={name}
					type="text"
					defaultValue={website}
					{...{
						...(onChange ? { onChange } : {}),
					}}
				/>
			</ControlContextProvider>
			{'function' === typeof setWebsites && (
				<Button
					variant="secondary"
					size="small"
					icon="trash"
					onClick={() => {
						apiFetch({
							path: 'auth/v1/license/delete',
							method: 'POST',
							headers: {
								'X-Blockera-Nonce': blockeraaiNonce,
							},
							data: {
								domain: website,
								domain_id: websiteId,
							},
						})
							.then((response) => {
								if (!response?.success) {
									return;
								}

								if ('object' === typeof websites) {
									const newWebsites = Object.fromEntries(
										Object.entries(websites).filter(
											([key]) => key !== websiteId
										)
									);
									setWebsites(newWebsites);
									setRemainingDomains(remainingDomains + 1);
								}
							})
							.catch((error) => {
								console.log(error);
							});
					}}
				>
					{__('Delete', 'blockera')}
				</Button>
			)}
		</Flex>
	);
};

/**
 * Websites component.
 *
 * @returns {JSX.Element}
 */
export const WebsitesManager = ({
	maxDomains,
	subscriptionId,
	activeWebsites,
}: {
	maxDomains: number,
	subscriptionId: number,
	activeWebsites: { [key: string]: string },
}): MixedElement => {
	const [{ hasError, errorMessage }, setError] = useState({
		hasError: false,
		errorMessage: '',
	});
	const activatedCount = Object.values(activeWebsites)?.length || 0;
	const [remainingDomains, setRemainingDomains] = useState(
		0 < maxDomains ? maxDomains - activatedCount : 0
	);
	const [newDomain, setNewDomain] = useState('');
	const [websites, setWebsites] = useState(activeWebsites);
	const { blockeraaiNonce, blockeraUserAccessToken } = window;
	const [isShowingWebsiteInputBox, setIsShowingWebsiteInputBox] =
		useState(false);

	return (
		<Flex
			gap={20}
			className="subscription-card-separator"
			direction="column"
		>
			<HeaderSection
				icon={{
					icon: 'settings',
					library: 'wp',
				}}
				title={__('Active Websites', 'blockera')}
				description={
					remainingDomains +
					__(' Production domain remaning', 'blockera')
				}
			/>
			<Flex
				style={{ backgroundColor: '#F7F7F7' }}
				justifyContent="space-between"
			>
				<Header />
			</Flex>
			{Object.entries(websites)?.map(
				([websiteId, website]: [string, string]) => (
					<Row
						website={website}
						websites={websites}
						websiteId={websiteId}
						setWebsites={setWebsites}
						subscriptionId={subscriptionId}
						blockeraaiNonce={blockeraaiNonce}
						remainingDomains={remainingDomains}
						setRemainingDomains={setRemainingDomains}
					/>
				)
			)}
			<Flex justifyContent="space-between" alignItems="center">
				<Button
					className="subscription-button-primary"
					disabled={remainingDomains <= 0}
					variant="primary"
					size="small"
					icon="plus"
					onClick={() => {
						setIsShowingWebsiteInputBox(true);
					}}
				>
					{__('Add Website', 'blockera')}
				</Button>
				{isShowingWebsiteInputBox && (
					<>
						<Row
							websiteId={0}
							website={newDomain}
							subscriptionId={subscriptionId}
							onChange={(domain) => {
								setNewDomain(domain);
							}}
						/>
						{hasError && (
							<span style={{ color: 'red', fontSize: '12px' }}>
								{errorMessage}
							</span>
						)}
						<Button
							variant="primary"
							size="small"
							onClick={() => {
								apiFetch({
									path: 'auth/v1/licenses/create',
									method: 'POST',
									headers: {
										'X-Blockera-Nonce': blockeraaiNonce,
									},
									data: {
										domain: newDomain,
										subscription_id: subscriptionId,
									},
								})
									.then((response) => {
										if (response?.success) {
											setIsShowingWebsiteInputBox(false);
											setWebsites({
												...websites,
												[response?.data?.id]:
													response?.data?.domain,
											});
											setNewDomain('');

											if (
												remainingDomains > 0 &&
												!isLocalhost(
													response?.data?.domain
												)
											) {
												setRemainingDomains(
													remainingDomains - 1
												);
											}

											setError({
												hasError: false,
												errorMessage: '',
											});
										}
									})
									.catch((error) => {
										setError({
											hasError: true,
											errorMessage: Object.values(
												error?.errors
											).join(', '),
										});
									});
							}}
						>
							{__('Add', 'blockera')}
						</Button>
					</>
				)}
				{remainingDomains <= 0 && (
					<span>
						{__(
							'Upgrade your subscription for more sites activation',
							'blockera'
						)}
					</span>
				)}
			</Flex>
		</Flex>
	);
};
