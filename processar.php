<?php
/** Tech Experience 2026: método HTTP, normalização, validação e escape. */
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');

// Escapa todos os textos dinâmicos antes de inseri-los no HTML (XSS).
function e(string $dado): string
{
    return htmlspecialchars($dado, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// mb_strlen conta caracteres Unicode. O fallback funciona sem mbstring.
function tamanhoTexto(string $dado): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($dado, 'UTF-8');
    }
    $total = preg_match_all('/./us', $dado);
    return $total === false ? strlen($dado) : $total;
}

$erros = [];
$metodoValido = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$nome = $email = $experiencia = $turno = $mensagem = '';
$oficinas = [];
// Whitelists: chaves aceitas e rótulos utilizados na confirmação.
$niveisPermitidos = ['iniciante' => 'Iniciante', 'intermediario' => 'Intermediário', 'avancado' => 'Avançado'];
$turnosPermitidos = ['manha' => 'Manhã', 'noite' => 'Noite'];
$oficinasPermitidas = ['html_css' => 'HTML5 e CSS3', 'js_dom' => 'JavaScript e DOM', 'php_backend' => 'PHP Backend'];

if (!$metodoValido) {
    // Nenhuma lógica de inscrição é executada se o método não for POST.
    http_response_code(405);
    header('Allow: POST');
    $erros[] = 'Método não permitido. Envie os dados pelo formulário de inscrição.';
} else {
    // Normalização: rejeita arrays e UTF-8 inválido antes de usar trim().
    $campos = ['nome' => 'Nome', 'email' => 'E-mail', 'experiencia' => 'Nível de experiência', 'turno' => 'Turno', 'mensagem' => 'Mensagem'];
    $dados = [];
    foreach ($campos as $campo => $rotulo) {
        $valor = $_POST[$campo] ?? '';
        if (!is_string($valor) || preg_match('//u', $valor) !== 1) {
            $erros[] = $rotulo . ': formato de texto inválido.';
            $dados[$campo] = '';
        } else {
            $dados[$campo] = trim($valor);
        }
    }
    $nome = $dados['nome'];
    $email = $dados['email'];
    $experiencia = $dados['experiencia'];
    $turno = $dados['turno'];
    $mensagem = $dados['mensagem'];
    $oficinasRecebidas = $_POST['oficinas'] ?? [];

    // Validação no servidor, independente das barreiras do navegador.
    if (tamanhoTexto($nome) < 3) {
        $erros[] = 'Informe um nome com pelo menos 3 caracteres.';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $erros[] = 'Informe um endereço de e-mail válido.';
    }
    if (!in_array($experiencia, array_keys($niveisPermitidos), true)) {
        $erros[] = 'Selecione um nível de experiência válido.';
    }
    if (!in_array($turno, array_keys($turnosPermitidos), true)) {
        $erros[] = 'Selecione um turno válido (manhã ou noite).';
    }
    // oficinas[] precisa ser uma lista de strings pertencentes à whitelist.
    if (!is_array($oficinasRecebidas) || $oficinasRecebidas !== array_values($oficinasRecebidas)) {
        $erros[] = 'O formato das oficinas selecionadas é inválido.';
    } elseif ($oficinasRecebidas === []) {
        $erros[] = 'Selecione pelo menos uma oficina de interesse.';
    } else {
        foreach ($oficinasRecebidas as $oficina) {
            if (!is_string($oficina) || !in_array(trim($oficina), array_keys($oficinasPermitidas), true)) {
                $erros[] = 'Uma das oficinas selecionadas é inválida.';
                break;
            }
            $oficinas[] = trim($oficina);
        }
        $oficinas = array_values(array_unique($oficinas));
    }
    // Normaliza quebras de linha enviadas pelo navegador antes de contar.
    $mensagemOriginal = $_POST['mensagem'] ?? '';
    if (is_string($mensagemOriginal) && preg_match('//u', $mensagemOriginal) === 1) {
        $mensagemOriginal = str_replace(["\r\n", "\r"], "\n", $mensagemOriginal);
        // O maxlength do HTML conta unidades UTF-16; alguns símbolos valem duas.
        $extras = preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $mensagemOriginal);
        if (tamanhoTexto($mensagemOriginal) + $extras > 200) {
            $erros[] = 'A mensagem deve ter no máximo 200 caracteres.';
        }
    }
    http_response_code($erros === [] ? 200 : 422);
}
$sucesso = $metodoValido && $erros === [];
$titulo = $sucesso ? 'Inscrição confirmada!' : ($metodoValido ? 'Revise seus dados' : 'Método não permitido');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Tech Experience 2026</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a class="skip" href="#conteudo">Pular para o conteúdo</a>
    <header class="header"><div class="header-inner">
        <a class="brand" href="index.html"><span class="mark" aria-hidden="true">&lt;/&gt;</span><span>TECH EXPERIENCE<small>2026</small></span></a>
        <span class="tag">Evento acadêmico</span>
    </div></header>
    <main class="result" id="conteudo">
        <section class="panel" aria-labelledby="titulo-resultado">
            <?php if ($sucesso): ?>
                <span class="status">DADOS VALIDADOS</span>
                <h1 id="titulo-resultado"><?= e($titulo) ?></h1>
                <p class="description">Seu formulário foi recebido e validado com sucesso. Confira os dados da sua participação.</p>
                <dl class="summary">
                    <div class="summary-row"><dt>Nome completo</dt><dd><?= e($nome) ?></dd></div>
                    <div class="summary-row"><dt>E-mail</dt><dd><?= e($email) ?></dd></div>
                    <div class="summary-row"><dt>Nível de experiência</dt><dd><?= e($niveisPermitidos[$experiencia]) ?></dd></div>
                    <div class="summary-row"><dt>Turno de preferência</dt><dd><?= e($turnosPermitidos[$turno]) ?></dd></div>
                    <div class="summary-row"><dt>Oficinas selecionadas</dt><dd><ul><?php foreach ($oficinas as $oficina): ?><li><?= e($oficinasPermitidas[$oficina]) ?></li><?php endforeach; ?></ul></dd></div>
                    <div class="summary-row"><dt>Mensagem / Motivação</dt><dd><?= e($mensagem === '' ? 'Não informada' : $mensagem) ?></dd></div>
                </dl>
                <p class="help">Projeto acadêmico: os dados não são armazenados em banco de dados.</p>
                <a class="button" href="index.html">Realizar nova inscrição</a>
            <?php else: ?>
                <span class="status status-error"><?= e($metodoValido ? 'ERRO DE VALIDAÇÃO · 422' : 'ERRO HTTP · 405') ?></span>
                <h1 id="titulo-resultado"><?= e($titulo) ?></h1>
                <p class="description">Confira as informações abaixo para continuar.</p>
                <ul class="error-list">
                    <?php foreach ($erros as $erro): ?>
                        <li><?= e($erro) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="button" href="index.html">Voltar ao formulário</a>
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <div>Tech Experience 2026 · Desenvolvimento Web</div>
        <div class="autoria">Desenvolvido por Júlio César Alves da Silva<br>
            Matrícula: 202508389401 · UNIFAVIP · 2026.2</div>
    </footer>
</body>
</html>
