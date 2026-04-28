<?php
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "sistema_login"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

$curso = isset($_GET['curso']) ? $_GET['curso'] : 'Enfermagem';

function renderizarBloco($titulo, $sql, $vagas, $conn) {
    $result = $conn->query($sql);
    echo "<tr><td colspan='5' class='secao-titulo'>$titulo</td></tr>";
    
    if ($result && $result->num_rows > 0) {
        $posicao = 1;
        while ($aluno = $result->fetch_assoc()) {
            $situacao = ($posicao <= $vagas) ? "CLASSIFICADO(A)" : "NÃO CLASSIFICADO(A)";
            $classe = ($posicao <= $vagas) ? "linha-classificado" : "linha-espera";
            
            echo "<tr class='$classe'>
                    <td>$posicao</td>
                    <td class='text-left'>{$aluno['nome_completo']}</td>
                    <td>$titulo</td>
                    <td>$situacao</td>
                    <td>" . number_format($aluno['media_geral'], 5, ',', '.') . "</td>
                  </tr>";
            $posicao++;
        }
    } else {
        echo "<tr><td colspan='5' style='padding:10px;'>Nenhum candidato registrado nesta categoria.</td></tr>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ranking Oficial - EEEP Manoel Mano</title>
    <link rel="stylesheet" href="../../style.css">
    
    <style>
        /* ESTILIZAÇÃO COMPLETA CASO O STYLE.CSS NÃO CARREGUE */
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f4f4f4; 
        }

        /* Navbar estilo padrão (verde) */
        .navbar {
            background-color: #1b8a00; /* Cor verde que você usa */
            padding: 15px 0;
            text-align: center;
            width: 100%;
        }
        .navbar .menu a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-weight: bold;
        }
        .navbar .menu a:hover { background-color: #146900; }
        .navbar .menu a.active { border-bottom: 3px solid white; }

        /* Filtro */
        .filtro-container {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
        }
        .filtro-container select {
            padding: 8px 15px;
            border-radius: 5px;
            border: 1px solid #1b8a00;
            font-size: 14px;
            cursor: pointer;
        }

        /* Folha do PDF */
        .folha-pdf { 
            background-color: #fff; 
            width: 900px; 
            margin: 0 auto 50px auto; 
            padding: 50px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }

        .header-pdf { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px; }
        .header-pdf p { margin: 2px 0; font-size: 13px; font-weight: bold; }
        .header-pdf h1 { font-size: 18px; text-decoration: underline; margin-top: 10px; }

        /* Tabela */
        table { width: 100%; border-collapse: collapse; }
        th { border: 1px solid #000; padding: 10px; font-size: 11px; background: #f0f0f0; text-transform: uppercase; }
        td { border: 1px solid #000; padding: 8px; font-size: 11px; text-align: center; }
        
        .secao-titulo { background-color: #d1d1d1; font-weight: bold; text-align: left; padding: 10px; border: 2px solid #000; font-size: 12px; }
        .text-left { text-align: left; padding-left: 10px; text-transform: uppercase; }
        
        .linha-classificado { font-weight: bold; color: #000; }
        .linha-espera { color: #888; font-style: italic; }

        @media print {
            .navbar, .filtro-container { display: none; }
            .folha-pdf { box-shadow: none; border: none; width: 100%; margin: 0; }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <nav class="menu">
            <a href="dashboard.php">Painel</a>
            <a href="cadastro.html">Cadastro</a>
            <a href="ranking.php" class="active">Ranking</a>
            <a href="../../index.html">Sair</a>
        </nav>
    </header>

    <div class="filtro-container">
        <form method="GET">
            <label><b>FILTRAR POR CURSO:</b> </label>
            <select name="curso" onchange="this.form.submit()">
                <option value="Enfermagem" <?= $curso=='Enfermagem'?'selected':'' ?>>ENFERMAGEM</option>
                <option value="Informatica" <?= $curso=='Informatica'?'selected':'' ?>>INFORMÁTICA</option>
                <option value="Administracao" <?= $curso=='Administracao'?'selected':'' ?>>ADMINISTRAÇÃO</option>
                <option value="Comercio" <?= $curso=='Comercio'?'selected':'' ?>>COMÉRCIO</option>
            </select>
        </form>
    </div>

    <div class="folha-pdf">
        <div class="header-pdf">
            <p>GOVERNO DO ESTADO DO CEARÁ</p>
            <p>SECRETARIA DA EDUCAÇÃO - SEDUC</p>
            <p>EEEP MANOEL MANO - CRATEÚS</p>
            <h1>RESULTADO PRELIMINAR - SELEÇÃO DE ALUNOS 2026</h1>
            <p style="margin-top:10px; font-size: 14px;"><b>CURSO TÉCNICO EM: <?php echo strtoupper($curso); ?></b></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="5%">ORDEM</th>
                    <th width="45%">NOME COMPLETO</th>
                    <th width="20%">TIPO CONCORRÊNCIA</th>
                    <th width="20%">SITUAÇÃO</th>
                    <th width="10%">MÉDIA</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Blocos conforme o PDF
                renderizarBloco("AMPLA CONCORRÊNCIA PÚBLICA", 
                    "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND procedencia_escolar = 'publica' AND pcd = 'nao' ORDER BY media_geral DESC", 37, $conn);

                renderizarBloco("AMPLA CONCORRÊNCIA PRIVADO", 
                    "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND procedencia_escolar = 'privada' AND pcd = 'nao' ORDER BY media_geral DESC", 8, $conn);

                renderizarBloco("COTA PESSOA COM DEFICIÊNCIA (PCD)", 
                    "SELECT * FROM alunos WHERE curso LIKE '%$curso%' AND pcd = 'sim' ORDER BY media_geral DESC", 3, $conn);
                ?>
            </tbody>
        </table>
    </div>

</body>
</html>