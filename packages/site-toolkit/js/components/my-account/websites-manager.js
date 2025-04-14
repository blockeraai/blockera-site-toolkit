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
import { Flex, Modal, Button, Tooltip } from '@blockera/controls';
import { isLocalhost } from '@blockera/utils';

/**
 * Internal dependencies
 */
import { Table } from './table';
import { HeaderSection } from './header-section';

// const Header = (): MixedElement => (
// 	<>
// 		<strong className="table-title">{__('Website', 'blockera')}</strong>
// 		<strong className="table-title">{__('Action', 'blockera')}</strong>
// 	</>
// );

const Row = ({
	num,
	website,
	websiteId,
	websites,
	remainingDomains,
	setRemainingDomains,
	setWebsites,
	// subscriptionId,
	// onChange = null,
	blockeraaiNonce,
}: {
	num: number,
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
	const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
	// const name = `${subscriptionId}-${website}-domain`;
	const handleDelete = () => {
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

					if ('function' === typeof setWebsites) {
						setWebsites(newWebsites);
					}

					if (
						'function' === typeof setRemainingDomains &&
						remainingDomains
					) {
						setRemainingDomains(remainingDomains + 1);
					}
				}
			})
			.catch((error) => {
				console.log(error);
			});
	};

	return (
		<>
			{isDeleteModalOpen && (
				<Modal
					className="delete-modal"
					size="large"
					headerTitle={__(
						'Are you sure, you want to delete this website?',
						'blockera'
					)}
					onRequestClose={() => setIsDeleteModalOpen(false)}
				>
					<p>
						{__(
							'Removing this domain will deactivate Blockera Pro features on this site. You can reassign it to another site later if needed. Proceed with caution.',
							'blockera'
						)}
					</p>
					<Flex>
						<Button onClick={() => setIsDeleteModalOpen(false)}>
							{__('No', 'blockera')}
						</Button>
						<Button onClick={handleDelete}>
							{__('Yes, Remove', 'blockera')}
						</Button>
					</Flex>
				</Modal>
			)}
			<span className="domain">
				<span className="number">{num}</span>
				{website}
				{isLocalhost(website) && (
					<Tooltip
						placement="top"
						position="top"
						text={__(
							'This domain is recognized as a development domain and is excluded from your subscription’s active domain count.',
							'blockera'
						)}
					>
						<Icon icon="info" library="wp" />
					</Tooltip>
				)}
			</span>
			{'function' === typeof setWebsites && (
				<Button
					className="delete-row"
					variant="secondary"
					size="small"
					icon="trash"
					onClick={() => {
						setIsDeleteModalOpen(true);
					}}
				>
					{__('Delete', 'blockera')}
				</Button>
			)}
		</>
	);
};

/**
 * Websites component.
 *
 * @return {JSX.Element} The rendered component.
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
	// const [{ hasError, errorMessage }, setError] = useState({
	// 	hasError: false,
	// 	errorMessage: '',
	// });
	const activatedCount = Object.values(activeWebsites)?.length || 0;
	const [remainingDomains, setRemainingDomains] = useState(
		0 < maxDomains ? maxDomains - activatedCount : 0
	);
	const [websites, setWebsites] = useState(activeWebsites);
	const { blockeraaiNonce } = window; // blockeraUserAccessToken

	return (
		<Flex gap={20} className="license-card-section" direction="column">
			<HeaderSection
				icon={{
					icon: 'flag',
					library: 'ui',
					iconSize: 24,
				}}
				title={__('Active Websites', 'blockera')}
				description={
					'(' +
					remainingDomains +
					')' +
					__(' Production domain remaning', 'blockera')
				}
			/>

			<Table
				headerBackground="#F7F7F7"
				cols={[
					<strong key="website" className="table-title">
						{__('Website', 'blockera')}
					</strong>,
					<strong key="action" className="table-title">
						{__('Action', 'blockera')}
					</strong>,
				]}
				rows={Object.entries(websites)?.map(
					([websiteId, website]: [string, string], index) => (
						<Row
							key={index}
							num={index + 1}
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
			/>
			<Flex justifyContent="space-between" alignItems="center">
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
