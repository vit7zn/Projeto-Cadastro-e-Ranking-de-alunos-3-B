<?php
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "sistema_login"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome_aluno'];
    $curso = $_POST['curso'];
    $procedencia = $_POST['procedencia'];
    $pcd = $_POST['pcd'];
    $media = $_POST['media_final']; // Campo oculto que enviaremos via JS

    // Lógica simples de categoria para o banco
    $categoria = ($pcd == 'sim') ? "Cota PCD" : (($procedencia == 'privada') ? "Ampla Concorrência Privado" : "AMPLA CONCORRÊNCIA PÚBLICA");

    $sql = "INSERT INTO alunos (nome_completo, curso, procedencia_escolar, pcd, categoria_ranking, media_geral) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssd", $nome, $curso, $procedencia, $pcd, $categoria, $media);

    if ($stmt->execute()) {
        // Redireciona automaticamente para o ranking após salvar
        header("Location: ranking.php?curso=" . urlencode($curso));
        exit();
    } else {
        echo "Erro ao cadastrar: " . $conn->error;
    }
}
?>