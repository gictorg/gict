#!/bin/bash

# Create images directory if it doesn't exist
mkdir -p images

# Download logo
curl -o images/lucknow_logo.png https://www.lkouniv.ac.in/site/writereaddata/siteContent/logo_1.png

# Download university gate image
curl -o images/university_gate.jpg https://www.lkouniv.ac.in/site/writereaddata/siteContent/university_building.jpg

# Download news images
curl -o images/convocation.jpg https://www.lkouniv.ac.in/site/writereaddata/siteContent/convocation.jpg
curl -o images/admission.jpg https://www.lkouniv.ac.in/site/writereaddata/siteContent/admission.jpg
curl -o images/events.jpg https://www.lkouniv.ac.in/site/writereaddata/siteContent/events.jpg

echo "Images downloaded successfully!" 