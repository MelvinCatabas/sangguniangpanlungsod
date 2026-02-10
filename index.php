<?php
include 'db.php';
if(isset($_POST['save'])){

$r = $_POST['resolution_no'];
$t = $_POST['title'];
$received = $_POST['date_received'];
$approved = $_POST['date_approved'];

$f = basename($_FILES['pdf']['name']);
$tmp = $_FILES['pdf']['tmp_name'];

if(!is_dir('uploads')) mkdir('uploads');
move_uploaded_file($tmp, __DIR__.'/uploads/'.$f);

$conn->query("
INSERT INTO resolutions
(resolution_no, title, pdf_file, date_received, date_approved)
VALUES
('$r', '$t', '$f', '$received', '$approved')
");

header("Location:index.php");



header("Location:index.php");
}

if(!isset($_SESSION['login'])) header("Location:login.php");

$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$sql = "SELECT * FROM resolutions WHERE 1=1";

if ($search != '') {
  $sql .= " AND (resolution_no LIKE '%$search%' OR title LIKE '%$search%')";
}

if ($date_from != '') {
  $sql .= " AND date_received >= '$date_from'";
}

if ($date_to != '') {
  $sql .= " AND date_received <= '$date_to'";
}

$sql .= " ORDER BY date_received DESC";

$q = $conn->query($sql);


?>

<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<!-- ADD MODAL -->
<div id="addModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center">

<div class="bg-white rounded-lg shadow w-[420px] p-6 relative">

<button onclick="closeAdd()" class="absolute right-4 top-3 text-gray-500 text-xl">×</button>

<h2 class="font-semibold text-lg mb-1">Add New Resolution</h2>
<p class="text-sm  text-gray-500 mb-4">
Enter the details for the new resolution or ordinance.
</p>

<form method="POST" enctype="multipart/form-data">

<label class="text-sm">Resolution No</label>
<input name="resolution_no" required
placeholder="e.g. R006"
class="border rounded w-full p-2 mb-3">

<label class="text-sm">Title</label>
<input name="title" required
placeholder="e.g. environmental-protection-act"
class="border rounded w-full p-2 mb-3">

<label class="text-sm">PDF File</label>
<input type="file" name="pdf" accept="application/pdf" required
class="mb-4">

<label class="text-sm">Date Received</label>
<input
  type="date"
  name="date_received"
  max="<?= date('Y-m-d') ?>"
  required
  class="border rounded w-full p-2 mb-3"
>

<label class="text-sm">Date Approved</label>
<input
  type="date"
  name="date_approved"
  max="<?= date('Y-m-d') ?>"
  required
  class="border rounded w-full p-2 mb-3"
>




<div class="flex justify-end gap-2">

<button type="button" onclick="closeAdd()"
class="border px-4 py-1 rounded">
Cancel
</button>

<button name="save"
class="bg-green-500 text-white px-4 py-1 rounded">
Add Resolution
</button>

</div>

</form>

</div>
</div>


<body class="bg-gray-50">

<!-- NAVBAR -->
<div class="bg-white shadow-sm px-10 py-4 flex justify-between items-center">

  <!-- Logo and Text -->
  <div class="flex items-center ">
    <!-- Logo (bigger) -->
    <img src="https://punto.com.ph/wp-content/uploads/2020/05/09D7332E-7156-4136-902E-D14B35703238.png" 
         class="h-20 w-auto object-contain flex-shrink-0">
    
    <!-- Title and Subtitle -->
    <div>
      <h1 class="text-xl font-bold text-blue-700">Sangguniang Panglungsod</h1>
      <p class="text-sm text-gray-500">Cabanatuan City</p>
    </div>
  </div>

  <!-- Logout Button -->
  <div>
    <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded">Logout</a>
  </div>

</div>



<!-- CONTENT -->
<div class="max-w-5xl mx-auto mt-10">

<!-- CARD -->
<div class="bg-white rounded shadow p-6">

<div class="flex justify-between mb-4">
<div>
<h2 class="text-lg font-semibold">Resolutions & Ordinances</h2>
<p class="text-sm text-gray-500">Browse and manage legislative documents</p>
</div>

<button onclick="openAdd()"
class="bg-green-500 text-white px-4 py-2 rounded">
+ Add
</button>

</div>

<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2">

  <input
    id="searchInput"
    type="text"
    name="search"
    value="<?= htmlspecialchars($search) ?>"
    placeholder="Search resolution no or title..."
    class="border rounded p-2 col-span-2"
  >

  <input
    type="date"
    name="date_from"
    value="<?= $date_from ?>"
    max="<?= date('Y-m-d') ?>"
    class="border rounded p-2"
  >

  <input
    type="date"
    name="date_to"
    value="<?= $date_to ?>"
    max="<?= date('Y-m-d') ?>"
    class="border rounded p-2"
  >

  <button
    type="submit"
    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 col-span-1 md:col-span-1">
    Filter
  </button>

  <a href="index.php"
    class="border px-4 py-2 rounded text-center">
    Clear
  </a>

</form>



</div>

<!-- TABLE -->
<div class="bg-white rounded shadow mt-6">

<table class="w-full text-sm">

<thead class="bg-gray-100">
<tr>
<th class="text-left p-3">Resolution No</th>
<th class="text-left p-3">Title</th>
<th class="text-left p-3">Date Received</th>
<th class="text-left p-3">Date Approved</th>


</tr>
</thead>

<tbody>

<?php while($row=$q->fetch_assoc()): ?>

<tr class="border-t hover:bg-gray-50">

<td class="p-3"><?= $row['resolution_no'] ?></td>

<td class="p-3">
<a href="uploads/<?= $row['pdf_file'] ?>" target="_blank"
class="text-blue-600 hover:underline">
<?= $row['title'] ?>
</a>
</td>


<td class="p-3">
<?= date("M d, Y", strtotime($row['date_received'])) ?>
</td>

<td class="p-3">
<?= date("M d, Y", strtotime($row['date_approved'])) ?>
</td>





</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</div>

<!-- PDF MODAL -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center">

<div class="bg-white w-4/5 h-4/5 rounded shadow relative">

<button onclick="closePdf()"
class="absolute top-2 right-2 bg-red-500 text-white px-3 rounded">
X
</button>

<iframe id="viewer" class="w-full h-full"></iframe>

</div>
</div>
<div class="h-24"></div>

<script>
function openPdf(file){
document.getElementById('modal').classList.remove('hidden');
document.getElementById('viewer').src=file;
}
function closePdf(){
document.getElementById('modal').classList.add('hidden');
}
</script>

</body>
</html>
<script>
function openAdd(){
document.getElementById('addModal').classList.remove('hidden');
}

function closeAdd(){
document.getElementById('addModal').classList.add('hidden');
}

const searchInput = document.getElementById('searchInput');

searchInput.addEventListener('input', function () {
  if (this.value.trim() === '') {
    window.location.href = 'index.php';
  }
});


</script>
