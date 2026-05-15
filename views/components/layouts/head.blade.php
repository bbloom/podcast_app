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

    <style>
        [x-cloak] { display: none !important; }

        /* Markdown rendering */
        .markdown-content h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .markdown-content h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        .markdown-content h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; }
        .markdown-content p { margin-bottom: 0.75rem; }
        .markdown-content ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 0.75rem; }
        .markdown-content ol { list-style-type: decimal; padding-left: 1.5rem; margin-bottom: 0.75rem; }
        .markdown-content li { margin-bottom: 0.25rem; }
        .markdown-content a { color: #7e22ce; text-decoration: underline; }
        .markdown-content strong { font-weight: 700; }
        .markdown-content blockquote { border-left: 3px solid #d1d5db; padding-left: 1rem; color: #6b7280; margin-bottom: 0.75rem; }
        .markdown-content code { background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-size: 0.875rem; }
    </style>
</head>