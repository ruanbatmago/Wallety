<?php
require 'conexao.php';

if (isset($_GET['sair'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$mensagem = '';
$classe = 'aviso';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($nome === '' || $email === '' || strlen($senha) < 6) {
            $mensagem = 'Preencha todos os campos. A senha precisa ter pelo menos 6 caracteres.';
            $classe = 'aviso erro';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)');
            try {
                $stmt->execute([$nome, $email, $hash]);
                $mensagem = 'Conta criada! Agora é só entrar.';
                $classe = 'aviso sucesso';
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $mensagem = 'Esse e-mail já está cadastrado.';
                $classe = 'aviso erro';
            }
        }
    }

    if ($acao === 'entrar') {
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([trim($_POST['email'] ?? '')]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($_POST['senha'] ?? '', $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            header('Location: index.php');
            exit;
        }
        $mensagem = 'E-mail ou senha incorretos.';
        $classe = 'aviso erro';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Entrar · Wallety</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="login">
  <h1>Wallety</h1>
  <?php if ($mensagem): ?>
    <p class="<?= $classe ?>"><?= htmlspecialchars($mensagem) ?></p>
  <?php endif; ?>

  <form method="post" class="card">
    <h2>Entrar</h2>
    <input type="hidden" name="acao" value="entrar">
    <label>E-mail <input type="email" name="email" required></label>
    <label>Senha <input type="password" name="senha" required></label>
    <button type="submit">Entrar</button>
  </form>

  <form method="post" class="card">
    <h2>Criar conta</h2>
    <input type="hidden" name="acao" value="cadastrar">
    <label>Nome <input type="text" name="nome" maxlength="100" required></label>
    <label>E-mail <input type="email" name="email" maxlength="150" required></label>
    <label>Senha <input type="password" name="senha" minlength="6" required></label>
    <button type="submit">Criar conta</button>
  </form>
</main>
</body>
</html>
