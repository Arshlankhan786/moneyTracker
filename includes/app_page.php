<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
require_auth();
$pageTitle = $pageTitle ?? 'MoneyTrack';
$activePage = $activePage ?? 'dashboard';
$userId = current_user_id();
$user = current_user();
[$from, $to] = month_range($_GET['from'] ?? null, $_GET['to'] ?? null);
$summary = period_summary($userId, $from, $to);
$balance = all_time_balance($userId);
$categories = user_categories($userId);
$topCategories = array_values(array_filter($categories, fn($c) => $c['parent_id'] === null));
$incomeCategories = array_values(array_filter($topCategories, fn($c) => $c['type'] === 'income'));
$expenseCategories = array_values(array_filter($topCategories, fn($c) => $c['type'] === 'expense'));
$childrenMap = [];
foreach ($categories as $_c) { if ($_c['parent_id'] !== null) $childrenMap[(int)$_c['parent_id']][] = $_c; }
$transactions = [];
if ($activePage === 'activity' || $activePage === 'dashboard' || $activePage === 'monthly') {
    $stmt = database()->prepare('SELECT t.*, c.name AS category_name, c.icon, c.color, c.parent_id AS cat_parent_id, pc.name AS parent_name, pc.icon AS parent_icon FROM transactions t JOIN categories c ON c.id=t.category_id LEFT JOIN categories pc ON pc.id=c.parent_id WHERE t.user_id=? AND t.transaction_date BETWEEN ? AND ? ORDER BY t.transaction_date DESC, t.created_at DESC LIMIT 50');
    $stmt->execute([$userId, $from, $to]);
    $transactions = $stmt->fetchAll();
}
require __DIR__ . '/header.php';
?>
<?php if ($activePage === 'dashboard'): ?>
<section class="page-heading"><div><p class="eyebrow">PERSONAL MONEY SPACE</p><h1>Good morning, <?= e(explode(' ', $user['name'])[0]) ?> <span class="wave">👋</span></h1><p class="subheading">Here's what happened with your money.</p></div><button class="period-chip" type="button" data-period-toggle><i class="bi bi-calendar3"></i><?= e(readable_date($from)) ?> – <?= e(readable_date($to)) ?><i class="bi bi-chevron-down"></i></button></section>
<section class="balance-hero ambient-income"><div class="hero-copy"><p class="eyebrow">ALL-TIME CURRENT BALANCE</p><div class="balance-value" data-balance><?= e(rupees($balance)) ?></div><p class="hero-note">What you have across all recorded activity</p></div><div class="period-net"><span class="eyebrow">THIS PERIOD NET</span><strong class="<?= $summary['net'] >= 0 ? 'text-income' : 'text-expense' ?>"><?= e(($summary['net'] >= 0 ? '+' : '') . rupees($summary['net'])) ?></strong><small><?= e(readable_date($from)) ?> — <?= e(readable_date($to)) ?></small></div></section>
<section class="metric-row"><div class="metric"><span class="metric-icon income"><i class="bi bi-arrow-down-left"></i></span><div><span>Received</span><strong class="text-income"><?= e(rupees($summary['income'])) ?></strong></div></div><div class="metric"><span class="metric-icon expense"><i class="bi bi-arrow-up-right"></i></span><div><span>Spent</span><strong class="text-expense"><?= e(rupees($summary['expense'])) ?></strong></div></div><div class="metric"><span class="metric-icon insight"><i class="bi bi-activity"></i></span><div><span>Transactions</span><strong><?= count($transactions) ?></strong></div></div></section>
<section class="action-grid"><button class="money-action income-action" type="button" data-open-transaction="income"><span class="action-icon"><i class="bi bi-plus-lg"></i></span><span><b>Money came in</b><small>Record money you received</small></span><i class="bi bi-arrow-up-right arrow"></i></button><button class="money-action expense-action" type="button" data-open-transaction="expense"><span class="action-icon"><i class="bi bi-dash-lg"></i></span><span><b>Money went out</b><small>Record something you spent</small></span><i class="bi bi-arrow-up-right arrow"></i></button></section>
<section class="content-grid"><div class="section-block"><div class="section-head"><div><p class="eyebrow">RECENT ACTIVITY</p><h2>Your money story</h2></div><a class="text-link" href="activity.php">See all <i class="bi bi-arrow-up-right"></i></a></div><?php if (!$transactions): ?><div class="empty-state compact"><div class="empty-orbit"><i class="bi bi-stars"></i></div><h3>Your money story starts here.</h3><p>Add your first income or expense to see it unfold.</p></div><?php else: ?><div class="timeline"><?php foreach (array_slice($transactions, 0, 5) as $transaction): ?><div class="timeline-item"><span class="timeline-dot <?= $transaction['type'] === 'income' ? 'income-dot' : 'expense-dot' ?>"></span><div class="timeline-main"><div><b><?= e(!empty($transaction['parent_name']) ? $transaction['parent_name'] . ' › ' . $transaction['category_name'] : $transaction['category_name']) ?></b><small><?= e($transaction['note'] ?: 'No note added') ?></small></div><strong class="<?= $transaction['type'] === 'income' ? 'text-income' : 'text-expense' ?>"><?= $transaction['type'] === 'income' ? '+' : '−' ?><?= e(rupees($transaction['amount'])) ?></strong></div><time><?= e(readable_date($transaction['transaction_date'])) ?></time></div><?php endforeach; ?></div><?php endif; ?></div><aside class="snapshot-panel"><p class="eyebrow">TODAY'S SNAPSHOT</p><h2>Keep your rhythm</h2><p class="snapshot-copy">Small entries create a clearer picture over time.</p><div class="snapshot-line"><span>Period received</span><b class="text-income"><?= e(rupees($summary['income'])) ?></b></div><div class="snapshot-line"><span>Period spent</span><b class="text-expense"><?= e(rupees($summary['expense'])) ?></b></div><div class="snapshot-progress"><span style="width:<?= $summary['income'] > 0 ? min(100, round(($summary['expense'] / $summary['income']) * 100)) : 0 ?>%"></span></div><small><?= $summary['income'] > 0 ? e(round(($summary['expense'] / $summary['income']) * 100) . '% of received money spent') : 'Add activity to unlock your snapshot' ?></small></aside></section>

