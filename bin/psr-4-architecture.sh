#!/bin/bash

# Change to build directory
cd build

# Find all php directories recursively
find . -type d -name "php" | while read -r php_dir; do
    # Get parent directory
    parent_dir=$(dirname "$php_dir")
    
    echo "Found php directory at: $php_dir"
    echo "Moving files to: $parent_dir"
    
    # Check if php directory exists and has files
    if [ -d "$php_dir" ] && [ "$(ls -A $php_dir)" ]; then
        # Move all files from php directory to parent directory
        mv "$php_dir"/* "$parent_dir"/
        # Convert directory name to PascalCase
        # Remove leading ./ and convert to PascalCase
        pascal_dir=$(basename "$parent_dir" | tr '-' ' ' | tr '_' ' ' | awk '{for(i=1;i<=NF;i++){$i=toupper(substr($i,1,1)) tolower(substr($i,2))}}1' | tr -d ' ')

        # Exception for WordPress directory!
        if [ "$pascal_dir" == "Wordpress" ]; then
            pascal_dir="WordPress"
        fi

        # Only attempt move if pascal_dir is not empty
        if [ -n "$pascal_dir" ]; then
            mv "$parent_dir" "$pascal_dir"
            parent_dir="$pascal_dir"
        else
            echo "Warning: Could not convert directory name to PascalCase"
        fi
        
        # Remove the now empty php directory
        rmdir "$php_dir"
        
        echo "Successfully moved files from $php_dir to $parent_dir"
    else
        echo "Warning: $php_dir is empty or not accessible"
    fi
done

# Create src directory if it doesn't exist
mkdir -p src

# Move all files and directories to src directory
echo "Moving all files to src directory..."
find . -maxdepth 1 -not -name "src" -not -name "." -exec mv {} src/ \;

echo "Successfully moved files to src directory"


# Change back to the original directory
cd ..

echo "Script completed successfully"
