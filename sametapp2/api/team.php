<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$user = $_SESSION['user'];
?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <title>Tým — Samet Festival</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-4">
  <div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold">Správa týmu</h1>
    <div id="current" class="mt-4 p-4 bg-white rounded shadow"></div>

    <div class="mt-6 grid grid-cols-1 gap-4">
      <div class="p-4 bg-white rounded shadow">
        <h2 class="font-semibold">Vytvořit tým</h2>
        <form id="createForm">
          <input name="name" placeholder="Název týmu" class="w-full p-2 border rounded mt-2">
          <button class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded">Vytvořit</button>
        </form>
      </div>

      <div class="p-4 bg-white rounded shadow">
        <h2 class="font-semibold">Připojit se k týmu</h2>
        <form id="joinForm">
          <input name="code" placeholder="Kód týmu" class="w-full p-2 border rounded mt-2">
          <button class="mt-2 px-4 py-2 bg-green-600 text-white rounded">Připojit</button>
        </form>
      </div>
    </div>
  </div>

<script>
async function refresh() {
  // získat aktuální tým – jednoduchě načteme z endpointu (můžeš implementovat /api/me)
  document.getElementById('current').innerText = 'Pro zobrazení informací o týmu přidej API /api/me';
}
document.getElementById('createForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const name = e.target.name.value;
  const res = await fetch('/api/team_create.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({name})});
  const j = await res.json();
  alert(JSON.stringify(j));
});
document.getElementById('joinForm').addEventListener('submit', async e=>{
  e.preventDefault();
  const code = e.target.code.value;
  const res = await fetch('/api/team_join.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({code})});
  const j = await res.json();
  alert(JSON.stringify(j));
});
refresh();
</script>
</body>
</html>
