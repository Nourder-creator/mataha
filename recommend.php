<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* 🔷 Step 1: Get all ratings */
$ratingsQuery = "SELECT user_id, book_id, rating FROM ratings";
$ratingsResult = mysqli_query($conn, $ratingsQuery);

$userRatings = [];
while ($row = mysqli_fetch_assoc($ratingsResult)) {
    $userRatings[$row['user_id']][$row['book_id']] = $row['rating'];
}

/* 🔷 Step 1.5: Add implicit ratings from favorites (5), reminders (3), user_reads (4) */

// Favorites
$fav_stmt = $conn->prepare("SELECT user_id, book_id FROM favorites");
$fav_stmt->execute();
$fav_result = $fav_stmt->get_result();
while ($row = $fav_result->fetch_assoc()) {
    $uid = $row['user_id'];
    $bid = $row['book_id'];
    if (!isset($userRatings[$uid][$bid])) {
        $userRatings[$uid][$bid] = 5;
    }
}

// Reminders
$rem_stmt = $conn->prepare("SELECT user_id, book_id FROM reminders");
$rem_stmt->execute();
$rem_result = $rem_stmt->get_result();
while ($row = $rem_result->fetch_assoc()) {
    $uid = $row['user_id'];
    $bid = $row['book_id'];
    if (!isset($userRatings[$uid][$bid])) {
        $userRatings[$uid][$bid] = 3;
    }
}

// User Reads
$read_stmt = $conn->prepare("SELECT user_id, book_id FROM user_reads");
$read_stmt->execute();
$read_result = $read_stmt->get_result();
while ($row = $read_result->fetch_assoc()) {
    $uid = $row['user_id'];
    $bid = $row['book_id'];
    if (!isset($userRatings[$uid][$bid])) {
        $userRatings[$uid][$bid] = 4;
    }
}

/* 🔷 Step 2: Cosine Similarity Collaborative */
function cosineSimilarity($vecA, $vecB) {
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;
    $allKeys = array_unique(array_merge(array_keys($vecA), array_keys($vecB)));
    foreach ($allKeys as $key) {
        $a = $vecA[$key] ?? 0;
        $b = $vecB[$key] ?? 0;
        $dotProduct += $a * $b;
        $normA += $a * $a;
        $normB += $b * $b;
    }
    return ($normA && $normB) ? $dotProduct / (sqrt($normA) * sqrt($normB)) : 0;
}

$similarities = [];
foreach ($userRatings as $otherUserId => $ratings) {
    if ($otherUserId == $user_id) continue;
    $sim = cosineSimilarity($userRatings[$user_id] ?? [], $ratings);
    if ($sim > 0) {
        $similarities[$otherUserId] = $sim;
    }
}
arsort($similarities);
$topUsers = array_slice($similarities, 0, 5, true);

/* 🔷 Step 3: Build liked categories from all sources */
$likedCategories = [];
$userRatedBookIds = array_keys($userRatings[$user_id] ?? []);

// Ratings
if (!empty($userRatedBookIds)) {
    $idList = implode(',', array_map('intval', $userRatedBookIds));
    $query = "SELECT id, category FROM books WHERE id IN ($idList)";
    $res = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($res)) {
        $cat = $row['category'];
        $score = $userRatings[$user_id][$row['id']];
        $likedCategories[$cat] = ($likedCategories[$cat] ?? 0) + $score;
    }
}

// Favorites
$fav_stmt = $conn->prepare("SELECT book_id FROM favorites WHERE user_id = ?");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_result = $fav_stmt->get_result();
while ($row = $fav_result->fetch_assoc()) {
    $bookId = $row['book_id'];
    $q = mysqli_prepare($conn, "SELECT category FROM books WHERE id = ?");
    mysqli_stmt_bind_param($q, "i", $bookId);
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);
    $bookRow = mysqli_fetch_assoc($result);
    if ($bookRow) {
        $cat = $bookRow['category'];
        $likedCategories[$cat] = ($likedCategories[$cat] ?? 0) + 5;
    }
}

