<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "BDD");
if (!$conn) die("Échec de connexion : " . mysqli_connect_error());

$msg = "";

// Ajouter livre
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_book'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $file_path = mysqli_real_escape_string($conn, $_POST['file_path']);
    $cover_image = mysqli_real_escape_string($conn, $_POST['cover_image']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $sql = "INSERT INTO books (title, category, file_path, cover_image, description, created_at)
            VALUES ('$title', '$category', '$file_path', '$cover_image', '$description', NOW())";

    if (mysqli_query($conn, $sql)) {
        $msg = "✅ Livre ajouté avec succès.";
    } else {
        $msg = "❌ Erreur : " . mysqli_error($conn);
    }
}

// Derniers 5 utilisateurs
$result_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$last_users = [];
if ($result_users) {
    while ($row = mysqli_fetch_assoc($result_users)) {
        $last_users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<!-- 🔷 Navbar -->
<nav class="navbar">
  <div class="container">
    <a class="navbar-brand" href="#">📚 Admin Panel</a>
    <div>
      <span class="welcome">👋 Bonjour, Admin</span>
      <button id="show-add" class="btn btn-light ms-3">➕ Ajouter Livre</button>
    </div>
  </div>
</nav>

<div class="container py-4">

  <?php if($msg): ?>
  <div class="alert alert-info animate__animated animate__fadeInDown"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- 🔷 Formulaire d'ajout -->
  <div class="form-section card p-4 mb-4 animate__animated animate__fadeInUp">
    <h4>Ajouter un Livre</h4>
    <form method="post">
      <input type="text" name="title" class="form-control mb-2" placeholder="Titre" required>
      <input type="text" name="category" class="form-control mb-2" placeholder="Catégorie">
      <input type="text" name="file_path" class="form-control mb-2" placeholder="Chemin du fichier">
      <input type="text" name="cover_image" class="form-control mb-2" placeholder="Image de couverture">
      <textarea name="description" class="form-control mb-3" placeholder="Description"></textarea>
      <button type="submit" name="add_book" class="btn btn-success">Ajouter</button>
    </form>
  </div>

  <!-- 🔷 Derniers 5 utilisateurs -->
  <h4 class="mb-3">🕒 Derniers 5 utilisateurs</h4>
  <div class="list-group animate__animated animate__fadeInUp">
    <?php foreach($last_users as $u): ?>
    <div class="list-group-item d-flex align-items-center user-card" 
         data-name="<?= htmlspecialchars($u['full_name']) ?>"
         data-contact="<?= htmlspecialchars($u['contact']) ?>"
         data-created="<?= htmlspecialchars($u['created_at']) ?>"
         data-photo="<?= htmlspecialchars($u['photo']) ?>"
         data-points="<?= htmlspecialchars($u['points']) ?>"
         data-badge="<?= htmlspecialchars($u['badge']) ?>">
      <img src="<?= htmlspecialchars($u['photo']) ?>" class="user-photo me-3">
      <div>
        <strong><?= htmlspecialchars($u['full_name']) ?></strong><br>
        <small><?= htmlspecialchars($u['contact']) ?></small>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(count($last_users) == 0): ?>
    <div class="list-group-item">Aucun utilisateur trouvé.</div>
    <?php endif; ?>
  </div>

</div>

<!-- 🔷 Modal Utilisateur -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Détails de l'utilisateur</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <img id="modalPhoto" src="" class="rounded-circle mb-3" width="60" height="60">
        <p><strong>Nom:</strong> <span id="modalName"></span></p>
        <p><strong>Contact:</strong> <span id="modalContact"></span></p>
        <p><strong>Date:</strong> <span id="modalCreated"></span></p>
        <p><strong>Points:</strong> <span id="modalPoints"></span></p>
        <p><strong>Badge:</strong> <span id="modalBadge"></span></p>
      </div>
    </div>
  </div>
</div>

<!-- 🔷 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('show-add').addEventListener('click', function(){
  const form = document.querySelector('.form-section');
  form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
});

document.querySelectorAll('.user-card').forEach(card => {
  card.addEventListener('click', () => {
    document.getElementById('modalPhoto').src = card.dataset.photo;
    document.getElementById('modalName').textContent = card.dataset.name;
    document.getElementById('modalContact').textContent = card.dataset.contact;
    document.getElementById('modalCreated').textContent = card.dataset.created;
    document.getElementById('modalPoints').textContent = card.dataset.points;
    document.getElementById('modalBadge').textContent = card.dataset.badge;
    
    let modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
  });
});
</script>
<style>body {
  background: #f8f9fa;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: #333;
}

.navbar {
  background: #343a40;
  padding: 10px 20px;
}

.navbar-brand {
  color: #fff;
  font-weight: 600;
  font-size: 20px;
}

.navbar .welcome {
  color: #ccc;
  font-size: 15px;
}

.btn-light {
  background: #6c757d;
  color: #fff;
  border: none;
  transition: 0.3s;
}

.btn-light:hover {
  background: #5a6268;
}

.container h4 {
  font-weight: 600;
  border-bottom: 2px solid #dee2e6;
  padding-bottom: 5px;
  margin-bottom: 20px;
}

.form-section {
  display: none;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.form-control, textarea {
  border-radius: 4px;
  border: 1px solid #ced4da;
}

.btn-success {
  background: #28a745;
  border: none;
}

.btn-success:hover {
  background: #218838;
}

.list-group-item {
  background: #fff;
  border: 1px solid #dee2e6;
  margin-bottom: 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.list-group-item:hover {
  background: #f1f3f5;
}

.user-photo {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid #ccc;
}

.modal-content {
  border-radius: 8px;
}

.modal-header {
  background: #343a40;
  color: #fff;
  border-bottom: none;
}

.modal-body p {
  margin-bottom: 10px;
}

/* Animations */
@import url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');
</style>
</body>
</html>
