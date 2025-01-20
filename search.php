<?php
require_once 'config.php';

// Get all available genres for filter
$genres = fetchAll("SELECT * FROM genres ORDER BY name");

// Get search parameters
$search = $_GET['q'] ?? '';
$selectedGenres = $_GET['genres'] ?? [];
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$rating = $_GET['rating'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;

// Build the base query
$query = "
    SELECT DISTINCT
        c.*,
        GROUP_CONCAT(g.name) as genres,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT ch.chapter_id) as chapter_count
    FROM comics c
    LEFT JOIN comic_genres cg ON c.comic_id = cg.comic_id
    LEFT JOIN genres g ON cg.genre_id = g.genre_id
    LEFT JOIN ratings r ON c.comic_id = r.comic_id
    LEFT JOIN chapters ch ON c.comic_id = ch.comic_id
    WHERE 1=1
";

$params = [];

// Add search condition
if (!empty($search)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add genre filter
if (!empty($selectedGenres)) {
    $placeholders = str_repeat('?,', count($selectedGenres) - 1) . '?';
    $query .= " AND c.comic_id IN (
        SELECT comic_id 
        FROM comic_genres cg2 
        JOIN genres g2 ON cg2.genre_id = g2.genre_id 
        WHERE g2.name IN ($placeholders)
        GROUP BY comic_id 
        HAVING COUNT(DISTINCT g2.genre_id) = ?
    )";
    $params = array_merge($params, $selectedGenres);
    $params[] = count($selectedGenres);
}

// Add status filter
if (!empty($status)) {
    $query .= " AND c.status = ?";
    $params[] = $status;
}

// Add rating filter
if (!empty($rating)) {
    $query .= " AND (SELECT AVG(rating) FROM ratings WHERE comic_id = c.comic_id) >= ?";
    $params[] = $rating;
}

$query .= " GROUP BY c.comic_id";

// Add sorting
switch ($sort) {
    case 'rating':
        $query .= " ORDER BY avg_rating DESC";
        break;
    case 'views':
        $query .= " ORDER BY c.total_views DESC";
        break;
    case 'title':
        $query .= " ORDER BY c.title ASC";
        break;
    default:
        $query .= " ORDER BY c.updated_at DESC";
}

// Add pagination
$totalQuery = "SELECT COUNT(DISTINCT c.comic_id) as total FROM ($query) as subquery";
$total = fetchOne($totalQuery, $params)['total'];
$totalPages = ceil($total / $perPage);

$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = ($page - 1) * $perPage;

