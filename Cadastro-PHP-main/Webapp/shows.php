<?php
// ============================================================
// shows.php — Gerenciador de Shows e Emissão de Ingressos
// ============================================================
session_start();


if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../Entity/db.php';

try {
    $pdo = getConexao();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shows (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            artista TEXT NOT NULL,
            data_show TEXT NOT NULL,
            local TEXT NOT NULL,
            preco REAL NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ingressos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            show_id INTEGER NOT NULL,
            usuario_id INTEGER NOT NULL,
            comprado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (show_id) REFERENCES shows(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    ");
} catch (Exception $e) {
    die("Erro ao conectar ou preparar tabelas: " . $e->getMessage());
}

$mensagem = '';

// AÇÃO 1: Cadastrar um Novo Show
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_show'])) {
    $artista   = trim($_POST['artista'] ?? '');
    $data_show = trim($_POST['data_show'] ?? '');
    $local     = trim($_POST['local'] ?? '');
    $preco     = (float)($_POST['preco'] ?? 0);

    if (!empty($artista) && !empty($data_show) && !empty($local)) {
        $stmt = $pdo->prepare('INSERT INTO shows (artista, data_show, local, preco) VALUES (:artista, :data_show, :local, :preco)');
        $stmt->execute([
            ':artista'   => $artista,
            ':data_show' => $data_show,
            ':local'     => $local,
            ':preco'     => $preco
        ]);
        $mensagem = "Show de $artista cadastrado com sucesso!";
    } else {
        $mensagem = "Erro: Preencha todos os campos obrigatórios.";
    }
}

// AÇÃO 2: Emitir / Retirar um Ingresso
if (isset($_GET['retirar_ingresso'])) {
    $show_id    = (int)$_GET['retirar_ingresso'];
    $usuario_id = $_SESSION['usuario_id'];

    $stmt = $pdo->prepare('INSERT INTO ingressos (show_id, usuario_id) VALUES (:show_id, :usuario_id)');
    $stmt->execute([
        ':show_id'    => $show_id,
        ':usuario_id' => $usuario_id
    ]);
    $mensagem = "Ingresso retirado com sucesso!";
}


$listaShows = $pdo->query('SELECT * FROM shows ORDER BY data_show ASC')->fetchAll();

$stmtIngressos = $pdo->prepare('
    SELECT ingressos.id AS ingresso_id, shows.artista, shows.data_show, shows.local 
    FROM ingressos 
    JOIN shows ON ingressos.show_id = shows.id 
    WHERE ingressos.usuario_id = :usuario_id
');
$stmtIngressos->execute([':usuario_id' => $_SESSION['usuario_id']]);
$meusIngressos = $stmtIngressos->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Ingressos</title>
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
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
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

        /* Navbar */
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

        .btn-top {
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
            margin-left: 10px;
        }
        .btn-top:hover {
            background: rgba(245,197,24,0.12);
            border-color: var(--amarelo);
        }
        .btn-sair { color: var(--rosa) !important; border-color: rgba(212,54,90,0.5) !important; }
        .btn-sair:hover { background: rgba(212,54,90,0.12) !important; border-color: var(--rosa) !important; }

        /* Conteúdo Principal */
        .main-content {
            position: relative;
            z-index: 10;
            max-width: 720px;
            margin: 2.5rem auto;
            padding: 0 1rem;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            color: var(--amarelo);
            margin: 2rem 0 0.75rem 0;
            font-size: 1.4rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.4);
        }

        /* Card de dados (Base Creme) */
        .data-card {
            background: rgba(253,246,227,0.96);
            border-radius: 16px;
            border: 2px solid rgba(245,197,24,0.3);
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            margin-bottom: 2rem;
            color: var(--marrom);
        }

        /* Alerta de Mensagem */
        .msg-alert {
            background: rgba(255, 255, 255, 0.15);
            border: 1.5px solid var(--creme);
            color: var(--creme);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Formulários */
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.2rem;
        }
        .form-group label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--verde-medio);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.4rem;
        }
        .form-group input {
            padding: 0.75rem 1rem;
            background: #fff;
            border: 1.5px solid #ede8d8;
            border-radius: 8px;
            color: var(--marrom);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--verde-medio);
        }

        
 /* Botão Principal */
