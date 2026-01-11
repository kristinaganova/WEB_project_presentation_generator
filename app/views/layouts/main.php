<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title . ' - ' : ''; ?>Презентационен генератор</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/main.css">
    <?php if (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <?php endif; ?>
    <?php if (strpos($_SERVER['REQUEST_URI'], '/presentation') !== false): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/presentation.css">
    <?php endif; ?>
    <?php if (strpos($_SERVER['REQUEST_URI'], '/viewWorkspace') !== false): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/workspace.css">
    <?php endif; ?>
    <?php if (strpos($_SERVER['REQUEST_URI'], '/slide') !== false): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/slides.css">
    <?php endif; ?>
    <!-- WebSocket Real-time Collaboration -->
    <?php if (strpos($_SERVER['REQUEST_URI'], '/presentation/view/') !== false): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/websocket.css">
    <?php endif; ?>
</head>
<body <?php if (isset($_SESSION['user_id'])): ?>data-user-id="<?php echo htmlspecialchars($_SESSION['user_id']); ?>" data-username="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>"<?php endif; ?>>
    <?php 
    $currentUrl = $_SERVER['REQUEST_URI'];
    $isAuthPage = strpos($currentUrl, '/auth/login') !== false || strpos($currentUrl, '/auth/register') !== false;
    
    if (isset($_SESSION['user_id']) && !$isAuthPage): 
    ?>
        <nav class="main-nav">
            <div class="nav-content">
                <a href="<?php echo BASE_URL; ?>" class="logo">Презентационен генератор</a>
                <ul class="nav-links">
                    <li><a href="<?php echo BASE_URL; ?>/dashboard">Табло</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/auth/logout" class="btn btn-danger">Изход</a></li>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

    <div class="container">
        <?php echo $content; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <!-- WebSocket Real-time Collaboration -->
    <?php if (strpos($_SERVER['REQUEST_URI'], '/presentation/view/') !== false): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/websocket-client.js"></script>
    <?php endif; ?>
</body>
</html> 