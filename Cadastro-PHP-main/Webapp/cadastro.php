<?php
// ============================================================
// cadastro.php — Formulário e processamento do cadastro
// ============================================================

session_start();

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../Entity/db.php';

$erro    = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $senha    = $_POST['senha']         ?? '';
    $confirma = $_POST['confirma']      ?? '';

    if (empty($nome) || empty($email) || empty($senha) || empty($confirma)) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } else {
        $pdo  = getConexao();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash   = password_hash($senha, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)');
            $insert->execute([':nome' => $nome, ':email' => $email, ':senha' => $hash]);
            // Após criar o usuário, autentica automaticamente e redireciona ao dashboard
            $novoId = $pdo->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int) $novoId;
            $_SESSION['usuario_nome'] = $nome;
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --verde-folha: #1a5c2a;
            --verde-medio: #2e7d3c;
            --laranja: #e8621a;
            --rosa: #d4365a;
            --amarelo: #f5c518;
            --azul: #5bb8d4;
            --creme: #fdf6e3;
            --marrom: #3b1f0e;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', sans-serif;
            background-color: #0d3318;
            overflow: auto; /* permitir rolagem quando necessário */
            -webkit-overflow-scrolling: touch; /* rolagem suave em iOS */
            position: relative;
        }

        #bg-canvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(10,40,15,0.45) 0%, rgba(5,20,8,0.75) 100%);
            z-index: 1;
        }

        .card-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 1rem;
        }

        .card {
            background: rgba(253, 246, 227, 0.96);
            border-radius: 20px;
            padding: 2.5rem 2rem 2rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
            border: 2px solid rgba(232, 98, 26, 0.35);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 120px; height: 120px;
            background: var(--laranja);
            border-radius: 50%;
            opacity: 0.12;
        }

        .card-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .card-brand .icon { font-size: 2.5rem; display: block; margin-bottom: 0.25rem; }
        .card-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            color: var(--laranja);
            letter-spacing: -0.5px;
        }
        .card-brand p { font-size: 0.85rem; color: #6b7a5e; margin-top: 0.25rem; }

        .divider-tropical {
            display: flex; align-items: center; gap: 0.75rem;
            margin: 1.25rem 0 1.5rem;
        }
        .divider-tropical span { flex: 1; height: 1.5px; background: linear-gradient(to right, transparent, #c8b87a, transparent); }
        .divider-tropical em { font-style: normal; font-size: 1rem; color: var(--verde-medio); }

        label {
            display: block;
            font-size: 0.78rem; font-weight: 500;
            color: var(--verde-folha);
            margin-bottom: 0.35rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        label small { text-transform: none; font-size: 0.75rem; color: #9a8a6a; font-weight: 400; }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #c9d8b8;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            background: #f7f2e4;
            color: var(--marrom);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        input:focus {
            border-color: var(--laranja);
            box-shadow: 0 0 0 3px rgba(232,98,26,0.15);
            background: #fff;
        }

        .mb-3 { margin-bottom: 1rem; }

        .alert-erro {
            background: #fff0f3; border: 1.5px solid #f4a0b0;
            color: #9b2335; border-radius: 10px;
            padding: 0.65rem 1rem; font-size: 0.88rem; margin-bottom: 1.25rem;
        }
        .alert-sucesso {
            background: #f0fdf4; border: 1.5px solid #86efac;
            color: #166534; border-radius: 10px;
            padding: 0.65rem 1rem; font-size: 0.88rem; margin-bottom: 1.25rem;
        }
        .alert-sucesso a { color: var(--laranja); font-weight: 500; }

        .btn-cadastrar {
            display: block; width: 100%; padding: 0.85rem;
            background: linear-gradient(135deg, #e8621a 0%, #b84a0e 100%);
            color: #fff; border: none; border-radius: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 16px rgba(232,98,26,0.35);
            position: relative; overflow: hidden;
        }
        .btn-cadastrar::after {
            content: ''; position: absolute; inset: 0;
            background: rgba(245,197,24,0.15); opacity: 0;
            transition: opacity 0.2s;
        }
        .btn-cadastrar:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(232,98,26,0.45); }
        .btn-cadastrar:hover::after { opacity: 1; }
        .btn-cadastrar:active { transform: translateY(0); }

        .card-footer-area { margin-top: 1.5rem; text-align: center; }
        .card-footer-area hr { border: none; border-top: 1px solid #ddd5b8; margin-bottom: 1rem; }
        .card-footer-area p { font-size: 0.88rem; color: #7a6e52; }
        .card-footer-area a { color: var(--verde-medio); font-weight: 500; text-decoration: none; }
        .card-footer-area a:hover { text-decoration: underline; }

        .bandeira-strip {
            display: flex; height: 5px;
            border-radius: 0 0 18px 18px; overflow: hidden;
            position: absolute; bottom: 0; left: 0; right: 0;
        }
        .bandeira-strip span { flex: 1; }
    </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="overlay"></div>

<div class="card-wrapper">
    <div class="card">

        <div class="card-brand">
            <span class="icon">🌺</span>
            <h1>Criar conta</h1>
            <p>Junte-se a nós</p>
        </div>

        <div class="divider-tropical">
            <span></span><em>🌿</em><span></span>
        </div>

        <?php if ($erro): ?>
            <div class="alert-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert-sucesso">
                <?= htmlspecialchars($sucesso) ?>
                <a href="login.php">Ir para o login →</a>
            </div>
        <?php endif; ?>

        <form method="post" action="cadastro.php" novalidate>
            <div class="mb-3">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome"
                    value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                    required autofocus placeholder="Seu nome">
            </div>
            <div class="mb-3">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required placeholder="seu@email.com">
            </div>
            <div class="mb-3">
                <label for="senha">Senha <small>(mín. 6 caracteres)</small></label>
                <input type="password" id="senha" name="senha" required placeholder="••••••••">
            </div>
            <div class="mb-3">
                <label for="confirma">Confirmar senha</label>
                <input type="password" id="confirma" name="confirma" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn-cadastrar">Cadastrar 🌺</button>
        </form>

        <div class="card-footer-area">
            <hr>
            <p>Já tem conta? <a href="login.php">Fazer login</a></p>
        </div>

        <div class="bandeira-strip">
            <span style="background:#009c3b;"></span>
            <span style="background:#009c3b;"></span>
            <span style="background:#ffdf00;"></span>
            <span style="background:#ffdf00;"></span>
            <span style="background:#002776;"></span>
            <span style="background:#002776;"></span>
        </div>
    </div>
</div>

<script>
const canvas = document.getElementById('bg-canvas');
const ctx = canvas.getContext('2d');

function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
resize();
window.addEventListener('resize', () => { resize(); initParticles(); });

const COLORS = ['#1a5c2a','#2e7d3c','#4caf50','#81c784','#e8621a','#d4365a','#5bb8d4','#f5c518','#a5d6a7'];

class Leaf {
    constructor() { this.reset(true); }
    reset(initial) {
        this.x = Math.random() * canvas.width;
        this.y = initial ? Math.random() * canvas.height : -80;
        this.size = 18 + Math.random() * 40;
        this.speed = 0.4 + Math.random() * 1.0;
        this.drift = (Math.random() - 0.5) * 0.6;
        this.rotation = Math.random() * Math.PI * 2;
        this.rotSpeed = (Math.random() - 0.5) * 0.025;
        this.opacity = 0.25 + Math.random() * 0.55;
        this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
        this.type = Math.random() < 0.55 ? 'leaf' : Math.random() < 0.5 ? 'flower' : 'petal';
        this.wobble = Math.random() * Math.PI * 2;
        this.wobbleSpeed = 0.02 + Math.random() * 0.03;
    }
    drawLeaf(c) {
        c.beginPath(); c.save(); c.scale(1, 2.2);
        c.arc(0, 0, this.size * 0.38, 0, Math.PI * 2);
        c.restore(); c.fillStyle = this.color; c.fill();
        c.beginPath(); c.moveTo(0, -this.size*0.55); c.lineTo(0, this.size*0.55);
        c.strokeStyle = 'rgba(0,0,0,0.15)'; c.lineWidth = 1.2; c.stroke();
    }
    drawFlower(c) {
        for (let i = 0; i < 5; i++) {
            const a = (i/5)*Math.PI*2, pr = this.size*0.35;
            c.beginPath();
            c.ellipse(Math.cos(a)*pr*0.55, Math.sin(a)*pr*0.55, pr*0.5, pr*0.22, a, 0, Math.PI*2);
            c.fillStyle = this.color; c.fill();
        }
        c.beginPath(); c.arc(0,0,this.size*0.14,0,Math.PI*2);
        c.fillStyle = '#f5c518'; c.fill();
    }
    drawPetal(c) {
        c.beginPath();
        c.ellipse(0,0,this.size*0.2,this.size*0.45,0,0,Math.PI*2);
        c.fillStyle = this.color; c.fill();
    }
    update() {
        this.wobble += this.wobbleSpeed;
        this.x += this.drift + Math.sin(this.wobble)*0.5;
        this.y += this.speed;
        this.rotation += this.rotSpeed;
        if (this.y > canvas.height + 80) this.reset(false);
    }
    draw(c) {
        c.save(); c.translate(this.x, this.y); c.rotate(this.rotation);
        c.globalAlpha = this.opacity;
        if (this.type==='leaf') this.drawLeaf(c);
        else if (this.type==='flower') this.drawFlower(c);
        else this.drawPetal(c);
        c.restore();
    }
}

let particles = [];
function initParticles() {
    const n = Math.min(55, Math.floor((canvas.width*canvas.height)/14000));
    particles = Array.from({length:n}, () => new Leaf());
}
initParticles();

function animate() {
    ctx.clearRect(0,0,canvas.width,canvas.height);
    const bg = ctx.createLinearGradient(0,0,canvas.width,canvas.height);
    bg.addColorStop(0,'#1a1a0a'); bg.addColorStop(0.5,'#3b1f0e'); bg.addColorStop(1,'#1a0a0a');
    ctx.fillStyle = bg; ctx.fillRect(0,0,canvas.width,canvas.height);
    particles.forEach(p => { p.update(); p.draw(ctx); });
    requestAnimationFrame(animate);
}
animate();
</script>

</body>
</html>