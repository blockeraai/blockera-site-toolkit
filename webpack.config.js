/**
 * External dependencies
 */
const fs = require('fs');
const path = require('path');
const {
	camelCaseDash,
} = require('@wordpress/dependency-extraction-webpack-plugin/lib/util');

/**
 * Internal dependencies
 */
const { dependencies } = require('./package');
const packagesConfig = require('./packages/global-packages/packages/dev-tools/js/webpack/packages');

const exportDefaultPackages = [];

// Resolve package dir: vendor symlink → local packages/ → global-packages submodule.
function resolveBlockeraPackageDir(packageName) {
	const candidates = [
		`./vendor/blockera/${packageName}`,
		`./packages/${packageName}`,
		`./packages/global-packages/packages/${packageName}`,
	];

	for (const candidate of candidates) {
		if (
			fs.existsSync(
				path.resolve(process.cwd(), candidate, 'package.json')
			)
		) {
			return candidate;
		}
	}

	throw new Error(
		`Cannot find Blockera package "${packageName}" under vendor/blockera, packages/, or packages/global-packages/packages/`
	);
}

module.exports = (env, argv) => {
	if (!argv) {
		return require(path.resolve(
			process.cwd(),
			'packages/global-packages/packages/dev-cypress/js/webpack.config.js'
		));
	}

	const BLOCKERA_NAMESPACE = '@blockera/';
	const blockeraPackages = Object.keys(dependencies)
		.filter((packageName) => packageName.startsWith(BLOCKERA_NAMESPACE))
		.map((packageName) => packageName.replace(BLOCKERA_NAMESPACE, ''));
	const blockeraPackagesVersion = Object.fromEntries(
		blockeraPackages.map((packageName) => {
			const packageDir = resolveBlockeraPackageDir(packageName);
			const { version } = require(`${packageDir}/package.json`);

			return [packageName, version.replace(/\./g, '_')];
		})
	);
	const blockeraEntries = blockeraPackages.reduce((memo, packageName) => {
		// Exclude dev packages.
		if (-1 !== packageName.indexOf('dev-')) {
			return memo;
		}

		if (!blockeraPackagesVersion[packageName]) {
			return memo;
		}

		const version = blockeraPackagesVersion[packageName];
		const packageDir = resolveBlockeraPackageDir(packageName);

		let name = packageName.startsWith('blockera')
			? camelCaseDash(packageName + '_' + version)
			: camelCaseDash('blockera-' + packageName + '_' + version);

		if ('icons' === packageName) {
			name = packageName.startsWith('blockera')
				? camelCaseDash(packageName)
				: camelCaseDash('blockera-' + packageName);
		}

		return {
			...memo,
			[packageName]: {
				import: packageDir,
				library: {
					name,
					type: 'var',
					export: exportDefaultPackages.includes(packageName)
						? 'default'
						: undefined,
				},
			},
		};
	}, {});

	return packagesConfig(env, {
		...argv,
		projectRoot: process.cwd(),
		entry: blockeraEntries,
		devtoolNamespace: 'blockeraSiteToolkit',
		mode: argv?.mode || 'production',
		externals: {
			// Externalize the local packages.
			'@blockera/icons': 'blockeraIcons',
			'@blockera/env': 'blockeraEnv_' + blockeraPackagesVersion.env,
			'@blockera/storage':
				'blockeraStorage_' + blockeraPackagesVersion.storage,
			'@blockera/data': 'blockeraData_' + blockeraPackagesVersion.data,
			'@blockera/utils': 'blockeraUtils_' + blockeraPackagesVersion.utils,
			'@blockera/editor':
				'blockeraEditor_' + blockeraPackagesVersion.editor,
			'@blockera/controls':
				'blockeraControls_' + blockeraPackagesVersion.controls,
			'@blockera/bootstrap':
				'blockeraBootstrap_' + blockeraPackagesVersion.bootstrap,
			'@blockera/wordpress':
				'blockeraWordpress_' + blockeraPackagesVersion.wordpress,
			'@blockera/classnames':
				'blockeraClassnames_' + blockeraPackagesVersion.classnames,
		},
	});
};
