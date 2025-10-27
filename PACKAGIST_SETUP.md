# Packagist Package Setup

## 📦 Package Information

This package is configured for easy installation from Packagist.

### Repository Details
- **GitHub**: https://github.com/es-77/laravel-stripe-managers
- **Maintainer**: Emmanuel Saleem
- **License**: MIT
- **Language**: PHP (Blade templates)

### Current Versions
- `v1.0.1` - Enhanced package metadata and installation instructions
- `v1.0.2` - Packagist integration with dependents support

## 🚀 Publishing to Packagist

### 1. Push tags to GitHub
```bash
git push origin v1.0.1
git push origin v1.0.2
git push origin master
```

### 2. Submit to Packagist

1. Visit: https://packagist.org/packages/submit
2. Enter your GitHub repository URL: `https://github.com/es-77/laravel-stripe-managers`
3. Click "Check" or "Submit"
4. Packagist will automatically import your package

### 3. Auto-update Setup (Optional)

To enable automatic updates from GitHub to Packagist:

1. Go to your package page on Packagist
2. Click "Settings" 
3. Copy the "API Token"
4. Go to your GitHub repository → Settings → Webhooks
5. Add webhook: `https://packagist.org/api/github? Sat =YOUR_API_TOKEN`
6. Select "Just the push event"

## 📊 Package Features

### What's Included in composer.json:

✅ **Repository information** (for GitHub integration)  
✅ **Homepage and support links**  
✅ **Issue tracking URL**  
✅ **Keywords** for better discoverability  
✅ **Require-dev** for PHPUnit testing  
✅ **Suggest** section with optional dependencies  
✅ **Config** for optimized autoloading  
✅ **Open source** (MIT license)

### Installation Commands

```bash
# Install stable version
composer require emmanuelsaleem/laravel-stripe-manager

# Install specific version
composer require emmanuelsaleem/laravel-stripe-manager:^1.0.2

# Install dev version
composer require emmanuelsaleem/laravel-stripe-manager:dev-master
```

## 📈 Package Stats (After Publishing)

Once published, your package will show:
- ✅ Install count
- ✅ Dependent packages
- ✅ Stars and watchers
- ✅ Open issues
- ✅ Security advisories (if any)

## ✨ Next Steps

1. Push all tags to GitHub
2. Submit to Packagist  
3. Install in a Laravel project to test
4. Monitor for dependents