// Execute final query
$comics = fetchAll($query, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Advanced Search - KomikVerse</title>
    <?php include 'includes/head.php'; ?>
</head>
<body class="bg-darker text-white">
    <?php include 'includes/navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Search Form -->
        <form id="searchForm" class="bg-dark border border-wine/30 rounded-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Search Input -->
                <div>
                    <label class="block text-gray-400 mb-2">Search</label>
                    <input type="text" 
                           name="q" 
                           value="<?= htmlspecialchars($search) ?>"
                           class="w-full bg-darker border border-wine/30 rounded p-2 text-white"
                           placeholder="Search titles...">
                </div>

                <!-- Genre Filter -->
                <div>
                    <label class="block text-gray-400 mb-2">Genres</label>
                    <div class="relative">
                        <button type="button"
                                id="genreDropdown"
                                class="w-full bg-darker border border-wine/30 rounded p-2 text-left flex justify-between items-center">
                            <span><?= !empty($selectedGenres) ? count($selectedGenres) . ' selected' : 'Select genres' ?></span>
                            <span>▼</span>
                        </button>
                        <div id="genreList" 
                             class="absolute z-10 w-full mt-1 bg-darker border border-wine/30 rounded-lg p-2 hidden">
                            <div class="max-h-48 overflow-y-auto">
                                <?php foreach ($genres as $genre): ?>
                                    <label class="flex items-center p-2 hover:bg-wine/20 rounded">
                                        <input type="checkbox" 
                                               name="genres[]" 
                                               value="<?= htmlspecialchars($genre['name']) ?>"
                                               <?= in_array($genre['name'], $selectedGenres) ? 'checked' : '' ?>
                                               class="mr-2">
                                        <?= htmlspecialchars($genre['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-gray-400 mb-2">Status</label>
                    <select name="status" 
                            class="w-full bg-darker border border-wine/30 rounded p-2 text-white">
                        <option value="">All</option>
                        <option value="ongoing" <?= $status === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>

                <!-- Rating Filter -->
                <div>
                    <label class="block text-gray-400 mb-2">Minimum Rating</label>
                    <select name="rating" 
                            class="w-full bg-darker border border-wine/30 rounded p-2 text-white">
                        <option value="">Any</option>
                        <option value="4.5" <?= $rating === '4.5' ? 'selected' : '' ?>>4.5+ ★</option>
                        <option value="4" <?= $rating === '4' ? 'selected' : '' ?>>4+ ★</option>
                        <option value="3" <?= $rating === '3' ? 'selected' : '' ?>>3+ ★</option>
                    </select>
                </div>
            </div>

            <!-- Sort Options -->
            <div class="mt-6 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <label class="text-gray-400">Sort by:</label>
                    <select name="sort" 
                            class="bg-darker border border-wine/30 rounded p-2 text-white">
                        <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest Update</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating</option>
                        <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>Popularity</option>
                        <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title</option>
                    </select>
                </div>
                <button type="submit" 
                        class="px-6 py-2 bg-flame text-white rounded hover:bg-crimson transition-colors">
                    Apply Filters
                </button>
            </div>
        </form>

        <!-- Results Count -->
        <div class="text-gray-400 mb-6">
            Found <?= $total ?> comics
        </div>

        <!-- Comics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <?php foreach ($comics as $comic): ?>
                <a href="comic-detail.php?id=<?= $comic['comic_id'] ?>" 
                   class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                    <div class="relative aspect-[3/4]">
                        <img src="assets/cover/<?= htmlspecialchars($comic['cover_image']) ?>" 
                             alt="<?= htmlspecialchars($comic['title']) ?>"
                             class="w-full h-full object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-dark to-transparent p-4">
                            <div class="text-sm">
                                <span class="text-flame">★</span>
                                <?= number_format($comic['avg_rating'], 1) ?>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-flame mb-1">
                            <?= htmlspecialchars($comic['title']) ?>
                        </h3>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <?php 
                            $comicGenres = explode(',', $comic['genres']);
                            $firstTwoGenres = array_slice($comicGenres, 0, 2);
                            foreach ($firstTwoGenres as $genre): 
                            ?>
                                <span class="bg-wine/20 text-flame text-xs px-2 py-1 rounded">
                                    <?= htmlspecialchars(trim($genre)) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-sm text-gray-400">
                            <?= $comic['chapter_count'] ?> Chapters
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 mt-8">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="px-4 py-2 rounded <?= $i === $page ? 'bg-flame text-white' : 'bg-dark text-gray-400 hover:bg-wine/20' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Genre dropdown toggle
        const genreDropdown = document.getElementById('genreDropdown');
        const genreList = document.getElementById('genreList');

        genreDropdown.addEventListener('click', () => {
            genreList.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!genreDropdown.contains(e.target) && !genreList.contains(e.target)) {
                genreList.classList.add('hidden');
            }
        });

        // Update selected genres count
        const genreCheckboxes = document.querySelectorAll('input[name="genres[]"]');
        genreCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const selectedCount = Array.from(genreCheckboxes).filter(cb => cb.checked).length;
                genreDropdown.querySelector('span').textContent = 
                    selectedCount > 0 ? `${selectedCount} selected` : 'Select genres';
            });
        });
    </script>
</body>
</html> 