// Reminders
$rem_stmt = $conn->prepare("SELECT book_id FROM reminders WHERE user_id = ?");
$rem_stmt->bind_param("i", $user_id);
$rem_stmt->execute();
$rem_result = $rem_stmt->get_result();
while ($row = $rem_result->fetch_assoc()) {
    $bookId = $row['book_id'];
    $q = mysqli_prepare($conn, "SELECT category FROM books WHERE id = ?");
    mysqli_stmt_bind_param($q, "i", $bookId);
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);
    $bookRow = mysqli_fetch_assoc($result);
    if ($bookRow) {
        $cat = $bookRow['category'];
        $likedCategories[$cat] = ($likedCategories[$cat] ?? 0) + 3;
    }
}

// User Reads
$read_stmt = $conn->prepare("SELECT book_id FROM user_reads WHERE user_id = ?");
$read_stmt->bind_param("i", $user_id);
$read_stmt->execute();
$read_result = $read_stmt->get_result();
while ($row = $read_result->fetch_assoc()) {
    $bookId = $row['book_id'];
    $q = mysqli_prepare($conn, "SELECT category FROM books WHERE id = ?");
    mysqli_stmt_bind_param($q, "i", $bookId);
    mysqli_stmt_execute($q);
    $result = mysqli_stmt_get_result($q);
    $bookRow = mysqli_fetch_assoc($result);
    if ($bookRow) {
        $cat = $bookRow['category'];
        $likedCategories[$cat] = ($likedCategories[$cat] ?? 0) + 4;
    }
}

/* 🔷 Step 4: Collaborative recommendations with Content-Based weighting */
$recommendedBooks = [];
foreach ($topUsers as $otherUserId => $similarity) {
    foreach ($userRatings[$otherUserId] as $bookId => $rating) {
        if (isset($userRatings[$user_id][$bookId])) continue;

        $q = mysqli_prepare($conn, "SELECT category FROM books WHERE id = ?");
        mysqli_stmt_bind_param($q, "i", $bookId);
        mysqli_stmt_execute($q);
        $result = mysqli_stmt_get_result($q);
        $row = mysqli_fetch_assoc($result);
        $category = $row ? $row['category'] : null;

        $catWeight = isset($likedCategories[$category]) ? 1 : 0.3;

        if (!isset($recommendedBooks[$bookId])) {
            $recommendedBooks[$bookId] = ['score' => 0, 'weight' => 0, 'count' => 0];
        }
        $recommendedBooks[$bookId]['score'] += $rating * $similarity * $catWeight;
        $recommendedBooks[$bookId]['weight'] += abs($similarity * $catWeight);
        $recommendedBooks[$bookId]['count'] += 1;
    }
}

/* 🔷 Step 5: Add pure content-based books */
if (!empty($likedCategories)) {
    $likedCats = array_keys($likedCategories);
    $placeholders = implode(',', array_fill(0, count($likedCats), '?'));
    $types = str_repeat('s', count($likedCats));

    $stmt = $conn->prepare("SELECT * FROM books WHERE category IN ($placeholders)");
    $stmt->bind_param($types, ...$likedCats);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (!isset($userRatings[$user_id][$row['id']]) && !isset($recommendedBooks[$row['id']])) {
            $recommendedBooks[$row['id']] = [
                'score' => $likedCategories[$row['category']] ?? 1,
                'weight' => 1,
                'count' => 1
            ];
        }
    }
}

/* 🔷 Step 6: Compute final scores */
$finalScores = [];
foreach ($recommendedBooks as $bookId => $data) {
    if ($data['weight'] > 0) {
        $finalScores[$bookId] = $data['score'] / $data['weight'];
    }
}
arsort($finalScores);

