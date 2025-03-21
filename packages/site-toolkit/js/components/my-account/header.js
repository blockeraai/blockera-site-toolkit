// @flow

/**
 * External dependencies
 */
import type { MixedElement } from 'react';

/**
 * Internal dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@blockera/controls';
import { classNames } from '@blockera/classnames';

/**
 * Internal dependencies
 */
import { Image } from '../image';

export const Header = ({
	logo,
	title,
	status,
	version,
	updatedOn,
	expiryDate,
}: {
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
			className="license-card-separator product-header"
			alignItems="flex-start"
			gap={20}
		>
			<Image
				src={logo}
				alt={title}
				className={{
					'division-68': true,
					'product-logo': true,
				}}
			/>
			<Flex direction="column" gap={25}>
				<h3 className="product-title">{title}</h3>
				<Flex gap={40}>
					<p
						className={classNames('product-version', {
							'product-details': true,
						})}
					>
						{version}
					</p>
					<p
						className={classNames('product-updated-on', {
							'product-details': true,
						})}
					>
						{__('Updated on: ', 'blockera') + updatedOn}
					</p>
				</Flex>
			</Flex>
			{isExpired && (
				<span
					className={classNames('product-status', {
						expired: true,
					})}
				>
					{__('Expired', 'blockera')}
				</span>
			)}
			{!isExpired && remainingDays <= 7 && (
				<span
					className={classNames('product-status', {
						expired: true,
					})}
				>
					{__('Expiring Soon', 'blockera')}
				</span>
			)}
		</Flex>
	);
};
