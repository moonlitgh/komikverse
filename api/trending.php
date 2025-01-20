<?php
require_once '../config.php';

$period = $_GET['period'] ?? 'daily';
$validPeriods = ['daily', 'weekly', 'monthly'];

if (!in_array($period, $validPeriods)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid period']);
    exit;
}

try {
    $trendingComics = getTrendingComics($period);
    
    ob_start();
    foreach ($trendingComics as $index => $comic): ?>
        <a href="comic-detail.php?id=<?= $comic['comic_id'] ?>" 
           class="card-hover relative bg-dark border border-wine/30 rounded-lg overflow-hidden">
            <?php if ($index < 3): ?>
                <div class="absolute top-2 left-2 w-8 h-8 bg-flame rounded-full flex items-center justify-center text-white font-bold">
                    #<?= $index + 1 ?>
                </div>
            <?php endif; ?>
            <div class="relative aspect-[3/4]">
                <img src="assets/cover/<?= htmlspecialchars($comic['cover_image']) ?>" 
                     alt="<?= htmlspecialchars($comic['title']) ?>"
                     class="w-full h-full object-cover">
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-flame mb-2"><?= htmlspecialchars($comic['title']) ?></h3>
                <div class="flex items-center justify-between text-sm text-gray-400">
                    <span><?= number_format($comic['period_views']) ?> views</span>
                </div>
            </div>
        </a>
    <?php endforeach;
    $html = ob_get_clean();
    
    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch trending comics']);
} 