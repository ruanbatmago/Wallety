<?php
require 'conexao.php';
exigir_login();
$uid = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'criar';

    if ($acao === 'criar') {
        $nome  = mb_substr(trim($_POST['nome'] ?? ''), 0, 100);
        $alvo  = (float) ($_POST['valor_alvo'] ?? 0);
        $atual = (float) ($_POST['valor_atual'] ?? 0);

        if ($nome !== '' && valor_valido($alvo) && $atual >= 0 && $atual <= 99999999.99) {
            $stmt = $pdo->prepare('INSERT INTO metas (usuario_id, nome, valor_alvo, valor_atual) VALUES (?, ?, ?, ?)');
            $stmt->execute([$uid, $nome, round($alvo, 2), round($atual, 2)]);
        }
    }

    if ($acao === 'aportar') {
        $valor = (float) ($_POST['valor'] ?? 0);

        $stmt = $pdo->prepare('SELECT * FROM metas WHERE id = ? AND usuario_id = ?');
        $stmt->execute([(int) ($_POST['meta_id'] ?? 0), $uid]);
        $meta = $stmt->fetch();

        if ($meta && valor_valido($valor) && $meta['valor_atual'] + $valor <= 99999999.99) {
            $valor = round($valor, 2);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE metas SET valor_atual = valor_atual + ? WHERE id = ? AND usuario_id = ?');
                $stmt->execute([$valor, $meta['id'], $uid]);

                $stmt = $pdo->prepare(
                    'INSERT INTO transacoes (usuario_id, tipo, descricao, categoria, valor, data_transacao)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$uid, 'despesa', 'Aporte: ' . $meta['nome'], CATEGORIA_POUPANCA, $valor, date('Y-m-d')]);

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
    }

    header('Location: metas.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM metas WHERE usuario_id = ? ORDER BY id DESC');
$stmt->execute([$uid]);
$metas = $stmt->fetchAll();

require 'topo.php';
?>

<h1>Metas de economia</h1>

<form method="post" class="card">
  <input type="hidden" name="acao" value="criar">
  <label>Nome da meta <input type="text" name="nome" maxlength="100" required></label>
  <label>Valor alvo (R$) <input type="number" name="valor_alvo" step="0.01" min="0.01" required></label>
  <label>Valor já guardado (R$) <input type="number" name="valor_atual" step="0.01" min="0" value="0" required></label>
  <button type="submit">Criar meta</button>
</form>

<h2>Suas metas</h2>
<?php if ($metas): ?>
  <?php foreach ($metas as $m): ?>
    <?php
      $atual = (float) $m['valor_atual'];
      $alvo = (float) $m['valor_alvo'];
      $pct = $alvo > 0 ? min(100, (int) floor($atual / $alvo * 100)) : 0;
    ?>
    <div class="card">
      <div class="linha">
        <strong><?= htmlspecialchars($m['nome']) ?></strong>
        <span class="pct"><?= $pct ?>%</span>
      </div>
      <p><?= moeda($atual) ?> de <?= moeda($alvo) ?></p>
      <progress value="<?= min($atual, $alvo) ?>" max="<?= $alvo ?>"></progress>
      <?php if ($atual >= $alvo): ?>
        <p class="entrada">Meta concluída</p>
      <?php endif; ?>
      <form method="post" class="aporte">
        <input type="hidden" name="acao" value="aportar">
        <input type="hidden" name="meta_id" value="<?= $m['id'] ?>">
        <input type="number" name="valor" step="0.01" min="0.01" placeholder="Valor a guardar (R$)" aria-label="Valor a guardar em <?= htmlspecialchars($m['nome']) ?>" required>
        <button type="submit">Guardar</button>
      </form>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>Você ainda não tem metas. Crie a primeira no formulário acima.</p>
<?php endif; ?>

</main>
</body>
</html>
