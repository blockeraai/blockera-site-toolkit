#!/bin/bash

# Exit if any command fails.
set -e

# Change to the expected directory.
cd "$(dirname "$0")"
cd ..

# Enable nicer messaging for build status.
BLUE_BOLD='\033[1;34m'
GREEN_BOLD='\033[1;32m'
RED_BOLD='\033[1;31m'
YELLOW_BOLD='\033[1;33m'
COLOR_RESET='\033[0m'
error () {
	echo -e "\n${RED_BOLD}$1${COLOR_RESET}\n"
}
status () {
	echo -e "\n${BLUE_BOLD}$1${COLOR_RESET}\n"
}
success () {
	echo -e "\n${GREEN_BOLD}$1${COLOR_RESET}\n"
}
warning () {
	echo -e "\n${YELLOW_BOLD}$1${COLOR_RESET}\n"
}

status "💃 Time to build the Blockera Site Toolkit plugin ZIP file 🕺"

if [ -z "$NO_CHECKS" ]; then
	# Make sure there are no changes in the working tree. Release builds should be
	# traceable to a particular commit and reliably reproducible.
	changed=
	if ! git diff --exit-code > /dev/null; then
		changed="file(s) modified"
	elif ! git diff --cached --exit-code > /dev/null; then
		changed="file(s) staged"
	fi
	if [ ! -z "$changed" ]; then
		git status
		error "ERROR: Cannot build plugin zip with dirty working tree. ☝️
		Commit your changes and try again."
		exit 1
	fi

	# Do a dry run of the repository reset. Prompting the user for a list of all
	# files that will be removed should prevent them from losing important files!
	#
	# Keep the sparse global-packages submodule working tree intact.
	status "Resetting the repository to pristine condition. ✨"
	git_clean_excludes=(
		--exclude=packages/global-packages
		--exclude=packages/global-packages/**
	)
	to_clean=$(git clean -xdf --dry-run "${git_clean_excludes[@]}")
	if [ ! -z "$to_clean" ]; then
		echo $to_clean
		warning "🚨 About to delete everything above! Is this okay? 🚨"
		echo -n "[y]es/[N]o: "
		read answer
		if [ "$answer" != "${answer#[Yy]}" ]; then
			# Remove ignored files to reset repository to pristine condition. Previous
			# test ensures that changed files abort the plugin build.
			status "Cleaning working directory... 🛀"
			git clean -xdf "${git_clean_excludes[@]}"
		else
			error "Fair enough; aborting. Tidy up your repo and try again. 🙂"
			exit 1
		fi
	fi
fi

# Clean old and extra files
status "Cleaning build files... 🗂"
rm -r -f dist

# Run the build.
status "Installing dependencies... 📦"
if [ -z "$NO_INSTALL_COMPOSER" ]; then
  composer install --no-dev -o --apcu-autoloader -a
fi
if [ -z "$NO_INSTALL_NPM" ]; then
  npm i
fi

status "Generating build... 🗂"
npm run build

# Shared packages live in packages/global-packages/packages and are consumed via
# Composer path repos under vendor/blockera/*. Prefer vendor for packaging.
resolve_shared_package_file () {
	local relative_path="$1"
	local candidate
	for candidate in \
		"vendor/blockera/${relative_path}" \
		"packages/global-packages/packages/${relative_path}"
	do
		if [ -f "${candidate}" ]; then
			php -r 'echo realpath($argv[1]);' "${candidate}"
			return 0
		fi
	done
	return 1
}

# Track temporary production edits so cleanup can always restore them.
ZIP_BUILD_BACKUPS=()
restore_zip_build_backups () {
	local backup
	local original
	for backup in "${ZIP_BUILD_BACKUPS[@]:-}"; do
		[ -n "${backup}" ] || continue
		original="${backup%.zip-build.bak}"
		if [ -f "${backup}" ]; then
			mv -f "${backup}" "${original}"
		fi
	done
	ZIP_BUILD_BACKUPS=()
}
trap restore_zip_build_backups EXIT

backup_and_replace () {
	local target_file="$1"
	local next_file="$2"

	cp "${target_file}" "${target_file}.zip-build.bak"
	ZIP_BUILD_BACKUPS+=("${target_file}.zip-build.bak")
	mv "${next_file}" "${target_file}"
}

# Temporarily modify `blockera-site-toolkit.php` with production constants defined.
# Use a temp file because `bin/generate-blockera-site-toolkit-php.php` reads from
# `blockera-site-toolkit.php` so we need to avoid writing to that file at the same time.
status "Generating blockera-site-toolkit.php 📝"
php bin/generate-blockera-site-toolkit-php.php > blockera-site-toolkit.tmp.php
backup_and_replace "blockera-site-toolkit.php" "blockera-site-toolkit.tmp.php"

# Ship autoloader-coordinator into inc/ (production entry requires inc/bootstrap.php).
status "Generating inc/ autoloader-coordinator 📝"
mkdir -p "inc"
COORDINATOR_BOOTSTRAP="$(resolve_shared_package_file "autoloader-coordinator/bootstrap.php" || true)"
COORDINATOR_CLASS="$(resolve_shared_package_file "autoloader-coordinator/class-shared-autoload-coordinator.php" || true)"
if [ -z "${COORDINATOR_BOOTSTRAP}" ] || [ -z "${COORDINATOR_CLASS}" ]; then
	error "ERROR: Could not find autoloader-coordinator under vendor/blockera or packages/global-packages/packages."
	exit 1
fi
cp "${COORDINATOR_CLASS}" inc/class-shared-autoload-coordinator.php
cp "${COORDINATOR_BOOTSTRAP}" inc/bootstrap.php

build_files=$(
	ls dist/*/*.{min.js,min.css,asset.php} 2>/dev/null || true
)

