<?php
require 'conexao.php';
exigir_login();
$uid = $_SESSION['usuario_id'];

$filtro = $_GET['tipo'] ?? '';
if (!in_array($filtro, ['entrada', 'despesa'], true)) {
    $filtro = '';
}
$destino = 'historico.php' . ($filtro !== '' ? '?tipo=' . $filtro : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $stmt = $pdo->prepare('DELETE FROM transacoes WHERE id = ? AND usuario_id = ?');
    $stmt->execute([(int) ($_POST['id'] ?? 0), $uid]);
    header('Location: ' . $destino);
    exit;
}

$sql = 'SELECT * FROM transacoes WHERE usuario_id = ?';
$params = [$uid];
if ($filtro !== '') {
    $sql .= ' AND tipo = ?';
    $params[] = $filtro;
}
$sql .= ' ORDER BY data_transacao DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transacoes = $stmt->fetchAll();

$opcoes = ['' => 'Todas', 'entrada' => 'Somente entradas', 'despesa' => 'Somente despesas'];

require 'topo.php';
?>

<h1>Histórico de transações</h1>

<div class="filtros">
  <?php foreach ($opcoes as $valor => $rotulo): ?>
    <a href="historico.php<?= $valor !== '' ? '?tipo=' . $valor : '' ?>"<?= (string) $valor === $filtro ? ' aria-current="page"' : '' ?>><?= $rotulo ?></a>
  <?php endforeach; ?>
</div>

<?php if ($transacoes): ?>
  <table>
    <tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th></th></tr>
    <?php foreach ($transacoes as $t): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($t['data_transacao'])) ?></td>
        <td><?= htmlspecialchars($t['descricao']) ?></td>
        <td><?= htmlspecialchars($t['categoria']) ?></td>
        <td class="<?= $t['tipo'] ?>"><?= $t['tipo'] === 'entrada' ? '+' : '-' ?> <?= moeda($t['valor']) ?></td>
        <td class="acao">
          <form method="post" action="<?= $destino ?>" class="inline" onsubmit="return confirm('Excluir esta transação?')">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" class="excluir">Excluir</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p>Nenhuma transação encontrada.</p>
<?php endif; ?>

</main>
</body>
</html>
