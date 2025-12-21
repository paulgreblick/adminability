<?php
/**
 * Head Include - SEO optimized with Open Graph and Twitter Cards
 *
 * Required variables (set before including):
 * - $page_title
 * - $page_description
 * - $page_keywords
 * - $page_author
 *
 * Optional variables:
 * - $page_lang (default: 'en')
 * - $og_title, $og_description, $og_image
 * - $twitter_title, $twitter_description, $twitter_image
 * - $site_name, $business_phone, $business_email
 * - $canonical_url
 * - $theme_color
 * - $custom_head
 */

// Defaults
$page_lang = $page_lang ?? 'en';
$site_name = $site_name ?? '';
$theme_color = $theme_color ?? '#ffffff';

// Open Graph defaults
$og_title = $og_title ?? $page_title;
$og_description = $og_description ?? $page_description;
$og_image = $og_image ?? '';

// Twitter Card defaults
$twitter_title = $twitter_title ?? $og_title;
$twitter_description = $twitter_description ?? $og_description;
$twitter_image = $twitter_image ?? $og_image;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($page_lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    <meta name="author" content="<?= htmlspecialchars($page_author) ?>">

    <?php if (!empty($canonical_url)): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <?php endif; ?>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($og_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($og_description) ?>">
    <?php if (!empty($og_image)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <?php endif; ?>
    <?php if (!empty($site_name)): ?>
    <meta property="og:site_name" content="<?= htmlspecialchars($site_name) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($twitter_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($twitter_description) ?>">
    <?php if (!empty($twitter_image)): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($twitter_image) ?>">
    <?php endif; ?>

    <!-- Theme Color -->
    <meta name="theme-color" content="<?= htmlspecialchars($theme_color) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <?php if (!empty($custom_head)): ?>
    <?= $custom_head ?>
    <?php endif; ?>
</head>
<body>
