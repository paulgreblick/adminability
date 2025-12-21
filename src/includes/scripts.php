<?php
/**
 * Scripts Include - JavaScript includes
 *
 * Optional variables:
 * - $custom_scripts - Additional inline scripts or script tags
 */
?>
<!-- Main JavaScript -->
<script src="/assets/js/scripts.js"></script>

<?php if (!empty($custom_scripts)): ?>
<?= $custom_scripts ?>
<?php endif; ?>

</body>
</html>
