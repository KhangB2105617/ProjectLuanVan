<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị</title>
    <link rel="icon" href="/../assets/img/vector-shop-icon-png_302739.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { overflow-x: hidden; }
        #sidebar { transition: width 0.3s ease; }
        .collapsed-sidebar #sidebar { width: 70px; }
        .collapsed-sidebar #sidebar .nav-link span,
        .collapsed-sidebar #sidebar .logo-text { display: none; }
        .collapsed-sidebar main { margin-left: 70px !important; }
        main { margin-left: 220px; transition: margin-left 0.3s ease; }
        .toggle-btn {
            position: fixed; top: 10px; left: 230px; z-index: 1000;
        }
        .collapsed-sidebar .toggle-btn { left: 80px; }
    </style>
</head>
<body>