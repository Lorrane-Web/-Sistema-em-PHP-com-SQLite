<?php
// ============================================================
// dashboard.php — Área restrita (somente usuários logados)
// ============================================================

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../Entity/db.php';

$pdo  = getConexao();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
$stmt->execute([':id' => $_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<?php
// Fallback simples para obter a primeira letra em maiúscula sem mbstring
if (!function_exists('firstCharUpper')) {
    function firstCharUpper(string $s): string {
        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
        }
        return strtoupper(substr($s, 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
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
            font-family: 'DM Sans', sans-serif;
            background-color: #0d3318;
            overflow-x: hidden;
            overflow-y: auto; /* permitir rolagem vertical no dashboard */
            -webkit-overflow-scrolling: touch; /* rolagem suave em iOS */
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
            background: radial-gradient(ellipse at 70% 20%, rgba(10,40,15,0.35) 0%, rgba(5,20,8,0.80) 100%);
            z-index: 1;
        }

        /* ——— Navbar ——— */
        .navbar {
            position: relative;
            z-index: 100;
            background: rgba(10, 35, 15, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1.5px solid rgba(245, 197, 24, 0.25);
            padding: 0.9rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .navbar-brand .icon { font-size: 1.5rem; }
        .navbar-brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--amarelo);
            letter-spacing: -0.3px;
        }

        .btn-sair {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 1.1rem;
            background: transparent;
            border: 1.5px solid rgba(245,197,24,0.5);
            border-radius: 8px;
            color: var(--amarelo);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        .btn-sair:hover {
            background: rgba(245,197,24,0.12);
            border-color: var(--amarelo);
        }

        /* ——— Conteúdo ——— */
        .main-content {
            position: relative;
            z-index: 10;
            max-width: 680px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        /* Cabeçalho de boas-vindas */
        .welcome-header {
            background: rgba(253,246,227,0.95);
            border-radius: 20px 20px 0 0;
            padding: 2rem 2rem 1.5rem;
            border: 2px solid rgba(245,197,24,0.3);
            border-bottom: none;
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .avatar {
            width: 58px; height: 58px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--verde-medio), var(--verde-folha));
            display: flex; align-items: center; justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--amarelo);
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .welcome-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            color: var(--verde-folha);
        }
        .welcome-text p {
            font-size: 0.88rem;
            color: #7a8c6a;
            margin-top: 0.2rem;
        }

        /* Card de dados */
        .data-card {
            background: rgba(253,246,227,0.96);
            border-radius: 0 0 20px 20px;
            border: 2px solid rgba(245,197,24,0.3);
            border-top: 1.5px solid #ddd5b8;
            overflow: hidden;
            position: relative;
        }

        .data-card table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-card tr {
            border-bottom: 1px solid #ede8d8;
            transition: background 0.15s;
        }
        .data-card tr:last-child { border-bottom: none; }
        .data-card tr:hover { background: rgba(245,197,24,0.07); }

        .data-card td {
            padding: 0.95rem 1.5rem;
            font-size: 0.93rem;
            color: var(--marrom);
        }

        .data-card td:first-child {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--verde-medio);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 42%;
        }

        .data-card td:first-child .label-inner {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .data-card td .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--laranja);
            flex-shrink: 0;
            display: inline-block;
        }

        /* Faixa bandeira */
        .bandeira-strip {
            display: flex; height: 5px;
            border-radius: 0 0 18px 18px; overflow: hidden;
        }
        .bandeira-strip span { flex: 1; }

        /* Tag de status */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.75rem;
            background: rgba(46,125,60,0.12);
            border: 1px solid rgba(46,125,60,0.3);
            border-radius: 20px;
            font-size: 0.78rem;
            color: var(--verde-medio);
            font-weight: 500;
            margin-top: 0.25rem;
        }
        .status-badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--verde-claro, #4caf50);
        }
    </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="overlay"></div>

<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <span class="icon">🦜</span>
        <h1>Sistema de Cadastro PHP</h1>
    </div>
    <a href="logout.php" class="btn-sair">Sair </a>
</nav>

<!-- Conteúdo principal -->
<div class="main-content">

    <div class="welcome-header">
        <div class="avatar">
            <?= htmlspecialchars(firstCharUpper($usuario['nome'])) ?>
        </div>
        <div class="welcome-text">
            <h2>Olá, <?= htmlspecialchars($usuario['nome']) ?>!</h2>
            <p>Você está autenticado com sucesso.</p>
            <span class="status-badge">Sessão ativa</span>
        </div>
    </div>

    <div class="data-card">
        <table>

            <tr>
                <td><span class="label-inner"><span class="dot"></span>Nome</span></td>
                <td><?= htmlspecialchars($usuario['nome']) ?></td>
            </tr>
            <tr>
                <td><span class="label-inner"><span class="dot"></span>E-mail</span></td>
                <td><?= htmlspecialchars($usuario['email']) ?></td>
            </tr>
            
        </table>

        <div class="bandeira-strip">
            <span style="background:#009c3b;"></span>
            <span style="background:#009c3b;"></span>
            <span style="background:#ffdf00;"></span>
            <span style="background:#ffdf00;"></span>
            <span style="background:#002776;"></span>
            <span style="background:#002776;"></span>
        </div>
    </div>

