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
	icon: {
		icon: string,
		library?: string,
	},
	title: string,
	description?: string,
}) => {
	return (
		<Flex justifyContent="space-between" alignItems="center">
			<h3 className="license-card-title">
				<Icon {...icon} />
				{title}
			</h3>
			{description && (
				<p className="license-card-description">{description}</p>
			)}
		</Flex>
	);
};
