<style>
  .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgb(159, 141, 207);
    padding: 15px 30px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 20px rgb(172, 156, 201);
    direction: rtl;
  }

  .navbar .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.6em;
    font-weight: bold;
    color: #fff;
    cursor: pointer;
    transition: transform 0.3s;
  }

  .navbar .logo i {
    font-size: 24px;
    color: white;
    animation: glowLogo 3s ease-in-out infinite alternate;
  }

  @keyframes glowLogo {
    from { text-shadow: 0 0 10px white; }
    to   { text-shadow: 0 0 20px rgb(201, 153, 230); }
  }

  .nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .nav-links a {
    text-decoration: none;
    color: #eee;
    font-weight: 500;
    position: relative;
    transition: transform 0.2s;
  }

  .nav-links a::after {
    content: '';
    display: block;
    width: 0;
    height: 2px;
    background: white;
    transition: width 0.3s;
    position: absolute;
    bottom: -5px;
    left: 0;
  }

  .nav-links a:hover::after {
    width: 100%;
  }

  .nav-links a:hover {
    color: rgb(51, 7, 105);
    transform: scale(1.1);
  }

  .menu-toggle {
    display: none;
    font-size: 2em;
    color: #fff;
    cursor: pointer;
    transition: transform 0.3s;
  }

  .menu-toggle:hover {
    transform: rotate(90deg);
  }

  @media (max-width: 768px) {
    .nav-links {
      display: none;
      flex-direction: column;
      background: rgb(160, 126, 192);
      position: absolute;
      top: 70px;
      right: 0;
      width: 220px;
      padding: 20px;
      border-radius: 0 0 0 20px;
      box-shadow: -4px 4px 20px rgba(0,0,0,0.7);
    }

    .nav-links.active {
      display: flex;
    }

    .menu-toggle {
      display: block;
    }
  }
</style>

<nav class="navbar">
  <div class="logo">
    <i class="fas fa-book-reader"></i>
    <span>متاهة</span>
  </div>

  <div class="menu-toggle">
    <i class="fas fa-bars"></i>
  </div>

  <div class="nav-links">
    <a href="profile.php">صفحتي</a>
    <a href="index.php">الرئيسية</a>
    <a href="favorite.php">المفضلة</a>
    <a href="recommend.php">التوصيات</a>
    <a href="logout.php">خروج</a>
  </div>
</nav>

<script>
  const menuToggle = document.querySelector('.menu-toggle');
  const navLinks = document.querySelector('.nav-links');

  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
  });
</script>
