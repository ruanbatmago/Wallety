<?php
require 'conexao.php';
exigir_login();
$uid = $_SESSION['usuario_id'];
$categorias = categorias_padrao();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'adicionar';

    if ($acao === 'excluir') {
        $stmt = $pdo->prepare('DELETE FROM transacoes WHERE id = ? AND usuario_id = ?');
        $stmt->execute([(int) ($_POST['id'] ?? 0), $uid]);
        header('Location: transacoes.php');
        exit;
    }

    $tipo      = $_POST['tipo'] ?? '';
    $descricao = mb_substr(trim($_POST['descricao'] ?? ''), 0, 150);
    $categoria = $_POST['categoria'] ?? '';
    $valor     = (float) ($_POST['valor'] ?? 0);
    $data      = $_POST['data'] ?? '';

    $dataObj = DateTime::createFromFormat('Y-m-d', $data);
    $dataValida = $dataObj && $dataObj->format('Y-m-d') === $data;

    if (
        isset($categorias[$tipo])
        && in_array($categoria, $categorias[$tipo], true)
        && $descricao !== ''
        && valor_valido($valor)
        && $dataValida
    ) {
        $stmt = $pdo->prepare(
            'INSERT INTO transacoes (usuario_id, tipo, descricao, categoria, valor, data_transacao)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$uid, $tipo, $descricao, $categoria, round($valor, 2), $data]);
        header('Location: transacoes.php');
        exit;
    }

    $erro = 'Não foi possível salvar. Confira o tipo, a categoria, o valor e a data.';
}

$stmt = $pdo->prepare('SELECT * FROM transacoes WHERE usuario_id = ? ORDER BY data_transacao DESC, id DESC LIMIT 5');
$stmt->execute([$uid]);
$ultimas = $stmt->fetchAll();

require 'topo.php';
?>

<h1>Entradas e despesas</h1>

<?php if ($erro): ?>
  <p class="aviso erro"><?= htmlspecialchars($erro) ?></p>
<?php endif; ?>

<form method="post" class="card">
  <input type="hidden" name="acao" value="adicionar">
  <label>Tipo
    <select name="tipo" id="tipo">
      <option value="despesa">Despesa</option>
      <option value="entrada">Entrada</option>
    </select>
  </label>
  <label>Descrição <input type="text" name="descricao" maxlength="150" required></label>
  <label>Categoria
    <select name="categoria" id="categoria">
      <?php foreach ($categorias['despesa'] as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Valor (R$) <input type="number" name="valor" step="0.01" min="0.01" required></label>
  <label>Data <input type="date" name="data" value="<?= date('Y-m-d') ?>" required></label>
  <button type="submit">Adicionar</button>
</form>

<h2>Últimos lançamentos</h2>
<?php if ($ultimas): ?>
  <table>
    <tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Valor</th><th></th></tr>
    <?php foreach ($ultimas as $t): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($t['data_transacao'])) ?></td>
        <td><?= htmlspecialchars($t['descricao']) ?></td>
        <td><?= htmlspecialchars($t['categoria']) ?></td>
        <td class="<?= $t['tipo'] ?>"><?= $t['tipo'] === 'entrada' ? '+' : '-' ?> <?= moeda($t['valor']) ?></td>
        <td class="acao">
          <form method="post" class="inline" onsubmit="return confirm('Excluir esta transação?')">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= $t['id'] ?>">
            <button type="submit" class="excluir">Excluir</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php else: ?>
  <p>Nenhum lançamento ainda. Use o formulário acima para registrar o primeiro.</p>
<?php endif; ?>

<script>
const categorias = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
const tipo = document.getElementById('tipo');
const categoria = document.getElementById('categoria');

function preencherCategorias() {
  categoria.innerHTML = '';
  categorias[tipo.value].forEach(nome => categoria.add(new Option(nome, nome)));
}

tipo.addEventListener('change', preencherCategorias);
preencherCategorias();
</script>

</main>
</body>
</html>
