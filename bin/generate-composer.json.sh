#!/bin/bash

# Function to convert directory name to package name
to_package_name() {
    echo "blockera/$1"
}

# Default values
package_name="blockera/build"
version="1.0.0" 
destination="./build"
package_names=("utils" "bootstrap" "wordpress" "site-toolkit" "dev-phpunit")

# Parse command line options
while getopts "n:v:d:p:" opt; do
  case $opt in
    n) package_name="$OPTARG";;
    v) version="$OPTARG";;
    d) destination="$OPTARG";;
    p) IFS=',' read -ra package_names <<< "$OPTARG";;
    \?) echo "Invalid option -$OPTARG" >&2; exit 1;;
  esac
done

# Initialize arrays for repositories and requirements
declare -a files
declare -a requirements

# Initialize files array
files=()

# Process packages to find functions.php files
for package in "${package_names[@]}"; do
    # Trim whitespace
    package=$(echo "$package" | xargs)

    # Convert to PascalCase using tr and awk
    pascal_case_package=$(basename "$package" | tr '-' ' ' | tr '_' ' ' | awk '{for(i=1;i<=NF;i++){$i=toupper(substr($i,1,1)) tolower(substr($i,2))}}1' | tr -d ' ')

    # Check if functions.php exists in package
    if [[ -f "build/src/${pascal_case_package}/functions.php" ]]; then
        if [[ "${#files[@]}" -eq 0 ]]; then
            files+=("\"src/${pascal_case_package}/functions.php\"")
        else
            files+=(",\"src/${pascal_case_package}/functions.php\"")
        fi
    fi
done

# Create files JSON string
if [[ "${#files[@]}" -gt 0 ]]; then
    files_json=$(printf "%s" "${files[@]}")
    
    # Add files section to requirements
    requirements+=("\"files\": [${files_json}]")
fi

# Create composer.json
cat > ./${destination}/composer.json << EOF
{
    "name": "${package_name}",
    "version": "${version}",
    "description": "A site toolkit plugin for Blockera AI.",
    "autoload": {
        "psr-4": {
            "Build\\\Packages\\\": "src/"
        },
        $(printf "\n        %s" "${requirements[@]}")
    }
}
EOF

echo "composer.json has been created successfully!"