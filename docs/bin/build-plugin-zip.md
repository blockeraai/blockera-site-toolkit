## Run Build Plugin Zip Command

<code>
php ./bin/generate-build-plugin-zip-sh.php > ./bin/build-plugin-zip.temp.sh &&
chmod +x ./bin/build-plugin-zip.temp.sh &&
export NO_CHECKS='true' &&
export NO_INSTALL_NPM='true' &&
./bin/build-plugin-zip.temp.sh &&
rm -rf ./bin/build-plugin-zip.temp.sh
</code>

### Delete Permanently Temporary File

`rm -rf ./bin/build-plugin-zip.temp.sh`