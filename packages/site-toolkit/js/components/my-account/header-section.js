// @flow

/**
 * External dependencies
 */
import type { Node } from 'react';

/**
 * Blockera dependencies
 */
import { Icon } from '@blockera/icons';
import { Flex } from '@blockera/controls';
import { classNames } from '@blockera/classnames';

export const HeaderSection = ({
	icon,
	title,
	description,
	descriptionClassName,
}: {
	icon: Object,
	title: string,
	description?: any,
	descriptionClassName?: string,
}): Node => {
	return (
		<Flex justifyContent="space-between" alignItems="center">
			<h3 className="license-card__section-title">
				{icon && (
					<Flex className="license-card__section-title__icon">
						{<Icon {...icon} />}
					</Flex>
				)}

				{title}
			</h3>

			{description && (
				<p
					className={classNames(
						'license-card-description',
						descriptionClassName
					)}
				>
					{description}
				</p>
			)}
		</Flex>
	);
};
