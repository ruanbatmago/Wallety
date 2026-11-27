<?php
require 'conexao.php';
exigir_login();
$uid = $_SESSION['usuario_id'];

$stmt = $pdo->prepare(
    "SELECT DISTINCT DATE_FORMAT(data_transacao, '%Y-%m') AS mes
     FROM transacoes WHERE usuario_id = ? ORDER BY mes DESC"
);
$stmt->execute([$uid]);
$meses = array_column($stmt->fetchAll(), 'mes');

$periodo = $_GET['periodo'] ?? '';
if (!in_array($periodo, $meses, true)) {
    $periodo = '';
}

$filtroData = '';
$params = [$uid];
if ($periodo !== '') {
    [$inicio, $fim] = intervalo_mes($periodo);
    $filtroData = ' AND data_transacao >= ? AND data_transacao < ?';
    $params[] = $inicio;
    $params[] = $fim;
}

$stmt = $pdo->prepare(
    "SELECT categoria, SUM(valor) AS total
     FROM transacoes
     WHERE usuario_id = ? AND tipo = 'despesa'" . $filtroData . "
     GROUP BY categoria
     ORDER BY total DESC"
);
$stmt->execute($params);
$categorias = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN valor END), 0) AS entradas,
        COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor END), 0) AS despesas
     FROM transacoes
     WHERE usuario_id = ?" . $filtroData
);
$stmt->execute($params);
$totais = $stmt->fetch();

$totalEntradas = (float) $totais['entradas'];
$totalDespesas = (float) $totais['despesas'];

$dadosCategorias = [
    'rotulos' => array_column($categorias, 'categoria'),
    'valores' => array_map('floatval', array_column($categorias, 'total')),
];
$dadosTotais = [$totalEntradas, $totalDespesas];

require 'topo.php';
?>

<h1>Gráficos</h1>

<form method="get" class="periodo">
  <label>Período
    <select name="periodo" onchange="this.form.submit()">
      <option value="">Todo o período</option>
      <?php foreach ($meses as $m): ?>
        <option value="<?= $m ?>"<?= $m === $periodo ? ' selected' : '' ?>><?= nome_mes($m) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <noscript><button type="submit">Filtrar</button></noscript>
</form>

<div class="graficos">
  <div class="card">
    <h2>Despesas por categoria</h2>
    <?php if ($categorias): ?>
      <div class="grafico"><canvas id="grafico-categorias"></canvas></div>
    <?php else: ?>
      <p>Nenhuma despesa registrada neste período.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Entradas e despesas</h2>
    <?php if ($totalEntradas > 0 || $totalDespesas > 0): ?>
      <div class="grafico"><canvas id="grafico-totais"></canvas></div>
    <?php else: ?>
      <p>Nenhuma transação registrada neste período.</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($categorias): ?>
  <h2>Total gasto por categoria</h2>
  <table>
    <tr><th>Categoria</th><th>Total gasto</th><th>Parte das despesas</th></tr>
    <?php foreach ($categorias as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['categoria']) ?></td>
        <td><?= moeda($c['total']) ?></td>
        <td><?= number_format($c['total'] / $totalDespesas * 100, 1, ',', '.') ?>%</td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
if (typeof Chart !== 'undefined') {
  const formatoMoeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
  const cores = ['#003087', '#0070E0', '#5BA8F0', '#0B8A5F', '#6CC7A3', '#F2A93B', '#D92D3A', '#8A94A6'];
  const dadosCategorias = <?= json_encode($dadosCategorias, JSON_UNESCAPED_UNICODE) ?>;
  const dadosTotais = <?= json_encode($dadosTotais) ?>;

  Chart.defaults.font.family = 'Inter, "Helvetica Neue", Helvetica, Arial, system-ui, sans-serif';
  Chart.defaults.color = '#5B6573';

  const canvasCategorias = document.getElementById('grafico-categorias');
  if (canvasCategorias) {
    new Chart(canvasCategorias, {
      type: 'doughnut',
      data: {
        labels: dadosCategorias.rotulos,
        datasets: [{
          data: dadosCategorias.valores,
          backgroundColor: dadosCategorias.rotulos.map((_, i) => cores[i % cores.length]),
          borderColor: '#FFFFFF',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: contexto => ' ' + contexto.label + ': ' + formatoMoeda.format(contexto.parsed)
            }
          }
        }
      }
    });
  }

  const canvasTotais = document.getElementById('grafico-totais');
  if (canvasTotais) {
    new Chart(canvasTotais, {
      type: 'bar',
      data: {
        labels: ['Entradas', 'Despesas'],
        datasets: [{
          data: dadosTotais,
          backgroundColor: ['#0B8A5F', '#D92D3A'],
          borderRadius: 8,
          maxBarThickness: 90
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: contexto => ' ' + formatoMoeda.format(contexto.parsed.y)
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: valor => formatoMoeda.format(valor) }
          }
        }
      }
    });
  }
}
</script>

</main>
</body>
</html>
