// @flow

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { MixedElement } from 'react';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Blockera dependencies
 */
import { Flex, Button } from '@blockera/controls';

/**
 * Internal dependencies
 */
import { Table } from './table';
import { HeaderSection } from './header-section';
import type { DownloadsMap } from './types';

const Row = ({
	num,
	name,
	version,
	generalVersion,
	enabled,
	filename,
	id,
	resource,
	file,
}: {
	id: string,
	num: number,
	name: string,
	version?: string,
	generalVersion: string,
	file: string,
	filename?: string,
	enabled: boolean,
	resource?: 'wp' | 'api',
}): MixedElement => {
	const { blockeraaiNonce } = window;
	const [isBusy, setIsBusy] = useState(false);
	// FIXME: This is a solution of WordPress Button to show the destructive button when the download fails.
	// We need to fix our css styles because isDestructive is not working. @ali
	const [isDestructive, setIsDestructive] = useState(false);

	return (
		<Flex direction="row" gap={10} justifyContent="space-between">
			<span className="name">
				<span className="number">{num}</span>
				{name}
			</span>
			<span className="version">{version || generalVersion}</span>
			<div className="action-buttons">
				{'api' === resource && (
					<Button
						className="license-button-primary"
						variant="primary"
						data-id={id}
						disabled={!enabled}
						isBusy={isBusy}
						isDestructive={isDestructive}
						onClick={() => {
							setIsBusy(true);

							apiFetch({
								method: 'POST',
								path: '/auth/v1/download',
								headers: {
									'X-Blockera-Nonce': blockeraaiNonce,
								},
								data: {
									token: id,
									name: filename,
								},
							})
								.then((response) => {
									if (
										response.success &&
										response.data.temporary_download_url
									) {
										// Create temp link and click it to download
										const a = document.createElement('a');
										a.href =
											response.data.temporary_download_url;
										a.download = name;
										a.style.display = 'none';

										const body = document.body;

										if (body) {
											body.appendChild(a);
											a.click();
											body.removeChild(a);
										} else {
											a.click();
										}
										setIsBusy(false);
									}
								})
								.catch((error) => {
									console.error('Download failed:', error);
									setIsBusy(false);
									setIsDestructive(true);
								});
						}}
						rel="noopener noreferrer"
					>
						{__('Download', 'blockera')}
					</Button>
				)}

				{'wp' === resource && (
					<Button
						className="license-button-primary"
						variant="primary"
						data-id={id}
						href={file}
						disabled={!enabled}
						isBusy={isBusy}
						onClick={() => {
							setIsBusy(true);

							setTimeout(() => {
								setIsBusy(false);
							}, 2000);
						}}
						isDestructive={isDestructive}
						rel="noopener noreferrer"
					>
						{__('Download', 'blockera')}
					</Button>
				)}
			</div>
		</Flex>
	);
};

/**
 * Downloads component.
 *
 * @return {JSX.Element} The rendered component.
 */
export const Downloads = ({
	downloads,
	version,
}: {
	downloads: DownloadsMap,
	version: string,
}): MixedElement => {
	return (
		<Flex gap={20} direction="column" className="license-card-section">
			<HeaderSection
				icon={{
					icon: 'download-box',
					library: 'ui',
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
						generalVersion={version}
						num={index + 1}
					/>
				))}
			/>
		</Flex>
	);
};
