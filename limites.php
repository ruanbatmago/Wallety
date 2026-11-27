<?php
require 'conexao.php';
exigir_login();
$uid = $_SESSION['usuario_id'];
$categorias = categorias_padrao()['despesa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'definir';

    if ($acao === 'definir') {
        $categoria = $_POST['categoria'] ?? '';
        $valor = (float) ($_POST['valor_limite'] ?? 0);

        if (in_array($categoria, $categorias, true) && valor_valido($valor)) {
            $valor = round($valor, 2);
            $stmt = $pdo->prepare(
                'INSERT INTO limites (usuario_id, categoria, valor_limite) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE valor_limite = ?'
            );
            $stmt->execute([$uid, $categoria, $valor, $valor]);
        }
    }

    if ($acao === 'remover') {
        $stmt = $pdo->prepare('DELETE FROM limites WHERE id = ? AND usuario_id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0), $uid]);
    }

    header('Location: limites.php');
    exit;
}

$mes = date('Y-m');
[$inicio, $fim] = intervalo_mes($mes);

$stmt = $pdo->prepare(
    "SELECT l.id, l.categoria, l.valor_limite, COALESCE(SUM(t.valor), 0) AS gasto
     FROM limites l
     LEFT JOIN transacoes t
       ON t.usuario_id = l.usuario_id
      AND t.categoria = l.categoria
      AND t.tipo = 'despesa'
      AND t.data_transacao >= ?
      AND t.data_transacao < ?
     WHERE l.usuario_id = ?
     GROUP BY l.id, l.categoria, l.valor_limite
     ORDER BY l.categoria"
);
$stmt->execute([$inicio, $fim, $uid]);
$limites = $stmt->fetchAll();

require 'topo.php';
?>

<h1>Limites mensais</h1>

<form method="post" class="card">
  <input type="hidden" name="acao" value="definir">
  <label>Categoria
    <select name="categoria">
      <?php foreach ($categorias as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Limite por mês (R$) <input type="number" name="valor_limite" step="0.01" min="0.01" required></label>
  <button type="submit">Definir limite</button>
</form>

<h2>Gastos de <?= nome_mes($mes) ?></h2>
<?php if ($limites): ?>
  <?php foreach ($limites as $l): ?>
    <?php
      $gasto = (float) $l['gasto'];
      $limite = (float) $l['valor_limite'];
      $estourou = $gasto > $limite;
    ?>
    <div class="card">
      <div class="linha">
        <strong><?= htmlspecialchars($l['categoria']) ?></strong>
        <form method="post" class="inline" onsubmit="return confirm('Remover este limite?')">
          <input type="hidden" name="acao" value="remover">
          <input type="hidden" name="id" value="<?= $l['id'] ?>">
          <button type="submit" class="excluir">Remover</button>
        </form>
      </div>
      <p><?= moeda($gasto) ?> de <?= moeda($limite) ?></p>
      <progress<?= $estourou ? ' class="estourou"' : '' ?> value="<?= min($gasto, $limite) ?>" max="<?= $limite ?>"></progress>
      <?php if ($estourou): ?>
        <p class="aviso erro">Limite ultrapassado em <?= moeda($gasto - $limite) ?>.</p>
      <?php else: ?>
        <p>Ainda restam <?= moeda($limite - $gasto) ?> neste mês.</p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>Nenhum limite definido. Escolha uma categoria acima para começar.</p>
<?php endif; ?>

</main>
</body>
</html>
