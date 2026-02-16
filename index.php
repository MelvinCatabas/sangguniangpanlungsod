<?php
include 'db.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* 🔐 LOGIN CHECK */
if (!isset($_SESSION['login'])) {
  header("Location: login.php");
  exit;
}

$message = '';

// Show message once and then clear
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // remove it so it won't show again
}

// ────────────────────────────────────────────────
// HANDLE SAVE (ADD) & UPDATE (EDIT)
// ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $r         = $conn->real_escape_string($_POST['resolution_no']);
  $t         = $conn->real_escape_string($_POST['title']);
  $received  = $_POST['date_received'];
  $approved  = $_POST['date_approved'];
  $pdf_file  = ''; // will be set only on new upload

  $today = date('Y-m-d');
  if ($received > $today || $approved > $today) {
    die('Invalid date selected. Dates cannot be in the future.');
  }

  // Handle file upload (only if a new file is provided)
  if (!empty($_FILES['pdf']['name'])) {
    $f = basename($_FILES['pdf']['name']);
    $tmp = $_FILES['pdf']['tmp_name'];

    if (!is_dir('uploads')) mkdir('uploads', 0755, true);

    $target = __DIR__ . '/uploads/' . $f;
    if (move_uploaded_file($tmp, $target)) {
      $pdf_file = $f;
    } else {
      die('Failed to upload PDF.');
    }
  }

  if (isset($_POST['add'])) {
    // ADD NEW
    $sql = "INSERT INTO resolutions (resolution_no, title, pdf_file, date_received, date_approved)
            VALUES ('$r', '$t', '$pdf_file', '$received', '$approved')";
    $conn->query($sql);
    $_SESSION['message'] = 'added';
    header("Location: index.php");
    exit;

  }

  if (isset($_POST['update']) && !empty($_POST['edit_id'])) {
    // UPDATE EXISTING
    $id = (int)$_POST['edit_id'];

    $update_sql = "UPDATE resolutions SET 
                   resolution_no = '$r',
                   title = '$t',
                   date_received = '$received',
                   date_approved = '$approved'";

    if ($pdf_file !== '') {
      $update_sql .= ", pdf_file = '$pdf_file'";
    }

    $update_sql .= " WHERE id = $id";

    $_SESSION['message'] = 'updated';
    header("Location: index.php");
    exit;
  }
}

// ────────────────────────────────────────────────
// FILTERS & LIST
// ────────────────────────────────────────────────
$search    = $_GET['search']    ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';

// PAGINATION
$limit = 10; // rows per page
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Start SQL
$sql = "SELECT * FROM resolutions WHERE 1=1";

// Apply filters
if ($search !== '') {
    $esc_search = $conn->real_escape_string($search);
    $sql .= " AND (resolution_no LIKE '%$esc_search%' OR title LIKE '%$esc_search%')";
}

if ($date_from !== '') {
    $sql .= " AND date_received >= '" . $conn->real_escape_string($date_from) . "'";
}

if ($date_to !== '') {
    $sql .= " AND date_received <= '" . $conn->real_escape_string($date_to) . "'";
}

// Count total rows for pagination
$count_sql = "SELECT COUNT(*) as total FROM resolutions WHERE 1=1";
if ($search !== '') $count_sql .= " AND (resolution_no LIKE '%$esc_search%' OR title LIKE '%$esc_search%')";
if ($date_from !== '') $count_sql .= " AND date_received >= '" . $conn->real_escape_string($date_from) . "'";
if ($date_to !== '') $count_sql .= " AND date_received <= '" . $conn->real_escape_string($date_to) . "'";

$total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Finally add ORDER BY and LIMIT
$sql .= " ORDER BY date_received DESC LIMIT $offset, $limit";

// Execute query
$q = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Resolutions & Ordinances</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
  /* Hide native calendar icon */
  input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: 0;
    position: absolute;
    right: 0;
  }
  input[type="date"] {
    appearance: none;
    -webkit-appearance: none;
  }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">

<?php if ($message): ?>
  <div id="flashMessage" class="fixed top-4 right-4 z-50 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg">
    <?php if ($message === 'added'): ?> Resolution added successfully! <?php endif; ?>
    <?php if ($message === 'updated'): ?> Resolution updated successfully! <?php endif; ?>
  </div>
<?php endif; ?>

