<?php
$pagina = basename($_SERVER['SCRIPT_NAME']);
$links = [
    'index.php'      => 'Início',
    'transacoes.php' => 'Entradas e despesas',
    'metas.php'      => 'Metas de economia',
    'limites.php'    => 'Limites mensais',
    'graficos.php'   => 'Gráficos',
    'historico.php'  => 'Histórico de transações',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wallety</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<nav>
  <strong>Wallety</strong>
  <?php foreach ($links as $arquivo => $nome): ?>
    <a href="<?= $arquivo ?>"<?= $pagina === $arquivo ? ' aria-current="page"' : '' ?>><?= $nome ?></a>
  <?php endforeach; ?>
  <a href="login.php?sair=1" class="sair">Sair (<?= htmlspecialchars($_SESSION['usuario_nome']) ?>)</a>
</nav>
<main>