<?php elseif ($activePage === 'activity'): ?>
<section class="page-heading"><div><p class="eyebrow">YOUR MONEY STORY</p><h1>Activity</h1><p class="subheading">Every inflow and outflow, in one calm place.</p></div><button class="primary-button" data-open-transaction="expense"><i class="bi bi-plus-lg"></i> Add activity</button></section>
<section class="filter-bar"><input class="search-input" type="search" placeholder="Search activity..." data-activity-search><select class="filter-select" data-activity-type><option value="all">All activity</option><option value="income">Money in</option><option value="expense">Money out</option></select><a class="period-chip small" href="?from=<?= e($from) ?>&to=<?= e($to) ?>"><i class="bi bi-calendar3"></i>This period</a></section>
<section class="activity-list" data-activity-list>
<?php if (!$transactions): ?>
<div class="empty-state"><div class="empty-orbit"><i class="bi bi-receipt"></i></div><h2>Nothing here yet.</h2><p>Your transactions will appear here once you add them.</p><button class="secondary-button" data-open-transaction="income">Add your first entry</button></div>
<?php else: foreach ($transactions as $transaction): ?>
<article class="activity-item" data-activity-row data-type="<?= e($transaction['type']) ?>" data-search="<?= e(strtolower($transaction['category_name'] . ' ' . ($transaction['parent_name'] ?? '') . ' ' . ($transaction['note'] ?? ''))) ?>">
<div class="category-symbol" style="--category-color:<?= e($transaction['color']) ?>"><?= e($transaction['icon']) ?></div>
<div class="activity-details"><b><?= e(!empty($transaction['parent_name']) ? $transaction['parent_name'] . ' › ' . $transaction['category_name'] : $transaction['category_name']) ?></b><span><?= e($transaction['note'] ?: 'No note added') ?></span><time><?= e(readable_date($transaction['transaction_date'])) ?></time></div>
<strong class="activity-amount <?= $transaction['type'] === 'income' ? 'text-income' : 'text-expense' ?>"><?= $transaction['type'] === 'income' ? '+' : '−' ?><?= e(rupees($transaction['amount'])) ?></strong>
<button class="icon-button subtle" data-edit-transaction='<?= e(json_encode($transaction)) ?>' aria-label="Edit transaction"><i class="bi bi-three-dots"></i></button>
</article>
<?php endforeach; endif; ?>
</section>

<?php elseif ($activePage === 'categories'): ?>
<section class="page-heading">
  <div>
    <p class="eyebrow">MAKE IT YOURS</p>
    <h1>Categories</h1>
    <p class="subheading">Create categories that match your real life.</p>
  </div>
  <button class="primary-button" data-open-category><i class="bi bi-plus-lg"></i> Create category</button>
