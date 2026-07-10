<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    
    <!-- Open Graph tags for WhatsApp & other social crawlers -->
    <meta property="og:image" content="{{ $imageUrl }}" />
    <meta property="og:title" content="{{ $title }}" />
    <meta property="og:description" content="{{ $description }}" />
    <meta property="og:type" content="website" />
    
    <!-- Fallback general meta tags -->
    <meta name="description" content="{{ $description }}" />
</head>
<body>
    <!-- Halaman ini disajikan khusus untuk crawler sosmed (seperti WhatsApp) -->
    <p>{{ $title }}</p>
</body>
</html>
