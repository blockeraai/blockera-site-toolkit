// @flow

/**
 * Blockera dependencies
 */
import { Icon } from '@blockera/icons';
import { Flex } from '@blockera/controls';

export const HeaderSection = ({
	icon,
	title,
	description,
}: {
	icon: Object,
	title: string,
	description?: string,
}) => {
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
				<p className="license-card-description">{description}</p>
			)}
		</Flex>
	);
};
