<?php
/**
 * Page Template
 * Copy this file and rename for new pages
 */

// Page-specific variables
$page_title = 'Page Title | Site Name';
$page_description = 'Page description for SEO';
$page_keywords = 'keyword1, keyword2, keyword3';
$page_author = 'Author Name';
$current_page = 'page-slug'; // For nav active state

// Optional variables
$site_name = 'Site Name';
// $business_email = 'email@example.com';
// $business_phone = '(555) 555-5555';
// $og_image = '/assets/images/og-image.jpg';

// Include head
include 'includes/head.php';

// Include navigation
include 'includes/nav.php';
?>

<main>
    <!-- Page content goes here -->
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900">Page Title</h1>
        <p class="mt-4 text-gray-600">Page content goes here.</p>
    </div>
</main>

<?php
// Include footer
include 'includes/footer.php';

// Include scripts
include 'includes/scripts.php';
?>
