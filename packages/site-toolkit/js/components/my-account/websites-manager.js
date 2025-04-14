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

const Row = ({
	num,
	website,
	websiteId,
	websites,
	remainingDomains,
	setRemainingDomains,
	setWebsites,
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
			</span>

			<span className="type">
				{isLocalhost(website)
					? __('Development', 'blockera')
					: __('Production', 'blockera')}
			</span>

			{'function' === typeof setWebsites && (
				<Button
					className="delete-row"
					variant="secondary"
					size="small"
					onClick={() => {
						setIsDeleteModalOpen(true);
					}}
				>
					{__('Delete Website', 'blockera')}
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
				title={__('Websites', 'blockera')}
				description={
					<>
						{__('Remaining websites:', 'blockera')}
						<span>{remainingDomains}</span>
					</>
				}
				descriptionClassName={
					remainingDomains <= 0 ? 'no-remaining-websites' : ''
				}
			/>

			{Object.keys(activeWebsites).length > 0 ? (
				<Table
					headerBackground="#F7F7F7"
					cols={[
						<strong key="website" className="table-title">
							{__('Website', 'blockera')}
						</strong>,
						<strong key="type" className="table-title">
							{__('Type', 'blockera')}
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
			) : (
				<p className="no-websites-notice">
					{__('You have not activated any websites yet.', 'blockera')}
				</p>
			)}

			{remainingDomains <= 0 && (
				<Flex
					justifyContent="flex-start"
					alignItems="center"
					className="no-remaining-websites-notice"
				>
					<Icon icon="warning" library="ui" iconSize={18} />
					<span>
						{__(
							'Upgrade or purchase another license to activate more websites.',
							'blockera'
						)}
					</span>
				</Flex>
			)}
		</Flex>
	);
};
