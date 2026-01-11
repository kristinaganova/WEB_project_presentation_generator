<?php
require_once __DIR__ . '/../../helpers/SlideRenderer.php';
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/presentation.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/slides.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Преглед на презентация</title>
</head>
<body>
    <div class="container">
        <div class="presentation-view" data-theme="<?= htmlspecialchars($data['presentation']['theme']) ?>">
            <div class="presentation-header">
                <h1><?= htmlspecialchars($data['presentation']['title']) ?></h1>
                <div class="presentation-meta">
                    <span><i class="fas fa-language"></i> Език: <?= htmlspecialchars($data['presentation']['language']) ?></span>
                    <span><i class="fas fa-palette"></i> Тема: <?= htmlspecialchars($data['presentation']['theme']) ?></span>
                </div>
                <div class="presentation-actions">
                    <a href="<?= BASE_URL ?>/presentation/exportPdfViaService/<?= $data['presentation']['id'] ?>" class="btn btn-success" title="Експорт като PDF чрез Node.js микросервиз">
                        <i class="fas fa-file-pdf"></i> Експорт PDF
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Експорт
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/presentation/export/<?php echo $data['presentation']['id']; ?>/html">HTML</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/presentation/export/<?php echo $data['presentation']['id']; ?>/xml">XML</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['warning']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="presentation-actions">
                <?php if ($data['isOwner']): ?>
                    <a href="<?= BASE_URL ?>/presentation/edit/<?= $data['presentation']['id'] ?>" class="btn btn-secondary"><i class="fas fa-edit"></i> Редактирай</a>
                    <a href="<?= BASE_URL ?>/presentation/delete/<?= $data['presentation']['id'] ?>" class="btn btn-danger"><i class="fas fa-trash"></i> Изтрий</a>
                    <a href="<?= BASE_URL ?>/slide/create/<?= $data['presentation']['id'] ?>" class="btn btn-success">
                        <i class="fas fa-plus"></i> Добави слайд
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/presentation/review/<?= $data['presentation']['id'] ?>" class="btn btn-primary"><i class="fas fa-eye"></i> Преглед</a>
                <a href="<?= BASE_URL ?>/dashboard" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Назад
                </a>
            </div>

            <div class="presentation-preview">
                <div class="slides-container">
                    <?php if (empty($data['slides'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt fa-3x"></i>
                            <p>Няма добавени слайдове.</p>
                            <a href="<?= BASE_URL ?>/slide/create/<?= $data['presentation']['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Добави първи слайд
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($data['slides'] as $index => $slide): ?>
                            <div class="slide-container">
                                <div class="slide" data-slide-id="<?php echo $slide['id']; ?>">
                                    <div class="slide-header">
                                        <div class="slide-header-content">
                                            <h2 class="slide-title">
                                                <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($slide['title']); ?>
                                            </h2>
                                            <div class="slide-actions">
                                                <?php if ($data['isOwner']): ?>
                                                    <a href="<?php echo BASE_URL; ?>/slide/edit/<?php echo $slide['id']; ?>" class="btn btn-primary">
                                                        <i class="fas fa-edit"></i> Редактирай
                                                    </a>
                                                    <a href="<?php echo BASE_URL; ?>/slide/delete/<?php echo $slide['id']; ?>" class="btn btn-danger">
                                                        <i class="fas fa-trash"></i> Изтрий
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="slide-content <?php echo htmlspecialchars($slide['layout'] ?? 'full'); ?>">
                                        <?php if (!empty($slide['elements'])): ?>
                                            <?php foreach ($slide['elements'] as $element): ?>
                                                <div class="element-container">
                                                    <?php if (!empty($element['title'])): ?>
                                                        <h3 class="element-title"><?php echo htmlspecialchars($element['title']); ?></h3>
                                                    <?php endif; ?>

                                                    <?php if ($element['type'] === 'image'): ?>
                                                        <div class="content-element image">
                                                            <div class="image-container" style="background-image: url('<?php echo $element['content']; ?>');"></div>
                                                        </div>
                                                    <?php elseif ($element['type'] === 'image_text'): ?>
                                                        <div class="content-element type-image_text">
                                                            <div class="image-text-container">
                                                                <div class="image-container" style="background-image: url('<?php echo $element['content']; ?>');"></div>
                                                                <div class="text"><p><?php echo nl2br(htmlspecialchars($element['text'])); ?></p></div>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($element['type'] === 'image_list'): ?>
                                                        <div class="content-element type-image_list">
                                                            <div class="image-list-container">
                                                                <div class="image-container" style="background-image: url('<?php echo $element['content']; ?>');"></div>
                                                                <ul>
                                                                    <?php foreach (explode("\n", $element['text']) as $item): if (trim($item) !== ''): ?>
                                                                        <li><?php echo htmlspecialchars($item); ?></li>
                                                                    <?php endif; endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($element['type'] === 'quote'): ?>
                                                        <div class="content-element type-quote">
                                                            <blockquote>
                                                                <?php echo nl2br(htmlspecialchars($element['content'])); ?>
                                                                <?php if (!empty($element['title'])): ?>
                                                                    <cite>— <?php echo htmlspecialchars($element['title']); ?></cite>
                                                                <?php endif; ?>
                                                            </blockquote>
                                                        </div>
                                                    <?php elseif ($element['type'] === 'list'): ?>
                                                        <div class="content-element type-list">
                                                            <ul>
                                                                <?php foreach (explode("\n", $element['content']) as $item): if (trim($item) !== ''): ?>
                                                                    <li><?php echo htmlspecialchars($item); ?></li>
                                                                <?php endif; endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="content-element type-text">
                                                            <?php echo nl2br(htmlspecialchars($element['content'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="empty-content">Няма добавено съдържание</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="slide-controls">
                                    <?php if ($data['isOwner']): ?>
                                        <?php if ($index > 0): ?>
                                            <button class="move-up" onclick="moveSlide(<?php echo $slide['id']; ?>, 'up')">
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($index < count($data['slides']) - 1): ?>
                                            <button class="move-down" onclick="moveSlide(<?php echo $slide['id']; ?>, 'down')">
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function moveSlide(slideId, direction) {
            const slides = document.querySelectorAll('.slide-card');
            const currentSlide = document.querySelector(`[data-slide-id="${slideId}"]`);
            const currentIndex = Array.from(slides).indexOf(currentSlide);
            
            if (direction === 'up' && currentIndex > 0) {
                const newIndex = currentIndex - 1;
                const newOrder = Array.from(slides).map((slide, index) => {
                    if (index === currentIndex) return { id: slide.dataset.slideId, order: newIndex + 1 };
                    if (index === newIndex) return { id: slide.dataset.slideId, order: currentIndex + 1 };
                    return { id: slide.dataset.slideId, order: index + 1 };
                });
                
                updateSlideOrder(newOrder);
            } else if (direction === 'down' && currentIndex < slides.length - 1) {
                const newIndex = currentIndex + 1;
                const newOrder = Array.from(slides).map((slide, index) => {
                    if (index === currentIndex) return { id: slide.dataset.slideId, order: newIndex + 1 };
                    if (index === newIndex) return { id: slide.dataset.slideId, order: currentIndex + 1 };
                    return { id: slide.dataset.slideId, order: index + 1 };
                });
                
                updateSlideOrder(newOrder);
            }
        }

        function updateSlideOrder(newOrder) {
            fetch('<?= BASE_URL ?>/slide/updateOrder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ slides: newOrder })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Възникна грешка при разместването на слайдовете');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Възникна грешка при разместването на слайдовете');
            });
        }

        // Функция за експорт на презентацията
        function exportPresentation() {
            const presentationId = <?= $data['presentation']['id'] ?>;
            
            // Изпращаме AJAX заявка към сървъра
            fetch(`${BASE_URL}/presentation/export/${presentationId}/html`, {
                method: 'GET',
                headers: {
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Грешка при експорт на презентацията');
                }
                return response.blob();
            })
            .then(blob => {
                // Създаваме връзка за изтегляне
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = '<?= $data['presentation']['title'] ?>.html';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Възникна грешка при експорт на презентацията');
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- WebSocket Real-time Collaboration -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/websocket.css">
    <script src="<?= BASE_URL ?>/assets/js/websocket-client.js"></script>
    <script>
        // Initialize WebSocket for real-time collaboration
        const presentationId = <?= $data['presentation']['id'] ?>;
        const userId = <?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null' ?>;
        const username = '<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Anonymous' ?>';
        
        if (presentationId && userId) {
            const wsClient = new PresentationWebSocket(presentationId, {
                userId: userId,
                username: username,
                debug: true
            });
            
            // Reload page when slides are updated by other users
            wsClient.onSlideUpdated = (data) => {
                console.log('[App] Slide updated by:', data.username, 'User ID:', data.userId, 'My ID:', userId);
                console.log('[App] Will reload?', data.userId != userId);
                // Only reload if it's a different user
                if (data.userId != userId) {
                    setTimeout(() => {
                        console.log('[App] Reloading page to show updates...');
                        location.reload();
                    }, 1500);
                }
            };
            
            // Reload when slides are created
            wsClient.onSlideCreated = (data) => {
                console.log('[App] Slide created by:', data.username);
                if (data.userId != userId) {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            };
            
            // Reload when slides are deleted
            wsClient.onSlideDeleted = (data) => {
                console.log('[App] Slide deleted by:', data.username);
                if (data.userId != userId) {
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            };
            
            // Reload when presentation is updated (e.g., slide order changed)
            wsClient.onPresentationUpdated = (data) => {
                console.log('[App] Presentation updated by:', data.username, 'User ID:', data.userId, 'My ID:', userId);
                console.log('[App] Will reload?', data.userId != userId);
                if (data.userId != userId) {
                    setTimeout(() => {
                        console.log('[App] Reloading page to show slide order...');
                        location.reload();
                    }, 1500);
                }
            };
        }
    </script>
</body>
</html> 