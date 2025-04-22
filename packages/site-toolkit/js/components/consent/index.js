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
	DynamicHtmlFormatter,
} from '@blockera/controls';
import { Icon } from '@blockera/icons';

/**
 * Internal dependencies
 */
import { Image } from '../image';

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
				justifyContent="flex-start"
				gap={17}
			>
				<Image
					src={productLogo}
					alt={productTitle}
					className="product-logo"
				/>

				<Flex direction="column" gap={12} grow={1}>
					<h3 className="product-title">{productTitle}</h3>

					<Flex gap={15} alignItems="center">
						<p className="product-details">{plan}</p>

						{remainingDomains > 0 && (
							<p className="product-details">
								{sprintf(
									// translators: %s is the number of websites remaining.
									__('%s Websites Remaining', 'blockera'),
									remainingDomains
								)}
							</p>
						)}
					</Flex>
				</Flex>

				{remainingDomains === 0 && (
					<div className="product-status status-max-domains">
						<Icon icon={'warning'} />
						{__('Max Sites Activation Reached', 'blockera')}
					</div>
				)}

				{remainingDomains > 0 && (
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
				)}
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
			<Flex
				direction="column"
				alignItems="stretch"
				gap={15}
				className="consent-form-inner-wrapper"
				justifyContent="center"
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
							<strong>
								{window.blockeraProductId || 'EMPTY'}
							</strong>
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

			{licenses.length > 0 && (
				<p className="consent-text">
					<DynamicHtmlFormatter
						text={sprintf(
							/* translators: %1$s is a link to the terms of service, %2$s is a link to the privacy policy. */
							__(
								'By connecting, you agree to our %1$s, %2$s and %3$s.',
								'blockera'
							),
							'{terms-of-service}',
							'{opt-in-usage}',
							'{privacy-policy}'
						)}
						replacements={{
							'terms-of-service': (
								<a
									href="https://blockera.ai/terms-and-conditions-of-use/"
									target="_blank"
									rel="noopener noreferrer"
								>
									{
										/* translators: This is a link to the terms of service. */
										__('Terms of Service', 'blockera')
									}
								</a>
							),
							'opt-in-usage': (
								<a
									href="https://blockera.ai/telemetry/"
									target="_blank"
									rel="noopener noreferrer"
								>
									{
										/* translators: This is a link to the opt-in usage. */
										__('Opt-in Usage', 'blockera')
									}
								</a>
							),
							'privacy-policy': (
								<a href="https://blockera.ai/privacy-policy/">
									{
										/* translators: This is a link to the privacy policy. */
										__('Privacy Policy', 'blockera')
									}
								</a>
							),
						}}
					/>
				</p>
			)}
		</Flex>
	);
};
