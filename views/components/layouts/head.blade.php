<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32"   href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16"   href="/favicons/favicon-16x16.png">
    
    <title>{{ $title ?? 'App' }}</title>

    <!-- Fonts -->
    <link href='https://fonts.googleapis.com/css?family=Poiret One:400,100,200,300,600,500,700,800,900' rel='stylesheet' type='text/css'>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>