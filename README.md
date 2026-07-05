# MistySoft QR-NFC Go

Redirect service for QR-NFC system. Handles QR code resolution and redirects.

## Tech Stack

- PHP 8+
- cURL for API calls
- Apache .htaccess for URL rewriting

## Setup

1. Copy environment variables:
```bash
cp .env.example .env
```

2. Edit `.env` with your configuration:
```
API_URL=https://api.mistydev.id.vn
LOG_SCANS=true
NODE_ENV=production
```

3. Upload to InfinityFree hosting
4. Configure subdomain: go.mistydev.id.vn

## How It Works

1. User scans QR code → goes to `go.mistydev.id.vn/{code}`
2. PHP resolves code via API
3. Logs scan event
4. Redirects to destination URL

## Deployment

Deploy to InfinityFree:
1. Upload all files to `htdocs/`
2. Configure subdomain in InfinityFree panel
3. Ensure PHP 8+ is enabled

## License

MIT
