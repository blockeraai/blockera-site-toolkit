// @flow

/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import type { MixedElement } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { useState, useCallback } from '@wordpress/element';

/**
 * Blockera dependencies
 */
import {
	Flex,
	Button,
	ToggleControl,
	ControlContextProvider,
} from '@blockera/controls';
import { Icon } from '@blockera/icons';

/**
 * Internal dependencies
 */
import { Image } from '../image';

const AttachIcon = ({ fill, style }: { fill: string, style: Object }) => (
	<svg
		style={style}
		width="12"
		height="13"
		viewBox="0 0 12 13"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M10.1148 2.3852C9.37056 1.64096 7.94681 1.64447 6.92556 2.66573L6.08256 3.50872C5.81754 3.77374 5.38786 3.77374 5.12284 3.50872C4.85782 3.2437 4.85782 2.81402 5.12284 2.54899L5.96583 1.706C7.39092 0.280909 9.68229 0.0332314 11.0745 1.42547C12.4668 2.81771 12.2191 5.10908 10.794 6.53417L9.951 7.37716C9.68598 7.64218 9.2563 7.64218 8.99128 7.37716C8.72626 7.11214 8.72626 6.68246 8.99128 6.41743L9.83427 5.57444C10.8555 4.55319 10.859 3.12944 10.1148 2.3852ZM8.00566 4.49452C8.27068 4.75954 8.27068 5.18922 8.00566 5.45424L4.9541 8.5058C4.68908 8.77082 4.25939 8.77082 3.99437 8.5058C3.72935 8.24078 3.72935 7.8111 3.99437 7.54607L7.04593 4.49452C7.31095 4.2295 7.74064 4.2295 8.00566 4.49452ZM3.00872 5.62319C3.27374 5.88821 3.27374 6.31789 3.00872 6.58291L2.16573 7.4259C1.14447 8.44716 1.14096 9.87091 1.8852 10.6151C2.62944 11.3594 4.05319 11.3559 5.07444 10.3346L5.91744 9.49163C6.18246 9.22661 6.61214 9.22661 6.87716 9.49163C7.14218 9.75665 7.14218 10.1863 6.87716 10.4514L6.03417 11.2943C4.60908 12.7194 2.31771 12.9671 0.925471 11.5749C-0.46677 10.1826 -0.219089 7.89127 1.206 6.46618L2.049 5.62319C2.31402 5.35817 2.7437 5.35817 3.00872 5.62319Z"
			fill={fill}
		/>
	</svg>
);

type LicenseOnChangeHandler = (params: {
	type: 'subscription' | 'no-subscription',
	orderId: number,
	orderItemId: number,
	licenseId: number,
	productId: number,
	variationId: number,
	subscriptionId: number,
}) => void;

type LicenseProps = {
	type: 'subscription' | 'no-subscription',
	orderId: number,
	orderItemId: number,
	productId: number,
	variationId: number,
	subscriptionId: number,
	productLogo: string,
	plan: string,
	_isActive: boolean,
	licenseId: number,
	productColor: string,
	productTitle: string,
	status: string,
	maxDomains: number,
	expiryDate: string,
	activeWebsites: Array<string>,
	onChange: LicenseOnChangeHandler,
};

const License = ({
	type,
	plan,
	orderId,
	_isActive,
	productId,
	licenseId,
	maxDomains,
	variationId,
	orderItemId,
	productLogo,
	productTitle,
	// expiryDate,
	subscriptionId,
	activeWebsites,
	// productColor,
	// status,
	onChange,
}: LicenseProps): MixedElement => {
	const remainingDomains = maxDomains - activeWebsites.length;

	return (
		<div className="license-box-wrapper">
			<Flex
				className="license"
				alignItems="center"
				justifyContent="space-between"
			>
				<Flex alignItems="center" gap={17}>
					<Image
						src={productLogo}
						alt={productTitle}
						className="product-logo"
					/>

					<Flex direction="column" gap={12}>
						<h3 className="product-title">{productTitle}</h3>

						<Flex gap={15}>
							<p className="product-details">
								{__('License:', 'blockera')} {plan}
							</p>

							<p className="product-details">
								{remainingDomains > 0 &&
									sprintf(
										// translators: %s is the number of websites remaining.
										__('%s Websites Remaining', 'blockera'),
										remainingDomains
									)}

								{!remainingDomains &&
									__('No Websites Remaining', 'blockera')}
							</p>
						</Flex>
					</Flex>
				</Flex>

				<ControlContextProvider
					value={{
						name: `toggle${plan.replace(/\s+/g, '')}`,
						value: licenseId === _isActive,
					}}
				>
					<ToggleControl
						labelType={'self'}
						id={`toggle${plan.replace(/\s+/g, '')}`}
						defaultValue={licenseId === _isActive}
						onChange={() =>
							onChange({
								type,
								orderId,
								licenseId,
								productId,
								variationId,
								orderItemId,
								subscriptionId,
							})
						}
					/>
				</ControlContextProvider>
			</Flex>
		</div>
	);
};

