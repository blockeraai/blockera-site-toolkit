// @flow

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { MixedElement } from 'react';

/**
 * Blockera dependencies
 */
import { Icon } from '@blockera/icons';
import { Flex } from '@blockera/controls';

/**
 * Internal dependencies
 */
import { Table } from './table';
import { HeaderSection } from './header-section';

const Row = ({
	num,
	name,
	version,
	file,
	enabled,
	id,
}: {
	id: string,
	num: number,
	name: string,
	version: string,
	file: string,
	enabled: boolean,
}): MixedElement => (
	<>
		<span className="name">
			<span className="number">{num}</span>
			{name}
		</span>
		<span className="version">{version}</span>
		<div className="download-button">
			<a
				className="components-button blockera-component blockera-component-button size-small variant-primary content-align-center license-button-primary is-primary has-text has-icon"
				href={enabled ? file : '#'}
				data-id={id}
				rel="noopener noreferrer"
			>
				<Icon icon="download" library="wp" />
				{__('Download', 'blockera')}
			</a>
		</div>
	</>
);

/**
 * Downloads component.
 *
 * @return {JSX.Element} The rendered component.
 */
export const Downloads = ({
	downloads,
	version,
}: {
	downloads: {
		[key: string]: {
			name: string,
			enabled: boolean,
			id: string,
			file: string,
		},
	},
	version: string,
}): MixedElement => {
	return (
		<Flex gap={20} direction="column" className="license-card-section">
			<HeaderSection
				icon={{
					icon: 'download',
					library: 'wp',
					iconSize: 24,
				}}
				title={__('Downloads', 'blockera')}
			/>

			<Table
				headerBackground="#F7F7F7"
				cols={[
					<strong key="name" className="table-title">
						{__('Name', 'blockera')}
					</strong>,
					<strong key="version" className="table-title">
						{__('Version', 'blockera')}
					</strong>,
					<strong key="link" className="table-title">
						{__('Link', 'blockera')}
					</strong>,
				]}
				rows={Object.values(downloads)?.map((download, index) => (
					<Row
						key={index}
						{...download}
						version={version}
						num={index + 1}
					/>
				))}
			/>
		</Flex>
	);
};
