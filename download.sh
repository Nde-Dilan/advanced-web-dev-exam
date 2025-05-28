#!/bin/bash
# filepath: c:\xampp\htdocs\advanced-web-dev-exam\download_images.sh

# Create the images directory if it doesn't exist
mkdir -p assets/images

# Base URL for placeholder images (using picsum.photos for free placeholder images)
BASE_URL="https://picsum.photos"

# Array of image names and their corresponding dimensions and themes
declare -A images=(
    ["tech-summit.jpg"]="800/600"
    ["ngondo-festival.jpg"]="800/600"
    ["business-conference.jpg"]="800/600"
    ["mount-cameroon-marathon.jpg"]="800/600"
    ["film-festival.jpg"]="800/600"
    ["food-craft-fair.jpg"]="800/600"
    ["music-awards.jpg"]="800/600"
    ["agriculture-expo.jpg"]="800/600"
    ["beach-festival.jpg"]="800/600"
    ["unity-day.jpg"]="800/600"
    ["trade-fair.jpg"]="800/600"
    ["seafood-festival.jpg"]="800/600"
    ["tech-conference.jpg"]="800/600"
    ["music-festival.jpg"]="800/600"
    ["networking.jpg"]="800/600"
    ["art-exhibition.jpg"]="800/600"
    ["food-festival.jpg"]="800/600"
)

echo "Starting image download..."

# Download each image
for filename in "${!images[@]}"; do
    dimensions=${images[$filename]}
    url="$BASE_URL/$dimensions"
    
    echo "Downloading $filename..."
    
    # Use curl to download the image
    curl -L -o "assets/images/$filename" "$url"
    
    if [ $? -eq 0 ]; then
        echo "✓ Successfully downloaded $filename"
    else
        echo "✗ Failed to download $filename"
    fi
    
    # Small delay to be respectful to the service
    sleep 1
done

echo "Image download completed!"
echo "Images saved to: assets/images/"