main_plugin_file='blockera-site-toolkit.php'

if [ -n "$MAIN_FILE_SUFFIX" ]; then
  main_plugin_file="blockera-site-toolkit$MAIN_FILE_SUFFIX.php"
  cp blockera-site-toolkit.php "$main_plugin_file"
fi

status "Verifying production Setup class is autoloadable... 🔎"
if ! php -r 'require "vendor/autoload.php"; exit(class_exists("BlockeraAI\\SiteToolkit\\Setup") ? 0 : 1);'; then
	error "ERROR: BlockeraAI\\SiteToolkit\\Setup was not found after composer install. Aborting zip."
	exit 1
fi

# Generate the plugin zip file.
status "Creating archive... 🎁"
zip -r -9 -q blockera-site-toolkit.zip \
	inc \
	languages \
	readme.txt \
	$build_files \
	$main_plugin_file \
	composer.json \
	experimental.config.json \
  ### BEGIN AUTO-GENERATED THIRD-PARTY VENDOR PATH PATTERN
  ### END AUTO-GENERATED THIRD-PARTY VENDOR PATH PATTERN
  ### BEGIN AUTO-GENERATED VENDOR PACKAGES PATH PATTERN
  ### END AUTO-GENERATED VENDOR PACKAGES PATH PATTERN
  -x "*.map" "*.scss" "*.zip-build.bak" \
  && echo "blockera-site-toolkit.zip created successfully ✅" || echo "blockera-site-toolkit.zip creation failed ❌"

status "Cleaning up... 🧹"
restore_zip_build_backups
trap - EXIT

# Drop generated main-file copy when a custom suffix was used.
if [ -n "${MAIN_FILE_SUFFIX:-}" ] && [ -f "${main_plugin_file}" ] && [ "${main_plugin_file}" != "blockera-site-toolkit.php" ]; then
	rm -f "${main_plugin_file}"
fi

success "Done ✅ You've built Blockera Site Toolkit! 🎉 "