/* 🔷 Step 7: Fetch book details */
$bookDetails = [];
$bookIds = array_keys($finalScores);
if (!empty($bookIds)) {
    $idList = implode(',', array_map('intval', $bookIds));
    $query = "SELECT * FROM books WHERE id IN ($idList)";
    $res = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($res)) {
        $bookDetails[$row['id']] = $row;
    }
}

/* 🔷 Step 8: Get favorites for heart icons */
$fav_stmt = $conn->prepare("SELECT book_id FROM favorites WHERE user_id = ?");
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$fav_result = $fav_stmt->get_result();
$favorites = [];
while ($row = $fav_result->fetch_assoc()) {
    $favorites[] = $row['book_id'];
}
?>
<!DOCTYPE html><html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>توصيات</title>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="style/stylereco.css">
</head>
<body>
  <?php require 'nav.php'; ?>
  
 <header class="welcome-header">
  <div class="welcome-content">
    <div class="welcome-text">
      <h1> مرحبًا بك في <span>متاهة</span></h1>
      <p>في متاهة، لا تبحث عن الكتاب...
بل دع المتاهة تُرشدك إلى ما يُشبهك.

نُراقب اختياراتك، نقرأ ذوقك،
ثم نفتح لك بابًا لم يخطر ببالك...

رواية تشبه ليلك.
فكرة تُشبه قلبك.
وكتاب ينتظرك خلف منعطفٍ ما.</p>
    
    </div>
    <img src="img/science-fiction.png" alt="مرحبًا" class="welcome-icon">
  </div>
</header>


<script>
const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

menuToggle.addEventListener('click', () => {
  navLinks.classList.toggle('active');
});
</script>

<?php if (empty($finalScores)): ?>
    <p>🙁 لا توجد توصيات حالياً. قم بتقييم المزيد من الكتب!</p>
<?php else: ?>
<div class="book-grid">
    <?php foreach ($finalScores as $bookId => $score): ?>
        <?php if (isset($bookDetails[$bookId])): $book = $bookDetails[$bookId]; ?>
            <div class="book">
                 <form method="post" action="toggle_favorite.php" class="favorite-form">
          <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
          <button type="submit">
            <i class="fa<?= in_array($book['id'], $favorites) ? 's' : 'r' ?> fa-heart" style="color: <?= in_array($book['id'], $favorites) ? 'red' : '#bbb' ?>;"></i>
          </button>
        </form>
                 <?php if (!empty($book['cover_image'])): ?>
      <img src="<?= htmlspecialchars($book['cover_image']) ?>" alt="غلاف الكتاب">
    <?php else: ?>
      <img src="default_cover.jpg" alt="غلاف افتراضي">
    <?php endif; ?>
                <h4><?= htmlspecialchars($book['title']) ?></h4>
                <p><?= nl2br(substr($book['description'], 0, 150)) ?>...</p>
                <p>⭐ التوصية: <?= number_format($score, 2) ?>/5</p>
                <a href="<?= htmlspecialchars($book['file_path']) ?>" target="_blank">📖 قراءة</a>
            </div>
        <?php endif; ?>
    <?php endforeach; ?></div>
<?php endif; ?>

  <script>
document.querySelectorAll('.favorite-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // ما يخليش يعاود تحميل الصفحة

        const formData = new FormData(form);
        const heartIcon = form.querySelector('i');

        fetch('toggle_favorite.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'added') {
                heartIcon.classList.remove('far'); // fa-regular
                heartIcon.classList.add('fas');    // fa-solid
                heartIcon.style.color = 'red';
            } else if (data.status === 'removed') {
                heartIcon.classList.remove('fas');
                heartIcon.classList.add('far');
                heartIcon.style.color = '#bbb';
            }
        })
        .catch(err => {
            console.error('خطأ في AJAX:', err);
        });
    });
});
</script>
 <?php  require 'reminder.php' ?>
</body>
</html>