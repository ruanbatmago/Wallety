<?php
require 'conexao.php';
exigir_login();
$uid = $_SESSION['usuario_id'];

$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor END), 0) AS entradas,
        COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor END), 0) AS despesas
     FROM transacoes WHERE usuario_id = ?"
);
$stmt->execute([$uid]);
$totais = $stmt->fetch();
$saldo = $totais['entradas'] - $totais['despesas'];

require 'topo.php';
?>

<h1>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></h1>
<p class="saldo">Saldo atual: <strong><?= moeda($saldo) ?></strong></p>

<div class="totais">
  <div class="total total-entrada">
    <span>Total de entradas</span>
    <strong><?= moeda($totais['entradas']) ?></strong>
  </div>
  <div class="total total-despesa">
    <span>Total de despesas</span>
    <strong><?= moeda($totais['despesas']) ?></strong>
  </div>
</div>

<div class="menu">
  <?php foreach ($links as $arquivo => $nome): ?>
    <?php if ($arquivo !== 'index.php'): ?>
      <a href="<?= $arquivo ?>"><?= $nome ?></a>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

</main>
</body>
</html>