export const ConsentForm = ({
	shopUrl,
	clientId,
	licenses,
	clientUrl,
	redirectUrl,
	consentNonce,
	clientWebsite,
}: {
	shopUrl: string,
	clientId: string,
	clientUrl: string,
	redirectUrl: string,
	consentNonce: string,
	clientWebsite: string,
	licenses: Array<LicenseProps>,
}): MixedElement => {
	const [pickedLicense, setPickedLicense] = useState(
		1 === licenses.length ? licenses[0] : null
	);
	const [connectionState, setConnectionState] = useState({
		isConnected: false,
		isConnecting: false,
	});

	const handleConnect = useCallback(() => {
		setConnectionState({
			...connectionState,
			isConnecting: true,
		});
		apiFetch({
			path: '/auth/v1/licenses/create',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Blockera-Nonce': consentNonce,
			},
			data: {
				domain: clientUrl,
				client_id: clientId,
				type: pickedLicense.type,
				order_id: pickedLicense.orderId,
				product_id: pickedLicense.productId,
				license_id: pickedLicense.licenseId,
				variation_id: pickedLicense.variationId,
				order_item_id: pickedLicense.orderItemId,
				subscription_id: pickedLicense.subscriptionId,
			},
		}).then((response) => {
			if (response?.success) {
				setConnectionState({
					isConnected: true,
					isConnecting: false,
				});
				window.location.href =
					redirectUrl + '&connectedWithYourAccount=true';
			}
		});
	}, [
		clientId,
		clientUrl,
		redirectUrl,
		consentNonce,
		pickedLicense,
		connectionState,
	]);

	const urlObject = new URL(clientUrl);

	return (
		<Flex
			direction="column"
			alignItems="stretch"
			className="consent-form"
			gap={15}
		>
			{licenses.length > 0 && (
				<>
					<Flex
						direction="column"
						alignItems="center"
						gap={15}
						style={{ marginBottom: '15px' }}
					>
						<h1>{__('Let’s connect your site', 'blockera')}</h1>

						<p className="consent-form-description">
							{__(
								'Once that’s done, you’ll be able to access your site from the My Blockera dashboard.',

								'blockera'
							)}
						</p>
					</Flex>

					<Flex direction="column" alignItems="center">
						<span className="domain">
							<span>{`${urlObject.protocol}//`}</span>
							{urlObject.hostname}
						</span>
					</Flex>

					<Flex
						direction="column"
						alignItems="stretch"
						className="consent-form-inner"
						gap={20}
					>
						<div className="dashed-line" />

						<Flex
							className="link-icon-wrapper"
							direction="column"
							alignItems="center"
							gap={4}
						>
							<Icon library="ui" icon="link" iconSize={20} />
						</Flex>

						{licenses?.map((license: LicenseProps) => (
							<License
								key={license.productTitle}
								{...license}
								onChange={setPickedLicense}
								_isActive={pickedLicense?.licenseId}
							/>
						))}
					</Flex>

					<Button
						className="connect-button"
						variant="primary"
						isBusy={
							connectionState.isConnecting &&
							!connectionState.isConnected
						}
						onClick={handleConnect}
						disabled={!pickedLicense}
					>
						<Icon library="ui" icon="unlock" iconSize={24} />

						{__('Connect & Activate License', 'blockera')}
					</Button>
				</>
			)}

			{licenses.length === 0 && (
				<>
					<h1>
						{__('❌ No Licenses found for ', 'blockera')}
						<strong>{window.blockeraProductId || 'EMPTY'}</strong>
						{__(' product', 'blockera')}
					</h1>
					<p className="consent-form-description">
						{__(
							'Please check your licenses and try again.',
							'blockera'
						)}
					</p>
					<Button
						className="connect-button"
						variant="primary"
						onClick={() => {
							window.location.href = shopUrl;
						}}
					>
						{__('Go to Shop', 'blockera')}
					</Button>
				</>
			)}
		</Flex>
	);
};
