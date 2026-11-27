<?php
session_start();

const CATEGORIA_POUPANCA = 'Poupança/Investimento';

$pdo = new PDO('mysql:host=localhost;dbname=wallety;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function exigir_login() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function categorias_padrao() {
    return [
        'despesa' => [
            'Alimentação',
            'Moradia',
            'Transporte',
            'Saúde',
            'Educação',
            'Lazer',
            'Compras',
            'Contas e serviços',
            CATEGORIA_POUPANCA,
            'Outros',
        ],
        'entrada' => [
            'Salário',
            'Renda extra',
            'Investimentos',
            'Presente',
            'Outros',
        ],
    ];
}

function moeda($valor) {
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function valor_valido($valor) {
    return $valor > 0 && $valor <= 99999999.99;
}

function nome_mes($mes) {
    $nomes = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];
    [$ano, $numero] = explode('-', $mes);
    return $nomes[(int) $numero] . '/' . $ano;
}

function intervalo_mes($mes) {
    $inicio = $mes . '-01';
    $fim = date('Y-m-d', strtotime($inicio . ' +1 month'));
    return [$inicio, $fim];
}
