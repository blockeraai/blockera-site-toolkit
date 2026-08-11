#!/usr/bin/env node

/**
 * Internal dependencies
 */
const config = require('./config');
const {
	createPluginCli,
} = require('../../packages/global-packages/packages/dev-tools/bin/plugin/cli');

createPluginCli(config);