<!-- ADD / EDIT MODAL (same modal reused) -->
<div id="resolutionModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 relative">

    <button onclick="closeModal()" class="absolute right-4 top-3 text-gray-500 hover:text-gray-700 text-xl">×</button>

    <h2 id="modalTitle" class="font-semibold text-lg mb-1">Add New Resolution</h2>
    <p class="text-sm text-gray-500 mb-5">Enter the details for the resolution.</p>

    <form method="POST" enctype="multipart/form-data">

      <input type="hidden" name="edit_id" id="edit_id" value="">

      <label class="block text-sm font-medium text-gray-700 mb-1.5">Resolution No</label>
      <input name="resolution_no" id="resolution_no" required class="border border-gray-300 rounded-lg w-full p-2.5 mb-4 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">

      <label class="block text-sm font-medium text-gray-700 mb-1.5">Title</label>
      <input name="title" id="title" required class="border border-gray-300 rounded-lg w-full p-2.5 mb-4 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">

      <label class="block text-sm font-medium text-gray-700 mb-1.5">PDF File <span class="text-xs text-gray-500">(leave blank to keep current)</span></label>
      <input type="file" name="pdf" id="pdf" accept="application/pdf" class="mb-5 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

      <label class="block text-sm font-medium text-gray-700 mb-1.5">Date Received</label>
      <input type="date" name="date_received" id="date_received" max="<?= date('Y-m-d') ?>" required
        class="border border-gray-300 rounded-lg w-full p-2.5 mb-4 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">

      <label class="block text-sm font-medium text-gray-700 mb-1.5">Date Approved</label>
      <input type="date" name="date_approved" id="date_approved" max="<?= date('Y-m-d') ?>" required
        class="border border-gray-300 rounded-lg w-full p-2.5 mb-6 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">

      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeModal()" class="px-5 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
        <button id="submitBtn" name="add" class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add</button>
      </div>

    </form>
  </div>
</div>

<!-- NAVBAR -->
<div class="bg-white shadow px-4 md:px-6 py-3 flex justify-between items-center">
  <div class="flex items-center gap-3">
    <img src="https://punto.com.ph/wp-content/uploads/2020/05/09D7332E-7156-4136-902E-D14B35703238.png" class="h-12 md:h-14">
    <div>
      <h1 class="text-lg md:text-xl font-bold text-blue-700">Sangguniang Panglungsod</h1>
      <p class="text-xs md:text-sm text-gray-500">Cabanatuan City</p>
    </div>
  </div>
  <a href="logout.php" class="bg-red-600 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-lg hover:bg-red-700 text-sm md:text-base">Logout</a>
</div>


