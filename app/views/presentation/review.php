<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/presentation.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/slides.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>Преглед на презентация</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #000;
            overflow-x: hidden;
            width: 100%;
        }
        .review-container {
            width: 1200px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        .review-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        .review-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        .review-controls {
            display: flex;
            gap: 1rem;
        }
        .review-controls button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.5rem;
        }
        .review-controls button:hover {
            color: #007bff;
        }
        .slides-container {
            flex: 1;
            padding: 4rem 1rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 3rem;
            width: 100%;
            box-sizing: border-box;
        }
        .slide {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            position: relative;
            padding: 3rem;
            height: 900px;
            display: flex;
            flex-direction: column;
            width: 100%;
            box-sizing: border-box;
        }
        .slide-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }
        .slide-header h2 {
            font-size: 1.8rem;
            color: #333;
            margin: 0;
        }
        .slide-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .content-element {
            padding: 1.5rem;
            border-radius: 8px;
            background: #f8f9fa;
            width: 100%;
            box-sizing: border-box;
        }
        .content-element h3 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .content-element p {
            font-size: 1.4rem;
            line-height: 1.6;
        }
        .content-element.type-list ul {
            font-size: 1.4rem;
            line-height: 1.6;
            padding-left: 2rem;
        }
        .content-element.type-list li {
            margin-bottom: 1rem;
        }
        .content-element.type-quote blockquote {
            font-size: 1.6rem;
            line-height: 1.6;
            padding: 2rem;
            border-left-width: 6px;
        }
        .content-element.type-quote cite {
            font-size: 1.2rem;
            margin-top: 1.5rem;
        }
        .content-element.type-image_text .image-text-container,
        .content-element.type-image_list .image-list-container {
            gap: 3rem;
            width: 100%;
            box-sizing: border-box;
        }
        .content-element.type-image_text .image-text-container img,
        .content-element.type-image_list .image-list-container img {
            max-width: 50%;
            height: auto;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .content-element.type-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .slide-number {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            color: #666;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .fullscreen-btn {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 3rem;
            height: 3rem;
            cursor: pointer;
            z-index: 1000;
        }
        .fullscreen-btn:hover {
            background: rgba(0, 0, 0, 0.9);
        }
        .print-btn {
            position: fixed;
            bottom: 1rem;
            right: 5rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 3rem;
            height: 3rem;
            cursor: pointer;
            z-index: 1000;
        }
        .print-btn:hover {
            background: rgba(0, 0, 0, 0.9);
        }
        @media print {
            body {
                background: white;
            }
            .review-header,
            .fullscreen-btn,
            .print-btn {
                display: none;
            }
            .slides-container {
                max-width: none;
                padding: 0;
                gap: 2rem;
            }
            .slide {
                min-height: auto;
                padding: 2rem;
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-after: always;
            }
            .content-element {
                padding: 1rem;
            }
            .content-element h3 {
                font-size: 1.8rem;
                color: #3A8EE4;
            }
            .content-element p,
            .content-element.type-list ul {
                font-size: 1.2rem;
            }
            .content-element.type-quote blockquote {
                font-size: 1.4rem;
            }
        }

        /* Dark theme styles */
        .slides-container[data-theme="dark"] {
            background-color: #1a1a1a;
        }
        
        .slides-container[data-theme="dark"] .slide .slide-header h2 {
            color: #fff;
        }
        .slides-container[data-theme="dark"] .slide .slide-header {
            background-color: #2d2d2d;
        }

        .slides-container[data-theme="dark"] .slide {
            background-color: #2d2d2d;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .slides-container[data-theme="dark"] .content-element {
            background-color: #333333;
            color: #e0e0e0;
        }

        .slides-container[data-theme="dark"] .content-element h3 {
            color: #ffffff;
        }

        .slides-container[data-theme="dark"] .content-element p {
            color: #e0e0e0;
        }

        .slides-container[data-theme="dark"] .content-element.type-list li {
            color: #e0e0e0;
        }

        .slides-container[data-theme="dark"] .content-element.type-quote blockquote {
            border-left-color: #666666;
            color: #e0e0e0;
        }

        .slides-container[data-theme="dark"] .content-element.type-quote cite {
            color: #b3b3b3;
        }

        .slides-container[data-theme="dark"] .slide-number {
            color: #b3b3b3;
        }

        /* Barbie theme styles */
        .slides-container[data-theme="barbie"] .slide .slide-header h2 {
            color: #FD269B;
        }
        .slides-container[data-theme="barbie"] .slide .slide-header {
            background-color: #ffc3f0;
        }

        .slides-container[data-theme="barbie"] .slide {
            background-color: #ffc3f0;
            color: #FD269B;
            box-shadow: 0 2px 4px rgba(253, 38, 155, 0.3);
        }

        .slides-container[data-theme="barbie"] .content-element {
            background-color: #ffc3f0;
            border-color: #FD269B;
        }

        .slides-container[data-theme="barbie"] .content-element h3 {
            color: #FD269B;
        }

        .slides-container[data-theme="barbie"] .content-element p {
            color: #FD269B;
        }

        .slides-container[data-theme="barbie"] .content-element.type-list li {
            color: #FD269B;
        }

        .slides-container[data-theme="barbie"] .content-element.type-quote blockquote {
            border-left-color: #FBB659;
            color: #FD269B;
        }

        .slides-container[data-theme="barbie"] .content-element.type-quote cite {
            color: #FBB659;
        }

        /* New styles for barbie theme */
        body.barbie-theme .slide {
            background: #ffc3f0;
            color: #FD269B;
            box-shadow: 0 2px 4px rgba(253, 38, 155, 0.3);
        }

        body.barbie-theme .slide-element {
            color: #FD269B;
        }

        body.barbie-theme .slide-element.image {
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }

        body.barbie-theme .slide-element.text {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        body.barbie-theme .slide-element.title {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 2.5em;
            font-weight: bold;
            color: #FD269B;
        }

        body.barbie-theme .slide-element.content {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        body.barbie-theme .slide.dragging {
            opacity: 0.5;
        }

        body.barbie-theme .slide.drag-over {
            border: 2px dashed #FBB659;
        }

        body.barbie-theme .slide-element {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.barbie-theme .slide-element.title {
            font-size: 2.5em;
            font-weight: bold;
            color: #FD269B;
        }

        body.barbie-theme .slide-element.text {
            font-size: 1.5em;
            color: #FD269B;
        }

        body.barbie-theme .slide-element.image {
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }

        body.barbie-theme .slide-element.content {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #FD269B;
        }

        /* Ken theme styles */
        .slides-container[data-theme="ken"] {
            background-color: #B4E6FF;
        }
        
        .slides-container[data-theme="ken"] .slide .slide-header h2 {
            color:#1963ad
        }
        .slides-container[data-theme="ken"] .slide .slide-header {
            background-color: #B4E6FF;
        }

        .slides-container[data-theme="ken"] .slide {
            background-color: #B4E6FF;
            color: #3A8EE4;
            box-shadow: 0 2px 4px rgba(58, 142, 228, 0.3);
        }

        .slides-container[data-theme="ken"] .content-element {
            background-color: #B4E6FF;
            border-color: #1963ad;
        }

        .slides-container[data-theme="ken"] .content-element h3 {
            color: #1963ad;
        }

        .slides-container[data-theme="ken"] .content-element p {
            color: #1963ad;
        }

        .slides-container[data-theme="ken"] .content-element.type-list li {
            color: #1963ad;
        }

        .slides-container[data-theme="ken"] .content-element.type-quote blockquote {
            border-left-color: #ffa5be;
            color: #1963ad;
        }

        .slides-container[data-theme="ken"] .content-element.type-quote cite {
            color: #ffa5be;
        }

        .slides-container[data-theme="ken"] .slide-number {
            color: #ffa5be;
        }

        /* New styles for ken theme */
        body.ken-theme .slide {
            background: #B4E6FF;
            color: #1963ad;
            box-shadow: 0 2px 4px rgba(58, 142, 228, 0.3);
        }

        body.ken-theme .slide-element {
            color: #1963ad;
        }

        body.ken-theme .slide-element.image {
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }

        body.ken-theme .slide-element.text {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        body.ken-theme .slide-element.title {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 2.5em;
            font-weight: bold;
            color: #1963ad;
        }

        body.ken-theme .slide-element.content {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        body.ken-theme .slide.dragging {
            opacity: 0.5;
        }

        body.ken-theme .slide.drag-over {
            border: 2px dashed #ffa5be;
        }

        body.ken-theme .slide-element {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        body.ken-theme .slide-element.title {
            font-size: 2.5em;
            font-weight: bold;
            color: #1963ad;
        }

        body.ken-theme .slide-element.text {
            font-size: 1.5em;
            color: #1963ad;
        }

        body.ken-theme .slide-element.image {
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }

        body.ken-theme .slide-element.content {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #1963ad;
        }
    </style>
</head>
<body>
    <div class="review-container">
        <div class="review-header">
            <h1><?= htmlspecialchars($data['presentation']['title']) ?></h1>
            <div class="review-controls">
                <button id="exitReview" title="Изход"><i class="fas fa-times"></i></button>
            </div>
        </div>

        <div class="slides-container" data-theme="<?= htmlspecialchars($data['presentation']['theme']) ?>">
            <?php foreach ($data['slides'] as $index => $slide): ?>
                <div class="slide" id="slide-<?= $index + 1 ?>">
                    <div class="slide-header">
                        <div class="slide-header-content">
                            <h2 class="slide-title">
                                <?php echo htmlspecialchars($slide['title']); ?>
                            </h2>
                        </div>
                    </div>

                    <div class="slide-content <?= htmlspecialchars($slide['layout'] ?? 'full') ?>">
                        <?php if (!empty($slide['elements'])): ?>
                            <?php foreach ($slide['elements'] as $element): ?>
                                <div class="content-element type-<?= htmlspecialchars($element['type'] ?? 'text') ?>" <?php 
                                    if (!empty($element['style'])) {
                                        $style = json_decode($element['style'], true);
                                        $styleString = '';
                                        if (!empty($style['color'])) $styleString .= 'color: ' . htmlspecialchars($style['color']) . ';';
                                        if (!empty($style['fontSize'])) $styleString .= 'font-size: ' . htmlspecialchars($style['fontSize']) . ';';
                                        if (!empty($style['textAlign'])) $styleString .= 'text-align: ' . htmlspecialchars($style['textAlign']) . ';';
                                        if ($styleString) echo 'style="' . $styleString . '"';
                                    }
                                ?>>
                                    <?php if (!empty($element['title'])): ?>
                                        <h3 class="element-title"><?= htmlspecialchars($element['title']) ?></h3>
                                    <?php endif; ?>
                                    
                                    <?php if ($element['type'] === 'text'): ?>
                                        <p><?= nl2br(htmlspecialchars($element['content'] ?? '')) ?></p>
                                    <?php elseif ($element['type'] === 'image'): ?>
                                            <div class="image-container" style="background-image: url('<?= htmlspecialchars($element['content'] ?? '') ?>');"></div>
                                    <?php elseif ($element['type'] === 'image_text'): ?>
                                        <div class="image-text-container">
                                            <div class="image-container" style="background-image: url('<?php echo $element['content']; ?>');"></div>
                                            <div class="text"><p><?php echo nl2br(htmlspecialchars($element['text'])); ?></p></div>
                                        </div>
                                    <?php elseif ($element['type'] === 'list'): ?>
                                        <ul>
                                            <?php 
                                            $items = !empty($element['content']) ? explode("\n", $element['content']) : [];
                                            foreach ($items as $item):
                                                if (trim($item) !== ''):
                                            ?>
                                                <li><?= htmlspecialchars(trim($item)) ?></li>
                                            <?php 
                                                endif;
                                            endforeach;
                                            ?>
                                        </ul>
                                    <?php elseif ($element['type'] === 'image_list'): ?>
                                        <div class="image-list-container">
                                            <div class="image-container" style="background-image: url('<?php echo $element['content']; ?>');"></div>
                                            <ul>
                                                <?php 
                                                $items = !empty($element['text']) ? explode("\n", $element['text']) : [];
                                                foreach ($items as $item): 
                                                    if (trim($item) !== ''):
                                                ?>
                                                    <li><?= htmlspecialchars(trim($item)) ?></li>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </ul>
                                        </div>
                                    <?php elseif ($element['type'] === 'quote'): ?>
                                        <blockquote>
                                            <?= nl2br(htmlspecialchars($element['content'] ?? '')) ?>
                                            <?php if (!empty($element['title'])): ?>
                                                <cite>— <?= htmlspecialchars($element['title']) ?></cite>
                                            <?php endif; ?>
                                        </blockquote>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="slide-number"><?= $index + 1 ?> / <?= count($data['slides']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="fullscreen-btn" id="fullscreenBtn" title="На цял екран">
            <i class="fas fa-expand"></i>
        </button>
        <button class="print-btn" id="printBtn" title="Принтирай">
            <i class="fas fa-print"></i>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exitBtn = document.getElementById('exitReview');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const printBtn = document.getElementById('printBtn');

            // Exit button
            exitBtn.addEventListener('click', function() {
                window.location.href = '<?= BASE_URL ?>/presentation/viewPresentation/<?= $data['presentation']['id'] ?>';
            });

            // Fullscreen
            fullscreenBtn.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen();
                    fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
                } else {
                    document.exitFullscreen();
                    fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
                }
            });

            // Print
            printBtn.addEventListener('click', function() {
                window.print();
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.location.href = '<?= BASE_URL ?>/presentation/viewPresentation/<?= $data['presentation']['id'] ?>';
                }
            });
        });
    </script>
</body>
</html> 