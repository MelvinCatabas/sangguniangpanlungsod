<?php
include 'db.php';

if($_POST){
$u=$_POST['username'];
$p=md5($_POST['password']);

$q=$conn->query("SELECT * FROM users WHERE username='$u' AND password='$p'");
if($q->num_rows){
$_SESSION['login']=true;
header("Location:index.php");
}
}
?>

<script src="https://cdn.tailwindcss.com"></script>

<div class="h-screen flex justify-center items-center bg-gray-100">

<form method="POST" class="bg-white p-6 rounded shadow w-80">

<h2 class="font-bold mb-4">Login</h2>

<input name="username" placeholder="Username" class="border p-2 w-full mb-3">
<input type="password" name="password" placeholder="Password" class="border p-2 w-full mb-3">

<button class="bg-blue-500 text-white w-full py-2">Login</button>

</form>
</div>