</section>
<div class="category-columns">
  <section class="category-section">
    <div class="section-head">
      <div><p class="eyebrow text-income">MONEY IN</p><h2>Where does your money come from?</h2></div>
      <button class="text-link button-reset" data-open-category="income">+ Add source</button>
    </div>
    <div class="cat-tree-list">
<?php if (!$incomeCategories): ?>
      <div class="category-empty"><i class="bi bi-plus-circle"></i><span>No income sources yet</span></div>
<?php endif; ?>
<?php foreach ($incomeCategories as $cat):
  $children = $childrenMap[$cat['id']] ?? [];
  $childCount = count($children);
?>
      <div class="cat-tree-item<?= $childCount ? ' has-children' : '' ?>">
        <div class="cat-tree-parent" style="--category-color:<?= e($cat['color']) ?>">
          <span class="category-symbol"><?= e($cat['icon']) ?></span>
          <div class="cat-tree-info">
            <b><?= e($cat['name']) ?></b>
            <small><?= $childCount ? $childCount . ' subcategor' . ($childCount === 1 ? 'y' : 'ies') : 'Income source' ?></small>
          </div>
          <div class="cat-tree-actions">
            <button class="icon-button subtle" data-toggle-tree aria-label="Toggle"><i class="bi bi-chevron-down"></i></button>
            <button class="icon-button subtle" data-edit-category='<?= e(json_encode($cat)) ?>' aria-label="Edit"><i class="bi bi-pencil"></i></button>
            <button class="icon-button subtle" data-archive-cat="<?= $cat['id'] ?>" aria-label="Archive"><i class="bi bi-archive"></i></button>
          </div>
        </div>
        <div class="cat-tree-children" data-tree-children<?= !$childCount ? ' hidden' : '' ?>>
<?php foreach ($children as $child): ?>
          <div class="cat-tree-child">
            <span class="tree-connector"></span>
            <span class="cat-child-icon"><?= e($child['icon']) ?></span>
            <span class="cat-child-name"><?= e($child['name']) ?></span>
            <div class="cat-tree-actions">
              <button class="icon-button subtle" data-edit-category='<?= e(json_encode($child)) ?>' aria-label="Edit"><i class="bi bi-pencil"></i></button>
              <button class="icon-button subtle" data-archive-cat="<?= $child['id'] ?>" aria-label="Archive"><i class="bi bi-archive"></i></button>
            </div>
          </div>
<?php endforeach; ?>
          <button class="cat-add-sub-inline" data-add-sub='<?= e(json_encode(['id' => $cat['id'], 'name' => $cat['name'], 'type' => $cat['type'], 'icon' => $cat['icon'], 'color' => $cat['color']])) ?>'>
            <i class="bi bi-plus"></i> Add subcategory
          </button>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </section>

  <section class="category-section">
    <div class="section-head">
      <div><p class="eyebrow text-expense">MONEY OUT</p><h2>What do you spend money on?</h2></div>
      <button class="text-link button-reset" data-open-category="expense">+ Add category</button>
    </div>
    <div class="cat-tree-list">
<?php if (!$expenseCategories): ?>
      <div class="category-empty"><i class="bi bi-plus-circle"></i><span>No expense categories yet</span></div>
<?php endif; ?>
<?php foreach ($expenseCategories as $cat):
  $children = $childrenMap[$cat['id']] ?? [];
  $childCount = count($children);
?>
      <div class="cat-tree-item<?= $childCount ? ' has-children' : '' ?>">
        <div class="cat-tree-parent" style="--category-color:<?= e($cat['color']) ?>">
          <span class="category-symbol"><?= e($cat['icon']) ?></span>
          <div class="cat-tree-info">
            <b><?= e($cat['name']) ?></b>
            <small><?= $childCount ? $childCount . ' subcategor' . ($childCount === 1 ? 'y' : 'ies') : 'Expense category' ?></small>
          </div>
          <div class="cat-tree-actions">
            <button class="icon-button subtle" data-toggle-tree aria-label="Toggle"><i class="bi bi-chevron-down"></i></button>
            <button class="icon-button subtle" data-edit-category='<?= e(json_encode($cat)) ?>' aria-label="Edit"><i class="bi bi-pencil"></i></button>
            <button class="icon-button subtle" data-archive-cat="<?= $cat['id'] ?>" aria-label="Archive"><i class="bi bi-archive"></i></button>
          </div>
        </div>
        <div class="cat-tree-children" data-tree-children<?= !$childCount ? ' hidden' : '' ?>>
