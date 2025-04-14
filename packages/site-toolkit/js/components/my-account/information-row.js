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
import { Flex, Grid } from '@blockera/controls';

export const InformationRow = ({
	label,
	value,
	children,
	gap = 10,
	maxDomains,
	justifyContent = 'start',
	className,
	columnsTemplate = '150px 1fr',
}: {
	className?: string,
	gap?: number,
	label?: string,
	value?: string,
	children?: Array<MixedElement> | MixedElement,
	maxDomains?: number,
	justifyContent?:
	| 'start'
	| 'center'
	| 'end'
	| 'space-evenly'
	| 'space-around'
	| 'space-between'
	| 'stretch',
	columnsTemplate?: string,
}): MixedElement => {
	return (
		<Grid
			justifyContent={justifyContent}
			className={'license-information-row details-wrapper'}
			gap={gap}
			gridTemplateColumns={columnsTemplate}
		>
			<p className="field-label">{label}</p>

			<Flex
				alignItems="center"
				justifyContent="space-between"
				className="field-value"
			>
				{'undefined' !== value && (
					<Flex
						alignItems="center"
						justifyContent="flex-start"
						gap={12}
						className={className}
					>
						{value && <span>{value}</span>}

						{maxDomains && (
							<span className="max-websites">
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
					</Flex>
				)}

				{children && (
					<Flex
						alignItems="center"
						justifyContent="flex-end"
						className="actions-wrapper"
					>
						{children}
					</Flex>
				)}
			</Flex>
		</Grid>
	);
};
