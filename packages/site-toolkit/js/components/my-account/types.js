// @flow

export type DownloadItem = {
	name: string,
	enabled: boolean,
	id: string,
	file: string,
	filename?: string,
	version?: string,
	resource?: 'wp' | 'api',
};

export type DownloadsMap = {
	[key: string]: DownloadItem,
};
