<?php
$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "sistema_login"; 

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome        = $_POST['nome_aluno'];
    $sexo        = $_POST['sexo'] ?? '';
    $curso       = $_POST['curso'];
    $procedencia = $_POST['procedencia'];
    $pcd         = $_POST['pcd'];
    $media       = $_POST['media_final'];

    $categoria = ($pcd == 'sim') ? "Cota PCD" : (($procedencia == 'privada') ? "Ampla Concorrência Privado" : "AMPLA CONCORRÊNCIA PÚBLICA");

    $sql = "INSERT INTO alunos (nome_completo, sexo, curso, procedencia_escolar, pcd, categoria_ranking, media_geral) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssd", $nome, $sexo, $curso, $procedencia, $pcd, $categoria, $media);

    if ($stmt->execute()) {
        // Redireciona automaticamente para o ranking após salvar
        header("Location: ranking.php?curso=" . urlencode($curso));
        exit();
    } else {
        echo "Erro ao cadastrar: " . $conn->error;
    }
}
?>