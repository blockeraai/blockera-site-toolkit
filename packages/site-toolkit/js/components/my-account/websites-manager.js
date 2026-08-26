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
import { Flex, Modal, Button } from '@blockera/controls';
import { isUndefined } from '@blockera/utils';

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
	setDevWebsites,
	blockeraaiNonce,
	productColor,
}: {
	num: number,
	website: { mode: 'production' | 'development', website: string },
	websiteId: string,
	remainingDomains?: number,
	setRemainingDomains?: Function,
	onChange?: Function,
	setWebsites?: Function,
	setDevWebsites?: Function,
	subscriptionId: number,
	websites?: { [key: string]: string },
	blockeraaiNonce?: string,
	productColor: string,
}): MixedElement => {
	const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
	const [isDeleting, setIsDeleting] = useState(false);
	const [isDeletingError, setIsDeletingError] = useState(false);

	const handleDelete = () => {
		setIsDeleting(true);

		apiFetch({
			path: 'auth/v1/license/delete',
			method: 'POST',
			headers: {
				'X-Blockera-Nonce': blockeraaiNonce,
			},
			data: {
				domain: website.website,
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

					if (
						'function' === typeof setWebsites &&
						'function' === typeof setDevWebsites
					) {
						if (website.mode === 'production') {
							setWebsites(newWebsites);
						} else {
							setDevWebsites(newWebsites);
						}
					}

					if (
						'function' === typeof setRemainingDomains &&
						!isUndefined(remainingDomains)
					) {
						// $FlowFixMe
						setRemainingDomains(remainingDomains + 1);
					}
				}
			})
			.catch(() => {
				setIsDeleting(false);
				setIsDeletingError(true);
			})
			.finally(() => {
				setIsDeleting(false);
			});
	};

	return (
		<Flex direction="row" gap={0}>
			<span className="domain">
				<span className="number">{num}</span>

				<a
					data-test="website-url"
					href={website.website}
					target="_blank"
					rel="noopener noreferrer"
				>
					{website.website
						.replace('https://', '')
						.replace('http://', '')}
				</a>
			</span>

			<span className="type">
				{'development' === website.mode
					? __('Development', 'blockera')
					: __('Production', 'blockera')}
			</span>

			{'function' === typeof setWebsites && (
				<div className="action-buttons">
					<Button
						data-test="delete-website"
						className="delete-row"
						variant="secondary"
						size="small"
						onClick={() => {
							setIsDeleteModalOpen(true);
						}}
					>
						{__('Deactivate', 'blockera')}
					</Button>
				</div>
			)}

			{isDeleteModalOpen && (
				// $FlowFixMe[prop-missing] focusOnMount is forwarded to WP Modal via ...props
				<Modal
					className="delete-modal"
					size="large"
					headerTitle={__(
						'Are you sure, you want to deactivate this website?',
						'blockera'
					)}
					data-test="modal-body"
					onRequestClose={() => setIsDeleteModalOpen(false)}
					focusOnMount={'firstContentElement'}
					style={{
						'--blockera-controls-primary-color': productColor,
						'--blockera-controls-primary-color-darker-20':
							'color-mix(in srgb, var(--blockera-controls-primary-color) 100%, black 20%)',
					}}
				>
					<Flex
						direction="column"
						gap={40}
						style={{ paddingTop: 40 }}
					>
						<Flex direction="column" gap={20}>
							<p style={{ margin: 0 }}>
								{__(
									'By deactivating this website, you will deactivate Pro features on the site. You can reassign it to another site later if needed. Proceed with caution.',
									'blockera'
								)}
							</p>

							<Flex direction="row" gap={10} alignItems="center">
								{__('Deactivating website:', 'blockera')}
								<a
									style={{
										margin: 0,
										color: 'var(--blockera-controls-primary-color)',
										backgroundColor:
											'color-mix(in srgb, var(--blockera-controls-primary-color) 10%, #ffffff)',
										padding: '2px 8px',
										borderRadius: 2,
										fontSize: 14,
										fontWeight: 500,
										textDecoration: 'none',
									}}
									href={website}
									target="_blank"
									rel="noopener noreferrer"
								>
									{website.website}
								</a>
							</Flex>
						</Flex>

						<Flex alignItems="center" direction="row" gap={20}>
							<Button
								data-test="cancel"
								variant="primary"
								onClick={() => setIsDeleteModalOpen(false)}
							>
								{__('No', 'blockera')}
							</Button>

							<Button
								data-test="confirm"
								className="delete-row"
								variant="secondary"
								onClick={handleDelete}
								isBusy={isDeleting}
							>
								{__('Yes, Deactivate', 'blockera')}
							</Button>

							{isDeleting && (
								<p
									style={{
										margin: 0,
										color: '#d00c0c',
										fontSize: 14,
									}}
								>
									{__(
										'Deactivating website, please wait…',
										'blockera'
									)}
								</p>
							)}

							{isDeletingError && (
								<p
									style={{
										margin: 0,
										color: '#d00c0c',
										fontSize: 14,
									}}
								>
									{__(
										'An error occurred while deactivating the website. Please try again or contact support.',
										'blockera'
									)}
								</p>
							)}
						</Flex>
					</Flex>
				</Modal>
			)}
		</Flex>
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
	productColor,
	developmentWebsites,
}: {
	maxDomains: number,
	subscriptionId: number,
	activeWebsites: {
		[key: string]: { mode: 'production' | 'development', website: string },
	},
	developmentWebsites: {
		[key: string]: { mode: 'production' | 'development', website: string },
	},
	productColor: string,
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
	const [devWebsites, setDevWebsites] = useState(developmentWebsites);
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
						{__('Remaining production websites:', 'blockera')}
						<span>{remainingDomains}</span>
					</>
				}
				descriptionClassName={
					remainingDomains <= 0 ? 'no-remaining-websites' : ''
				}
			/>

			{Object.keys({ ...websites, ...devWebsites }).length > 0 ? (
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
					rows={Object.entries({ ...websites, ...devWebsites })?.map(
						(
							[websiteId, website]: [
								string,
								{
									mode: 'production' | 'development',
									website: string,
								},
							],
							index
						) => (
							<Row
								key={index}
								num={index + 1}
								website={website}
								websites={{ ...websites, ...devWebsites }}
								websiteId={websiteId}
								setWebsites={setWebsites}
								setDevWebsites={setDevWebsites}
								subscriptionId={subscriptionId}
								blockeraaiNonce={blockeraaiNonce}
								remainingDomains={remainingDomains}
								setRemainingDomains={setRemainingDomains}
								productColor={productColor}
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