<!-- MAIN CONTENT -->
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pb-12">

  <div class="bg-white rounded-xl shadow p-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Resolutions & Ordinances</h2>
        <p class="text-sm text-gray-500 mt-1">Browse and manage legislative documents</p>
      </div>
      <button onclick="openAdd()" class="bg-green-600 text-white px-5 py-2.5 rounded-lg hover:bg-green-700 font-medium whitespace-nowrap">
        + Add Resolution
      </button>
    </div>

    <!-- FILTER FORM (unchanged) -->
    <form method="GET" class="mt-2 flex flex-col gap-4 md:flex-row md:items-end md:flex-wrap lg:flex-nowrap lg:gap-4">
      <div class="flex-1 min-w-[260px]">
        <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-1.5">Search</label>
        <input id="searchInput" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Resolution number or title..." class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
      </div>
      <div class="w-full md:w-48">
        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1.5">From Date</label>
        <div class="flex items-center border border-gray-300 rounded-lg shadow-sm bg-white focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
          <input
            type="date"
            name="date_from"
            value="<?= htmlspecialchars($date_from) ?>"
            max="<?= date('Y-m-d') ?>"
            onclick="this.showPicker()"
            class="w-full px-4 py-2.5 border-none outline-none cursor-pointer rounded-l-lg"
          />
          <button type="button" onclick="this.previousElementSibling.showPicker()" class="px-3 text-gray-400 hover:text-blue-600">
            📅
          </button>
        </div>
      </div>

      <div class="w-full md:w-48">
        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1.5">To Date</label>
        <div class="flex items-center border border-gray-300 rounded-lg shadow-sm bg-white focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
          <input
            type="date"
            id="date_to"
            name="date_to"
            value="<?= htmlspecialchars($date_to) ?>"
            max="<?= date('Y-m-d') ?>"
            onclick="this.showPicker()"
            class="w-full px-4 py-2.5 border-none outline-none cursor-pointer rounded-l-lg"
          />
          <button type="button" onclick="document.getElementById('date_to').showPicker()" class="px-3 text-gray-400 hover:text-blue-600">
            📅
          </button>
        </div>
      </div>
      <div class="flex items-center gap-3 pt-2 md:pt-0">
        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">Filter</button>
        <a href="index.php" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none transition">Clear</a>
      </div>
    </form>

  </div>

  <!-- TABLE -->
  <div class="bg-white rounded-xl shadow mt-6 overflow-hidden">
    <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
      <table class="w-full text-sm text-gray-700">
        <thead class="bg-gray-200 border-b-2 border-gray-300 sticky top-0 z-10">
          <tr>
            <th class="p-4 text-left font-semibold text-gray-800 uppercase tracking-wide">Resolution No</th>
            <th class="p-4 text-left font-semibold text-gray-800 uppercase tracking-wide">Title</th>
            <th class="p-4 text-left font-semibold text-gray-800 uppercase tracking-wide">Date Received</th>
            <th class="p-4 text-left font-semibold text-gray-800 uppercase tracking-wide">Date Approved</th>
            <th class="p-4 text-left font-semibold text-gray-800 uppercase tracking-wide">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $q->fetch_assoc()): ?>
          <tr class="border-t hover:bg-gray-50 transition">
            <td class="p-4"><?= htmlspecialchars($row['resolution_no']) ?></td>
            <td class="p-4">
              <a href="uploads/<?= htmlspecialchars($row['pdf_file']) ?>" target="_blank" class="text-blue-600 hover:underline hover:text-blue-800">
                <?= htmlspecialchars($row['title']) ?>
              </a>
            </td>
            <td class="p-4"><?= date("M d, Y", strtotime($row['date_received'])) ?></td>
            <td class="p-4"><?= date("M d, Y", strtotime($row['date_approved'])) ?></td>
            <td class="p-4">
              <button 
                onclick="openEdit(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['resolution_no'])) ?>', '<?= htmlspecialchars(addslashes($row['title'])) ?>', '<?= $row['date_received'] ?>', '<?= $row['date_approved'] ?>', '<?= htmlspecialchars($row['pdf_file']) ?>')"
                class="inline-flex items-center px-4 py-1.5 rounded-full bg-indigo-100 text-indigo-700 font-medium text-xs hover:bg-indigo-200 hover:text-indigo-900 transition">
                ✏️ Edit
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

    <!-- Move pagination **inside** scrollable container -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center items-center mt-4 py-4 border-t border-gray-200 gap-2">
      <!-- Previous Button -->
      <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
          class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">&laquo; Prev</a>
      <?php else: ?>
        <span class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">&laquo; Prev</span>
      <?php endif; ?>

      <!-- Page Numbers -->
      <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
          class="px-3 py-1 rounded-md <?= $p == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>

      <!-- Next Button -->
      <?php if ($page < $total_pages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
          class="px-3 py-1 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">Next &raquo;</a>
      <?php else: ?>
        <span class="px-3 py-1 rounded-md bg-gray-100 text-gray-400 cursor-not-allowed">Next &raquo;</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div> <!-- end scrollable div -->
  </div>


<script>
function openAdd() {
  document.getElementById('modalTitle').textContent = 'Add New Resolution';
  document.getElementById('submitBtn').name = 'add';
  document.getElementById('submitBtn').textContent = 'Add';
  document.getElementById('edit_id').value = '';
  document.getElementById('resolution_no').value = '';
  document.getElementById('title').value = '';
  document.getElementById('date_received').value = '';
  document.getElementById('date_approved').value = '';
  document.getElementById('pdf').value = '';
  document.getElementById('resolutionModal').classList.remove('hidden');
}

function openEdit(id, res_no, title, received, approved, current_pdf) {
  document.getElementById('modalTitle').textContent = 'Edit Resolution';
  document.getElementById('submitBtn').name = 'update';
  document.getElementById('submitBtn').textContent = 'Update';
  document.getElementById('edit_id').value = id;
  document.getElementById('resolution_no').value = res_no;
  document.getElementById('title').value = title;
  document.getElementById('date_received').value = received;
  document.getElementById('date_approved').value = approved;
  // Note: file input cannot be pre-filled for security reasons
  document.getElementById('resolutionModal').classList.remove('hidden');
}

function closeModal() {
  document.getElementById('resolutionModal').classList.add('hidden');
}

const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function () {
  if (this.value.trim() === '') {
    window.location.href = 'index.php';
  }
});
</script>

<script>
  // Hide flash message after 3 seconds
  const flash = document.getElementById('flashMessage');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = "opacity 0.5s";
      flash.style.opacity = 0;
      setTimeout(() => flash.remove(), 500); // remove from DOM
    }, 3000); // 3000ms = 3s
  }
</script>
</body>
</html>