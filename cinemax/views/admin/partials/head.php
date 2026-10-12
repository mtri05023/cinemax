<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quản lý người dùng - Cinemax Admin</title>
<link rel="stylesheet" href="../../public/assets/css/style.css">
<link rel="stylesheet" href="../../public/assets/css/admin.css?v=<?= filemtime(__DIR__.'/../../../public/assets/css/admin.css') ?>">
<link rel="stylesheet" href="../../public/assets/css/pagination.css">
<?php foreach (['dashboard'=>'dashboard.css','seats'=>'seats_admin.css','movies'=>'movies_admin.css','permissions'=>'permission.css','bookings'=>'bookings_admin.css','404'=>'404.css'] as $cssPage=>$cssFile): ?>
<?php if ($page===$cssPage): ?><link rel="stylesheet" href="../../public/assets/css/<?= $cssFile ?>?v=<?= filemtime(__DIR__.'/../../../public/assets/css/'.$cssFile) ?>"><?php endif; ?>
<?php endforeach; ?>
<?php if ($page==='shows'): ?><link rel="stylesheet" href="../../public/assets/css/shows_admin.css?v=<?= filemtime(__DIR__.'/../../../public/assets/css/shows_admin.css') ?>"><?php endif; ?>
<link rel="stylesheet" href="../../public/assets/css/admin-responsive.css?v=<?= filemtime(__DIR__.'/../../../public/assets/css/admin-responsive.css') ?>">
<link rel="stylesheet" href="../../public/assets/fontawesome/css/all.min.css">

<script src="../../public/assets/js/all_effects.js"></script>
<script src="../../public/assets/js/admin-layout.js?v=<?= filemtime(__DIR__.'/../../../public/assets/js/admin-layout.js') ?>" defer></script>
<script src="../../public/assets/js/pagination.js"></script>
<?php if ($page==='shows'): ?><script src="../../public/assets/js/shows_admin.js?v=<?= filemtime(__DIR__.'/../../../public/assets/js/shows_admin.js') ?>" defer></script><?php endif; ?>
<?php foreach (['roles'=>'roles_admin.js','users'=>'users_admin.js','permissions'=>'permissions.js','bookings'=>'booking_admin.js'] as $scriptPage=>$scriptFile): ?>
<?php if ($page===$scriptPage): ?><script src="../../public/assets/js/<?= $scriptFile ?>?v=<?= filemtime(__DIR__.'/../../../public/assets/js/'.$scriptFile) ?>" defer></script><?php endif; ?>
<?php endforeach; ?>
<?php if ($page==='promotions'): ?><script src="../../public/assets/js/promotions_admin.js?v=<?= filemtime(__DIR__.'/../../../public/assets/js/promotions_admin.js') ?>" defer></script><?php endif; ?>
