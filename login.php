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

<div class="relative h-screen flex items-center justify-center">

    <!-- Background -->
    <div class="absolute inset-0">
        <img src="http://localhost/Records Management System/General_Antonio_Luna_Monument,_Cabanatuan,_Nueva_Ecija,_April_2023.jpg"
             class="w-full h-full object-cover blur-lg">
        <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <!-- Login Form -->
    <form method="POST"
          class="relative z-10 bg-white/80 backdrop-blur-lg p-8 rounded-xl shadow-2xl w-80">

        <h2 class="font-bold mb-6 flex items-center gap-4 text-2xl">
            <img src="http://localhost/Records Management System/09D7332E-7156-4136-902E-D14B35703238-removebg-preview.png"
                class="w-20 h-auto object-contain">
            Login
        </h2>

        <input name="username" placeholder="Username"
               class="border border-gray-300 p-2 w-full mb-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">

        <input type="password" name="password" placeholder="Password"
               class="border border-gray-300 p-2 w-full mb-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">

        <button class="bg-blue-600 hover:bg-blue-700 text-white w-full py-2 rounded transition">
            Login
        </button>

    </form>
</div>