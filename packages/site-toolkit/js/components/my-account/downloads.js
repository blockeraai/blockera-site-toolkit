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
import { HeaderSection } from './header-section';

const Header = (): MixedElement => (
	<>
		<span>{__('Name', 'blockera')}</span>
		<span>{__('Version', 'blockera')}</span>
		<span>{__('Link', 'blockera')}</span>
	</>
);

const Row = ({
	name,
	version,
	file,
	enabled,
	id,
}: {
	id: string,
	name: string,
	version: string,
	file: string,
	enabled: boolean,
}): MixedElement => (
	<>
		<span>{name}</span>
		<span>{version}</span>
		<a
			className="components-button blockera-component blockera-component-button size-small variant-primary content-align-center subscription-button-primary is-primary has-text has-icon"
			href={enabled ? file : '#'}
			data-id={id}
			rel="noopener noreferrer"
		>
			<Icon icon="download" />
			{__('Download', 'blockera')}
		</a>
	</>
);

/**
 * Downloads component.
 *
 * @returns {JSX.Element}
 */
export const Downloads = ({
	downloads,
	version,
}: {
	downloads: { [key: string]: {
		name: string,
		link: string,
	} },
	version: string,
}): MixedElement => {
	return (
		<Flex
			gap={20}
			direction="column"
			className="subscription-card-separator"
		>
			<HeaderSection
				icon={{
					icon: 'download',
					library: 'wp',
				}}
				title={__('Downloads', 'blockera')}
			/>
			<Flex
				style={{ backgroundColor: '#F7F7F7' }}
				justifyContent="space-between"
			>
				<Header />
			</Flex>
			<Flex justifyContent="space-between">
				{Object.values(downloads)?.map((download) => (
					<Row {...download} version={version} />
				))}
			</Flex>
		</Flex>
	);
};
