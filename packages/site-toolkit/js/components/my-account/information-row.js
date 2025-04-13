// @flow

/**
 * External dependencies
 */
import { sprintf, _n } from '@wordpress/i18n';
import type { MixedElement } from 'react';
import { classNames } from '@blockera/classnames';

/**
 * Blockera dependencies
 */
import { Flex } from '@blockera/controls';

export const InformationRow = ({
	label,
	value,
	children,
	gap = 40,
	maxDomains,
	justifyContent = 'space-between',
	className,
}: {
	className?: string,
	gap?: number,
	label?: string,
	value?: string,
	children?: Array<MixedElement> | MixedElement,
	maxDomains?: number,
	justifyContent?:
	| 'flex-start'
	| 'flex-end'
	| 'center'
	| 'space-between'
	| 'space-around'
	| 'space-evenly',
}): MixedElement => {
	return (
		<Flex
			justifyContent={justifyContent}
			className={classNames('license-information-row', className)}
			gap={gap}
		>
			<Flex className="details-wrapper">
				{'undefined' !== label && (
					<p className="field-label">{label}</p>
				)}

				{'undefined' !== value && (
					<p className="field-value">
						{value}{' '}
						{maxDomains && (
							<span>
								{sprintf(
									/* translators: %s: number of websites */
									_n(
										'(%s Website)',
										'(%s Websites)',
										maxDomains,
										'blockera'
									),
									maxDomains
								)}
							</span>
						)}
					</p>
				)}
			</Flex>

			<Flex justifyContent="flex-end" className="actions-wrapper">
				{children}
			</Flex>
		</Flex>
	);
};
