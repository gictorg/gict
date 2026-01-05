# Cloudinary Setup Guide

This guide will help you set up Cloudinary for image and file uploads in the GICT system.

## Step 1: Create Cloudinary Account

1. Go to [Cloudinary](https://cloudinary.com/)
2. Click **"Sign Up for Free"**
3. Create your account (free tier includes 25GB storage and 25GB bandwidth)
4. Verify your email

## Step 2: Get Your Credentials

1. After logging in, go to your **Dashboard**
2. You'll see your credentials:
   - **Cloud Name** (e.g., `your-cloud-name`)
   - **API Key** (e.g., `123456789012345`)
   - **API Secret** (e.g., `abcdefghijklmnopqrstuvwxyz123456`)

**Important**: Keep your API Secret secure! Never commit it to version control.

## Step 3: Install Cloudinary PHP SDK

Run this command in your project directory:

```bash
composer require cloudinary/cloudinary_php
```

Or if Composer is not available, the helper will try to load it automatically.

## Step 4: Configure Your Application

You have two options:

### Option A: Environment Variables (Recommended)

Create or edit `.env` file in your project root:

```bash
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

### Option B: Direct Configuration

Edit `includes/cloudinary_helper.php` and update these constants (around line 9-15):

```php
define('CLOUDINARY_CLOUD_NAME', 'your_cloud_name');
define('CLOUDINARY_API_KEY', 'your_api_key');
define('CLOUDINARY_API_SECRET', 'your_api_secret');
```

## Step 5: Test the Connection

Create a test file `test_cloudinary.php`:

```php
<?php
require_once 'includes/cloudinary_helper.php';

$result = testCloudinaryConnection();
echo "Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
echo "Message: " . $result['message'] . "\n";
if ($result['details']) {
    print_r($result['details']);
}
?>
```

Run it:
```bash
php test_cloudinary.php
```

## Step 6: Test Upload

Test uploading an image:

```php
<?php
require_once 'includes/cloudinary_helper.php';

// Create a test image
$test_file = '/tmp/test_image.jpg';
// ... create or use an existing image file

$result = smartUpload($test_file, 'test_image');
if ($result && $result['success']) {
    echo "Upload successful!\n";
    echo "URL: " . $result['url'] . "\n";
} else {
    echo "Upload failed: " . ($result['error'] ?? 'Unknown error') . "\n";
}
?>
```

## Features

- ✅ Automatic image optimization
- ✅ CDN delivery (fast loading)
- ✅ Automatic format conversion
- ✅ Image transformations on-the-fly
- ✅ Free tier: 25GB storage, 25GB bandwidth
- ✅ No folder sharing issues (unlike Google Drive)

## Security Notes

1. **Never commit** your API Secret to version control
2. Add `.env` to your `.gitignore`
3. Use environment variables in production
4. Keep your API Secret secure

## Troubleshooting

### Error: "Cloudinary SDK not found"
- **Solution**: Run `composer require cloudinary/cloudinary_php`

### Error: "Cloudinary credentials not configured"
- **Solution**: Set `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, and `CLOUDINARY_API_SECRET`

### Error: "Invalid credentials"
- **Solution**: Double-check your Cloud Name, API Key, and API Secret from Cloudinary Dashboard

### Images not displaying
- **Solution**: Check that the URL is using HTTPS (Cloudinary uses secure URLs by default)

## Migration from Google Drive

All files have been updated to use Cloudinary:
- ✅ Course images
- ✅ Student profile images
- ✅ Staff profile images
- ✅ Student documents

The `smartUpload()` function works the same way, so no other code changes are needed!

## Next Steps

1. Sign up for Cloudinary (free)
2. Get your credentials from Dashboard
3. Install SDK: `composer require cloudinary/cloudinary_php`
4. Configure credentials (environment variables or direct)
5. Test the connection
6. Start uploading!

## Support

- [Cloudinary Documentation](https://cloudinary.com/documentation)
- [Cloudinary PHP SDK](https://cloudinary.com/documentation/php_integration)
- [Cloudinary Dashboard](https://cloudinary.com/console)

