<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'MoneyTrack';
$activePage = $activePage ?? 'dashboard';
$currentUser = current_user();
?><!doctype html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle) ?> · MoneyTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
<header class="topbar">
  <a class="brand" href="dashboard.php"><span class="brand-mark"><i class="bi bi-arrow-up-right"></i></span><span>MoneyTrack</span></a>
  <nav class="desktop-nav" aria-label="Primary">
    <?php foreach ([['dashboard','Home','dashboard.php','bi-house'],['activity','Activity','activity.php','bi-receipt'],['insights','Insights','insights.php','bi-stars'],['categories','Categories','categories.php','bi-tags'],['settings','Settings','settings.php','bi-gear']] as $item): ?>
      <a class="nav-link <?= $activePage === $item[0] ? 'active' : '' ?>" href="<?= $item[2] ?>"><i class="bi <?= $item[3] ?>"></i><?= $item[1] ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="profile-menu"><span class="avatar"><?= e(strtoupper(substr($currentUser['name'] ?? 'M', 0, 1))) ?></span><span class="d-none d-md-inline"><?= e($currentUser['name'] ?? 'Account') ?></span><a class="icon-button" href="logout.php" aria-label="Log out"><i class="bi bi-box-arrow-right"></i></a></div>
</header>
<main class="page-wrap">