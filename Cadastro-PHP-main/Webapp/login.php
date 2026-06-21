<?php
// ============================================================
// login.php — Formulário e processamento do login
// ============================================================

session_start();

// Se já está logado, vai direto para o dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../Entity/db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $pdo  = getConexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --verde-folha: #1a5c2a;
            --verde-medio: #2e7d3c;
            --verde-claro: #4caf50;
            --amarelo: #f5c518;
            --laranja: #e8621a;
            --rosa: #d4365a;
            --azul-ceu: #5bb8d4;
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
                overflow: auto; /* permito rolagem se conteúdo exceder a viewport */
                -webkit-overflow-scrolling: touch; /* rolagem suave em iOS */
                position: relative;
        }

        /* ——— Canvas animado ——— */
        #bg-canvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        /* ——— Overlay escuro suave ——— */
        .overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center, rgba(10,40,15,0.45) 0%, rgba(5,20,8,0.75) 100%);
            z-index: 1;
        }

        /* ——— Card ——— */
        .card-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .card {
            background: rgba(253, 246, 227, 0.96);
            border-radius: 20px;
            padding: 2.5rem 2rem 2rem;
            box-shadow: 0 24px 60px rgba(0,0,0,0.5);
            border: 2px solid rgba(245, 197, 24, 0.4);
            position: relative;
            overflow: hidden;
        }

        /* Detalhe decorativo no canto superior */
        .card::before {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 120px;
            height: 120px;
            background: var(--amarelo);
            border-radius: 50%;
            opacity: 0.15;
        }

        .card-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .card-brand .icon-toucan {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.25rem;
        }

        .card-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            color: var(--verde-folha);
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .card-brand p {
            font-size: 0.85rem;
            color: #6b7a5e;
            margin-top: 0.25rem;
        }

        /* Divider decorativo */
        .divider-tropical {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0 1.5rem;
        }
        .divider-tropical span {
            flex: 1;
            height: 1.5px;
            background: linear-gradient(to right, transparent, #c8b87a, transparent);
        }
        .divider-tropical em {
            font-style: normal;
            font-size: 1rem;
            color: var(--laranja);
        }

        /* Campos */
        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--verde-folha);
            margin-bottom: 0.35rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

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
            border-color: var(--verde-medio);
            box-shadow: 0 0 0 3px rgba(46,125,60,0.15);
            background: #fff;
        }

        .mb-3 { margin-bottom: 1rem; }

        /* Alerta de erro */
        .alert-erro {
            background: #fff0f3;
            border: 1.5px solid #f4a0b0;
            color: #9b2335;
            border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
        }

        /* Botão principal */
        .btn-entrar {
            display: block;
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, var(--verde-medio) 0%, var(--verde-folha) 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 16px rgba(26,92,42,0.35);
            position: relative;
            overflow: hidden;
        }

        .btn-entrar::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(245,197,24,0.12);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .btn-entrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26,92,42,0.45);
        }
        .btn-entrar:hover::after { opacity: 1; }
        .btn-entrar:active { transform: translateY(0); }

        /* Rodapé */
        .card-footer {
            margin-top: 1.5rem;
            text-align: center;
        }
        .card-footer hr {
            border: none;
            border-top: 1px solid #ddd5b8;
            margin-bottom: 1rem;
        }
        .card-footer p {
            font-size: 0.88rem;
            color: #7a6e52;
        }
        .card-footer a {
            color: var(--laranja);
            font-weight: 500;
            text-decoration: none;
        }
        .card-footer a:hover { text-decoration: underline; }

        /* Faixa de cores brasileiro na base do card */
        .bandeira-strip {
            display: flex;
            height: 5px;
            border-radius: 0 0 18px 18px;
            overflow: hidden;
            position: absolute;
            bottom: 0; left: 0; right: 0;
        }
        .bandeira-strip span {
            flex: 1;
        }
    </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="overlay"></div>

<div class="card-wrapper">
    <div class="card">

        <div class="card-brand">
            <span class="icon-toucan">🦜</span>
            <h1>Bem-vindo</h1>
            <p>Acesse sua conta</p>
        </div>

        <div class="divider-tropical">
            <span></span>
            <em>🌺</em>
            <span></span>
        </div>

        <?php if ($erro): ?>
            <div class="alert-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" novalidate>
            <div class="mb-3">
                <label for="email">E-mail</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autofocus
                    placeholder="seu@email.com"
                >
            </div>
            <div class="mb-3">
                <label for="senha">Senha</label>
                <input
                    type="password"
                    id="senha"
                    name="senha"
                    required
                    placeholder="••••••••"
                >
            </div>
            <button type="submit" class="btn-entrar">Entrar 🌿</button>
        </form>

        <div class="card-footer">
            <hr>
            <p>Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
        </div>

        <!-- Faixa verde-amarela-azul na base -->
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
// ——— Animação de fundo: folhas e flores tropicais ———
const canvas = document.getElementById('bg-canvas');
const ctx = canvas.getContext('2d');

function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
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

    drawLeaf(ctx) {
        ctx.beginPath();
        ctx.save();
        ctx.scale(1, 2.2);
        ctx.arc(0, 0, this.size * 0.38, 0, Math.PI * 2);
        ctx.restore();
        ctx.fillStyle = this.color;
        ctx.fill();
        // midrib
        ctx.beginPath();
        ctx.moveTo(0, -this.size * 0.55);
        ctx.lineTo(0, this.size * 0.55);
        ctx.strokeStyle = 'rgba(0,0,0,0.15)';
        ctx.lineWidth = 1.2;
        ctx.stroke();
    }

    drawFlower(ctx) {
        const petals = 5;
        const pr = this.size * 0.35;
        for (let i = 0; i < petals; i++) {
            const angle = (i / petals) * Math.PI * 2;
            ctx.beginPath();
            ctx.ellipse(Math.cos(angle) * pr * 0.55, Math.sin(angle) * pr * 0.55, pr * 0.5, pr * 0.22, angle, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.fill();
        }
        ctx.beginPath();
        ctx.arc(0, 0, this.size * 0.14, 0, Math.PI * 2);
        ctx.fillStyle = '#f5c518';
        ctx.fill();
    }

    drawPetal(ctx) {
        ctx.beginPath();
        ctx.ellipse(0, 0, this.size * 0.2, this.size * 0.45, 0, 0, Math.PI * 2);
        ctx.fillStyle = this.color;
        ctx.fill();
    }

    update() {
        this.wobble += this.wobbleSpeed;
        this.x += this.drift + Math.sin(this.wobble) * 0.5;
        this.y += this.speed;
        this.rotation += this.rotSpeed;
        if (this.y > canvas.height + 80) this.reset(false);
    }

    draw(ctx) {
        ctx.save();
        ctx.translate(this.x, this.y);
        ctx.rotate(this.rotation);
        ctx.globalAlpha = this.opacity;
        if (this.type === 'leaf') this.drawLeaf(ctx);
        else if (this.type === 'flower') this.drawFlower(ctx);
        else this.drawPetal(ctx);
        ctx.restore();
    }
}

let particles = [];
function initParticles() {
    const count = Math.min(55, Math.floor((canvas.width * canvas.height) / 14000));
    particles = Array.from({ length: count }, () => new Leaf());
}
initParticles();

function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Fundo gradiente
    const bg = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
    bg.addColorStop(0, '#0d3318');
    bg.addColorStop(0.5, '#1a5c2a');
    bg.addColorStop(1, '#0a2210');
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    particles.forEach(p => { p.update(); p.draw(ctx); });
    requestAnimationFrame(animate);
}
animate();
</script>

</body>
</html>