<?php foreach ($children as $child): ?>
          <div class="cat-tree-child">
            <span class="tree-connector"></span>
            <span class="cat-child-icon"><?= e($child['icon']) ?></span>
            <span class="cat-child-name"><?= e($child['name']) ?></span>
            <div class="cat-tree-actions">
              <button class="icon-button subtle" data-edit-category='<?= e(json_encode($child)) ?>' aria-label="Edit"><i class="bi bi-pencil"></i></button>
              <button class="icon-button subtle" data-archive-cat="<?= $child['id'] ?>" aria-label="Archive"><i class="bi bi-archive"></i></button>
            </div>
          </div>
<?php endforeach; ?>
          <button class="cat-add-sub-inline" data-add-sub='<?= e(json_encode(['id' => $cat['id'], 'name' => $cat['name'], 'type' => $cat['type'], 'icon' => $cat['icon'], 'color' => $cat['color']])) ?>'>
            <i class="bi bi-plus"></i> Add subcategory
          </button>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </section>
</div>

<?php elseif ($activePage === 'settings'): ?>
<section class="page-heading"><div><p class="eyebrow">YOUR SPACE</p><h1>Settings</h1><p class="subheading">Keep your MoneyTrack experience feeling like yours.</p></div></section><div class="settings-layout"><section class="settings-card"><div class="settings-card-head"><span class="settings-icon"><i class="bi bi-person"></i></span><div><h2>Profile</h2><p>Your account details</p></div></div><form data-profile-form><label>Full name<input name="name" value="<?= e($user['name']) ?>" required></label><label>Email<input type="email" value="<?= e($user['email']) ?>" disabled></label><button class="secondary-button" type="submit">Save profile</button></form></section><section class="settings-card"><div class="settings-card-head"><span class="settings-icon insight"><i class="bi bi-sliders"></i></span><div><h2>Preferences</h2><p>Simple defaults for your daily view</p></div></div><div class="preference-row"><span>Currency</span><b>₹ INR</b></div><div class="preference-row"><span>Default view</span><b>Monthly</b></div></section><section class="settings-card"><div class="settings-card-head"><span class="settings-icon expense"><i class="bi bi-shield-lock"></i></span><div><h2>Security</h2><p>Change your password anytime</p></div></div><form data-password-form><label>Current password<input name="current_password" type="password" required></label><label>New password<input name="new_password" type="password" minlength="8" required></label><button class="secondary-button" type="submit">Update password</button></form></section></div>

<?php else: ?>
<section class="page-heading"><div><p class="eyebrow">MONEY INSIGHTS</p><h1><?= $activePage === 'monthly' ? 'My Month' : 'Money Insights' ?></h1><p class="subheading">Here's what your money did from <?= e(readable_date($from)) ?> to <?= e(readable_date($to)) ?>.</p></div><button class="period-chip"><i class="bi bi-calendar3"></i><?= e(readable_date($from)) ?> – <?= e(readable_date($to)) ?></button></section><section class="insight-summary"><div><span>CAME IN</span><strong class="text-income"><?= e(rupees($summary['income'])) ?></strong></div><div><span>WENT OUT</span><strong class="text-expense"><?= e(rupees($summary['expense'])) ?></strong></div><div><span>STAYED</span><strong><?= e(rupees($summary['net'])) ?></strong></div></section><section class="chart-grid"><div class="chart-card"><div class="section-head"><div><p class="eyebrow">MONEY FLOW</p><h2>Income vs expense</h2></div></div><div class="chart-wrap"><canvas id="flow-chart" data-income="<?= e((string)$summary['income']) ?>" data-expense="<?= e((string)$summary['expense']) ?>"></canvas></div></div><div class="chart-card"><div class="section-head"><div><p class="eyebrow">A QUICK READ</p><h2>Your month in focus</h2></div></div><div class="insight-copy"><i class="bi bi-stars"></i><p><?= $summary['income'] || $summary['expense'] ? 'You received ' . e(rupees($summary['income'])) . ' and spent ' . e(rupees($summary['expense'])) . ' in this period.' : 'Give us a little data. Add a few transactions and your money insights will appear here.' ?></p></div><a class="secondary-button inline-button" href="categories.php">Manage categories</a></div></section>
<?php endif; ?>