<div style="margin: 20px 0; text-align: center;">
    <a href="shows.php" style="display: inline-block; padding: 12px 24px; background: #20e90e; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Acessar Painel de Shows & Ingressos
    </a>
</div>

</div>

<?php
// Seção pública de listagem de todos os usuários (apenas leitura)
$stmtAll = $pdo->query('SELECT id,nome,email,criado_em FROM usuarios ORDER BY id DESC');
$usuarios = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Seção de debug: print dos dados do usuário e da sessão -->
<div style="max-width:680px;margin:1.25rem auto;padding:0 1rem;">
    <h3 style="color:var(--amarelo);font-family:'Playfair Display',serif;margin-bottom:0.5rem;">Dados do usuário (raw)</h3>
    <pre style="background:#fff;padding:1rem;border-radius:10px;border:1px solid #e6dfc8;overflow:auto;">
<?php
echo htmlspecialchars(json_encode($usuario, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
?>
    </pre>

    <h4 style="color:var(--verde-medio);font-family:'DM Sans',sans-serif;margin-top:0.6rem;">Sessão</h4>
    <pre style="background:#fff;padding:1rem;border-radius:10px;border:1px solid #e6dfc8;overflow:auto;">
<?php
echo htmlspecialchars(json_encode($_SESSION ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
?>
    </pre>
</div>

<div class="main-content">
    <div style="max-width:680px;margin:2rem auto;padding:0 1rem;">
        <h3 style="color:var(--amarelo);font-family:'Playfair Display',serif;margin-bottom:0.75rem;">Lista de usuários cadastrados</h3>
        <div class="data-card">
            <table>
                <thead>
                    <tr style="background:rgba(0,0,0,0.03);font-weight:600;">
                        <td>ID</td>
                        <td>Nome</td>
                        <td>E-mail</td>
                        <td>Cadastrado em</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['id']) ?></td>
                            <td><?= htmlspecialchars($u['nome']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['criado_em']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
        c.beginPath(); c.moveTo(0,-this.size*0.55); c.lineTo(0,this.size*0.55);
        c.strokeStyle='rgba(0,0,0,0.15)'; c.lineWidth=1.2; c.stroke();
    }
    drawFlower(c) {
        for (let i=0;i<5;i++){
            const a=(i/5)*Math.PI*2, pr=this.size*0.35;
            c.beginPath();
            c.ellipse(Math.cos(a)*pr*0.55,Math.sin(a)*pr*0.55,pr*0.5,pr*0.22,a,0,Math.PI*2);
            c.fillStyle=this.color; c.fill();
        }
        c.beginPath(); c.arc(0,0,this.size*0.14,0,Math.PI*2);
        c.fillStyle='#f5c518'; c.fill();
    }
    drawPetal(c) {
        c.beginPath();
        c.ellipse(0,0,this.size*0.2,this.size*0.45,0,0,Math.PI*2);
        c.fillStyle=this.color; c.fill();
    }
    update() {
        this.wobble += this.wobbleSpeed;
        this.x += this.drift + Math.sin(this.wobble)*0.5;
        this.y += this.speed;
        this.rotation += this.rotSpeed;
        if (this.y > canvas.height+80) this.reset(false);
    }
    draw(c) {
        c.save(); c.translate(this.x,this.y); c.rotate(this.rotation);
        c.globalAlpha=this.opacity;
        if(this.type==='leaf') this.drawLeaf(c);
        else if(this.type==='flower') this.drawFlower(c);
        else this.drawPetal(c);
        c.restore();
    }
}

let particles=[];
function initParticles(){
    const n=Math.min(55,Math.floor((canvas.width*canvas.height)/14000));
    particles=Array.from({length:n},()=>new Leaf());
}
initParticles();

function animate(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    const bg=ctx.createLinearGradient(0,0,canvas.width,canvas.height);
    bg.addColorStop(0,'#0d3318'); bg.addColorStop(0.5,'#1a5c2a'); bg.addColorStop(1,'#0a2210');
    ctx.fillStyle=bg; ctx.fillRect(0,0,canvas.width,canvas.height);
    particles.forEach(p=>{p.update();p.draw(ctx);});
    requestAnimationFrame(animate);
}
animate();
</script>

</body>
</html>