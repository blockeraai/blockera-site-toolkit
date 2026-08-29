/**
 * Internal dependencies
 */
const {
	createPluginCliConfig,
} = require('../../packages/global-packages/packages/dev-tools/bin/plugin/create-config');

const gitRepoOwner = 'blockeraai';

module.exports = createPluginCliConfig({
	slug: 'blockera-site-toolkit',
	name: 'Blockera Site Toolkit',
	team: 'Blockeraai',
	githubRepositoryOwner: gitRepoOwner,
	githubRepositoryName: 'blockera-site-toolkit',
	pluginEntryPoint: 'blockera-site-toolkit.php',
	buildZipCommand: '/bin/bash bin/build-plugin-zip.temp.sh',
	githubRepositoryURL:
		'https://github.com/' + gitRepoOwner + '/blockera-site-toolkit/',
	wpRepositoryReleasesURL:
		'https://github.com/' +
		gitRepoOwner +
		'/blockera-site-toolkit/releases/',
	gitRepositoryURL:
		'https://github.com/' + gitRepoOwner + '/blockera-site-toolkit.git',
	svnRepositoryURL: '',
	changelog: {
		archiveUrl:
			'https://github.com/' +
			gitRepoOwner +
			'/blockera-site-toolkit/releases',
		archiveLabel: 'Blockera Site Toolkit',
		includeCommitCount: true,
	},
});
