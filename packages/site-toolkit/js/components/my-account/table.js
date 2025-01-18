// @flow

/**
 * External dependencies
 */
import type { MixedElement } from 'react';

/**
 * Blockera dependencies
 */
import { Flex } from '@blockera/controls';
import { classNames } from '@blockera/classnames';

/**
 * Table component.
 *
 * @param {Object} props - The component props.
 * @param {MixedElement} props.children - The component children.
 * @returns {MixedElement} The rendered component.
 */
export const Table = ({
	cols,
	rows,
	headerBackground,
}: {
	rows: Array<MixedElement>,
	cols: Array<MixedElement>,
	headerBackground: string,
}): MixedElement => {
	return (
		<Flex className={classNames('table-container')} direction="column" gap={15}>
			<Flex
				className={classNames('table-column')}
				alignItems="center"
				style={{ backgroundColor: headerBackground }}
			>
				{cols}
			</Flex>
			<Flex className={classNames('table-row')} alignItems="center">
				{rows}
			</Flex>
		</Flex>
	);
};
