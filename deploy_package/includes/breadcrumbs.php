<?php
/**
 * Breadcrumb Component
 * Menampilkan breadcrumb navigation untuk better UX
 *
 * @param array $breadcrumbs Array of breadcrumb items dengan format ['label' => 'Text', 'url' => 'URL']
 */
if (!isset($breadcrumbs) || !is_array($breadcrumbs)) {
    $breadcrumbs = [];
}
?>
<?php if (!empty($breadcrumbs)): ?>
<nav class="breadcrumbs" aria-label="Breadcrumb navigation">
    <div class="container">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index === count($breadcrumbs) - 1): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($crumb['label']) ?></li>
                <?php else: ?>
                    <li class="breadcrumb-item">
                        <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>
<style>
.breadcrumbs {
    background: #f8f9fa;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 1rem;
}
.breadcrumb-list {
    list-style: none;
    display: flex;
    align-items: center;
    margin: 0;
    padding: 0;
    font-size: 0.85rem;
}
.breadcrumb-item {
    display: flex;
    align-items: center;
}
.breadcrumb-item:not(:last-child)::after {
    content: '/';
    margin: 0 0.5rem;
    color: #999;
}
.breadcrumb-item a {
    color: #2980b9;
    text-decoration: none;
}
.breadcrumb-item a:hover {
    text-decoration: underline;
}
.breadcrumb-item.active {
    color: #555;
    font-weight: 500;
}
@media (prefers-color-scheme: dark) {
    .breadcrumbs {
        background: #1a1a2e;
        border-bottom-color: #555;
    }
    .breadcrumb-item a {
        color: #4cc9f0;
    }
    .breadcrumb-item.active {
        color: #e8e8e8;
    }
}
body.dark-mode .breadcrumbs {
    background: #1a1a2e;
    border-bottom-color: #555;
}
body.dark-mode .breadcrumb-item a {
    color: #4cc9f0;
}
body.dark-mode .breadcrumb-item.active {
    color: #e8e8e8;
}
</style>
<?php endif; ?>
