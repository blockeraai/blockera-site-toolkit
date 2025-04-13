// @flow

/**
 * External dependencies
 */
import type { MixedElement } from 'react';

/**
 * Internal dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Flex } from '@blockera/controls';
import { classNames } from '@blockera/classnames';
import { Icon } from '@blockera/icons';

/**
 * Internal dependencies
 */
import { Image } from '../image';

export const Header = ({
	type,
	logo,
	title,
	status,
	version,
	updatedOn,
	expiryDate,
}: {
	type: string,
	logo: string,
	title: string,
	status: string,
	version: string,
	updatedOn: string,
	expiryDate: string,
}): MixedElement => {
	const isExpired = status === 'expired';

	const remainingDays = Math.ceil(
		(new Date(expiryDate).getTime() - new Date().getTime()) /
		(1000 * 60 * 60 * 24)
	);

	return (
		<Flex
			className="license-card-section product-header"
			alignItems="center"
			gap={'var(--inner-space)'}
		>
			<Flex
				className="product-logo-container"
				alignItems="center"
				justifyContent="center"
			>
				<Icon icon="blockera" library="blockera" iconSize={24} />

				{logo && (
					<Image src={logo} alt={title} className={'product-logo'} />
				)}
			</Flex>

			<Flex direction="column" gap={'calc(var(--inner-space) * 0.6)'}>
				<h3 className="product-title">{title}</h3>

				<Flex
					gap={'calc(var(--inner-space) * 0.8)'}
					alignItems="center"
				>
					<p
						className={classNames('product-version', {
							'product-details': true,
						})}
					>
						{version}
					</p>

					<span className="separator-dot" />

					<p
						className={classNames('product-updated-on', {
							'product-details': true,
						})}
					>
						{__('Updated on:', 'blockera') + ' ' + updatedOn}
					</p>
				</Flex>
			</Flex>

			<Flex
				className="product-status-container"
				gap={'calc(var(--inner-space) * 0.8)'}
				alignItems="center"
				justifyContent="flex-end"
			>
				{type === 'non-subscription' && (
					<span
						className={classNames('product-status', {
							'lifetime-plan': true,
						})}
					>
						<Icon icon="star-filled" library="wp" iconSize={20} />
						{__('Lifetime', 'blockera')}
					</span>
				)}

				{isExpired && (
					<span
						className={classNames('product-status', {
							expired: true,
						})}
					>
						{__('Expired', 'blockera')}
					</span>
				)}

				{!isExpired && remainingDays <= 30 && (
					<span
						className={classNames('product-status', {
							expired: true,
						})}
					>
						{sprintf(
							/* translators: %s is the number of days remaining */
							__('Expires in %s days', 'blockera'),
							remainingDays
						)}
					</span>
				)}
			</Flex>
		</Flex>
	);
};