.btn-submit {
    display: block; /* Garante o comportamento de bloco */
    width: 100%;    /* Faz ocupar toda a largura do card */
    background: linear-gradient(135deg, var(--verde-medio), var(--verde-folha));
    color: var(--amarelo);
    border: none;
    padding: 0.85rem;
    border-radius: 8px;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    font-size: 1rem;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.1s, opacity 0.2s;
}
        .btn-submit:hover { opacity: 0.95; }
        .btn-submit:active { transform: scale(0.99); }

        /* Tabelas Estilizadas */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table tr {
            border-bottom: 1px solid #ede8d8;
        }
        .data-table tr:last-child { border-bottom: none; }
        .data-table th {
            font-size: 0.75rem;
            font-weight: bold;
            color: var(--verde-folha);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
            text-align: left;
            background: rgba(0,0,0,0.02);
        }
        .data-table td {
            padding: 0.95rem 1rem;
            font-size: 0.9rem;
            color: var(--marrom);
        }

        /* Botão de Ação na Tabela */
        .btn-action {
            display: inline-block;
            background: var(--laranja);
            color: white;
            text-decoration: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: bold;
            transition: opacity 0.2s;
        }
        .btn-action:hover { opacity: 0.9; }

        /* Grid de Ingressos (Estilo Bilhete Tradicional) */
        .ticket-box {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-top: 0.5rem;
        }
        .ticket-card {
            background: #fff;
            border: 2px dashed var(--laranja);
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            position: relative;
        }
        .ticket-id {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--laranja);
            border-bottom: 1px dashed #ddd;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .ticket-card p {
            font-size: 0.88rem;
            margin: 4px 0;
            color: var(--marrom);
        }
        .ticket-tag {
            display: inline-block;
            font-size: 0.7rem;
            background: rgba(46,125,60,0.1);
            color: var(--verde-medio);
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 8px;
            font-weight: bold;
        }

        .bandeira-strip {
            display: flex; height: 5px;
            border-radius: 0 0 14px 14px; overflow: hidden;
            margin: 1.5rem -1.5rem -1.5rem -1.5rem;
        }
        .bandeira-strip span { flex: 1; }
    </style>
</head>
<body>

<canvas id="bg-canvas"></canvas>
<div class="overlay"></div>

<nav class="navbar">
    <div class="navbar-brand">
        <h1>Bilheteria & Eventos</h1>
    </div>
    <div>
        <a href="dashboard.php" class="btn-top">Painel Principal</a>
        <a href="logout.php" class="btn-top btn-sair">Sair</a>
    </div>
</nav>

<div class="main-content">

    <?php if (!empty($mensagem)): ?>
        <div class="msg-alert"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <h3 class="section-title">1. Registrar Novo Show</h3>
    <div class="data-card">
        <form method="POST" action="shows.php">
            <input type="hidden" name="cadastrar_show" value="1">
            <div class="form-group">
                <label>Nome da Atração / Banda</label>
                <input type="text" name="artista" required placeholder="Ex: Linkin Park">
            </div>
            <div class="form-group">
                <label>Data do Evento</label>
                <input type="date" name="data_show" required>
            </div>
            <div class="form-group">
                <label>Local / Arena</label>
                <input type="text" name="local" required placeholder="Ex: Allianz Parque">
            </div>
            <div class="form-group">
                <label>Preço do Ingresso (R$)</label>
                <input type="number" name="preco" step="0.01" required placeholder="0.00">
            </div>
            <button type="submit" class="btn-submit">Salvar Evento Musical</button>
        </form>
    </div>

    <h3 class="section-title">2. Próximos Shows Disponíveis</h3>
    <div class="data-card" style="padding: 0.5rem 0 0 0; overflow: hidden;">
        <?php if (count($listaShows) === 0): ?>
            <p style="padding: 1.5rem; color: #ffffff; font-size: 0.9rem;">Nenhum show cadastrado no momento.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Artista</th>
                        <th>Data</th>
                        <th>Local</th>
                        <th>Preço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listaShows as $show): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($show['artista']) ?></strong></td>
                            <td><?= htmlspecialchars($show['data_show']) ?></td>
                            <td><?= htmlspecialchars($show['local']) ?></td>
                            <td>R$ <?= number_format($show['preco'], 2, ',', '.') ?></td>
                            <td>
                                <a class="btn-action" href="shows.php?retirar_ingresso=<?= $show['id'] ?>">Garantir Vaga 🎫</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="bandeira-strip">
            <span style="background:#009c3b;"></span>
            <span style="background:#ffdf00;"></span>
            <span style="background:#002776;"></span>
        </div>
    </div>

    <h3 class="section-title">3. Meus Ingressos Emitidos</h3>
    <?php if (count($meusIngressos) === 0): ?>
        <div class="data-card">
            <p style="color: #ffffff; font-size: 0.9rem;">Você não resgatou nenhum ingresso até o momento.</p>
        </div>
    <?php else: ?>
        <div class="ticket-box">
            <?php foreach ($meusIngressos as $ingresso): ?>
                <div class="ticket-card">
                    <div class="ticket-id"> Ingressos #<?= $ingresso['ingresso_id'] ?></div>
                    <p><strong>Banda:</strong> <?= htmlspecialchars($ingresso['artista']) ?></p>
                    <p><strong>Local:</strong> <?= htmlspecialchars($ingresso['local']) ?></p>
                    <p><strong>Data:</strong> <?= htmlspecialchars($ingresso['data_show']) ?></p>
                    <span class="ticket-tag">✓ Presença Confirmada</span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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