<?php if (in_array($activePage, ['dashboard', 'activity', 'categories'], true)): ?>
<script>window.__MT_CATS = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;</script>
<div class="drawer-backdrop" data-drawer-backdrop></div>

<!-- ─── Transaction Drawer ─── -->
<aside class="action-drawer" data-transaction-drawer aria-hidden="true">
  <button class="drawer-close" data-close-drawer aria-label="Close"><i class="bi bi-x-lg"></i></button>
  <p class="eyebrow" data-drawer-eyebrow>MONEY OUT</p>
  <h2 data-drawer-title>What did you spend?</h2>
  <form data-transaction-form>
    <input type="hidden" name="type" value="expense">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="category_id" value="" data-category-input>
    <label>Amount
      <div class="amount-input"><span>₹</span><input name="amount" type="number" min="0.01" step="0.01" placeholder="0.00" required autofocus></div>
    </label>
    <div class="quick-amounts">
      <?php foreach ([100,500,1000,2000,5000] as $quick): ?>
      <button type="button" data-quick-amount="<?= $quick ?>">₹<?= number_format($quick) ?></button>
      <?php endforeach; ?>
    </div>
    <!-- Interactive Category Selector -->
    <div class="cat-selector" data-cat-selector>
      <div class="cat-main" data-cat-main>
        <p class="cat-label" data-cat-label>Category</p>
        <div class="cat-grid" data-cat-grid></div>
      </div>
      <div class="cat-sub" data-cat-sub hidden>
        <button type="button" class="cat-back" data-cat-back><i class="bi bi-arrow-left"></i> Back to categories</button>
        <p class="cat-label"><span data-subcat-parent-icon></span> <b data-subcat-title></b></p>
        <div class="cat-grid" data-subcat-grid></div>
      </div>
      <div class="cat-chosen" data-cat-chosen hidden>
        <div class="cat-chosen-info">
          <span class="cat-chosen-icon" data-chosen-icon></span>
          <div><b data-chosen-name></b><small class="cat-chosen-path" data-chosen-path></small></div>
        </div>
        <button type="button" class="cat-change" data-cat-change>Change</button>
      </div>
    </div>
    <label>Note <span class="label-optional">Optional</span>
      <input name="note" maxlength="255" placeholder="What was it for?">
    </label>
    <label>Date
      <input name="transaction_date" type="date" value="<?= e(date('Y-m-d')) ?>" required>
    </label>
    <button class="primary-button full-width" type="submit" data-submit-transaction>Record expense</button>
  </form>
</aside>

<!-- ─── Category Drawer ─── -->
<aside class="action-drawer category-drawer" data-category-drawer aria-hidden="true">
  <button class="drawer-close" data-close-category aria-label="Close"><i class="bi bi-x-lg"></i></button>
  <p class="eyebrow" data-cat-drawer-eyebrow>MAKE IT YOURS</p>
  <h2 data-cat-drawer-title>Create a category</h2>
  <form data-category-form>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id">
    <input type="hidden" name="parent_id" value="">
    <label>Name<input name="name" placeholder="e.g. Groceries" maxlength="80" required></label>
    <label data-cat-type-label>Type
      <select name="type" required>
        <option value="income">Income source</option>
        <option value="expense">Expense category</option>
      </select>
    </label>
    <label>Choose an icon
      <div class="icon-grid">
        <?php foreach (['🏫','💻','🏠','🚗','📱','🎓','💼','🛍️','🍔','🛒','🎮','✈️','💰','🎁','👨‍👩‍👦','💳','⛽','🏥','📚','🎬','🏋️','☕','🔧','📦'] as $icon): ?>
        <button type="button" class="icon-choice" data-icon="<?= e($icon) ?>"><?= e($icon) ?></button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="icon" value="💰">
    </label>
    <label>Choose a color
      <div class="color-grid">
        <?php foreach (['#22C55E','#F05252','#6366F1','#A78BFA','#F59E0B','#06B6D4','#EC4899','#84CC16'] as $color): ?>
        <button type="button" class="color-choice" style="--choice-color:<?= $color ?>" data-color="<?= $color ?>" aria-label="Select color"></button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="color" value="#6366F1">
    </label>
    <div class="category-preview"><span data-preview-icon>💰</span><div><b data-preview-name>New category</b><small data-preview-type>Income source</small></div></div>
    <button class="primary-button full-width" type="submit" data-cat-submit-btn>Create category</button>
  </form>
</aside>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>