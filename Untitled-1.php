<?
function importarCSV() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['arquivo_csv']) && $_FILES['arquivo_csv']['error'] === UPLOAD_ERR_OK) {
            $caminho_arquivo = $_FILES['arquivo_csv']['tmp_name'];

            // Processar os dados do arquivo CSV e inserir na tabela omnigenous
            global $wpdb;
            $nome_tabela = $wpdb->prefix . 'omnigenous';

            if (($arquivo = fopen($caminho_arquivo, 'r')) !== false) {
                fgetcsv($arquivo); // Ignorar o cabeçalho do CSV

                while (($linha = fgetcsv($arquivo)) !== false) {
                    $nfp = $linha[2];
                    $tipo = $linha[3];

                    if (ctype_digit($nfp) && 1 <= $nfp && $nfp <= 10 && ($tipo == "False" || $tipo == 1 || $tipo == "QA")) {
                        $linha[3] = 1;
                        $linha = array_map('floatval', $linha);
                        $linha[3] = 1;
                        $linha[2] = intval($linha[2]);
                        $wpdb->insert(omnigenous, array(
                            'axLength' => $linha[0],
                            'RotTrans' => $linha[1],
                            'nfp' => $linha[2],
                            'Tipo' => $linha[3],
                            'rc1' => $linha[4],
                            'zs1' => $linha[5],
                            'etabar' => $linha[6],
                            'max_elong' => $linha[7],
                            'lgradB' => $linha[8],
                            'min_RO' => $linha[9]
                        ));
                        echo "Inserção válida\n";
                    } else if (ctype_digit($nfp) && 1 <= $nfp && $nfp <= 10 && ($tipo == "True" || $tipo == 2 || $tipo == "QH")) {
                        $linha[3] = 2;
                        $linha = array_map('floatval', $linha);
                        $linha[3] = 2;
                        $linha[2] = intval($linha[2]);
                        $wpdb->insert(omnigenous, array(
                            'axLength' => $linha[0],
                            'RotTrans' => $linha[1],
                            'nfp' => $linha[2],
                            'Tipo' => $linha[3],
                            'rc1' => $linha[4],
                            'zs1' => $linha[5],
                            'etabar' => $linha[6],
                            'max_elong' => $linha[7],
                            'lgradB' => $linha[8],
                            'min_RO' => $linha[9]
                        ));
                        echo "Inserção válida\n";
                    } else {
                        echo "Inserção inválida\n";
                    }
                }

                fclose($arquivo);
            } else {
                echo "Erro ao abrir o arquivo.\n";
            }
        } else {
            echo "Por favor, selecione um arquivo CSV válido.";
        }
    }
}

function formularioCSV() {
    ob_start();
    ?>
    <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post" enctype="multipart/form-data">
        <input type="file" name="arquivo_csv" accept=".csv">
        <input type="submit" value="Enviar">
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('meu_shortcode', 'formularioCSV');
add_action('init', 'importarCSV');

// Função para exportar a tabela "omnigenous" para um arquivo CSV
function exportarTabelaOmnigenous($colunas) {
    // Conexão com o banco de dados
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "stellarak";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    // Converter as colunas em uma string separada por vírgulas
    $colunasString = implode(',', $colunas);

    // Consulta para obter os dados das colunas selecionadas
    $sql = "SELECT $colunasString FROM omnigenous";
    $result = $conn->query($sql);

    // Verificar se há registros retornados
    if ($result->num_rows > 0) {
        // Nome do arquivo CSV
        $filename = 'tabela_omnigenous.csv';



        // Cabeçalho do arquivo CSV
        $header = $colunas;

        // Abrir o arquivo CSV em modo de escrita
        $file = fopen($filename, 'w');

        // Escrever o cabeçalho no arquivo CSV
        fputcsv($file, $header);

        // Escrever os dados no arquivo CSV
        while ($row = $result->fetch_assoc()) {
            $dados = array();
            foreach ($colunas as $coluna) {
                $dados[] = $row[$coluna];
            }
            fputcsv($file, $dados);
        }

        // Fechar o arquivo
        fclose($file);

        // Forçar o download do arquivo CSV
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        readfile($filename);

        // Remover o arquivo CSV
        unlink($filename);
    } else {
        echo "Nenhum dado encontrado.";
    }

    $conn->close();
}

function exibirTabelaOmnigenous() {
    // Conexão com o banco de dados
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "stellarak";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    // Definir o número de registros por página
    $registrosPorPagina = 10;

    // Verificar se a página atual foi definida pelo usuário
    $paginaAtual = isset($_GET['pagina']) ? $_GET['pagina'] : 1;

    // Calcular o índice inicial dos registros a serem exibidos
    $indiceInicial = ($paginaAtual - 1) * $registrosPorPagina;

// Verificar se foram enviados dados de filtro
$filtro = "";
$params = array();
if (isset($_POST['filtro'])) {
    $filtros = $_POST['filtro'];
    $filtroArray = array();

 // Verificar se uma coluna foi selecionada
 if (!empty($filtros['coluna'])) {
    $colunaSelecionada = $filtros['coluna'];


        // Verificar se foi fornecido um valor para a coluna selecionada
        if (isset($filtros[$colunaSelecionada])) {
            $valorColuna = sanitize_text_field($filtros[$colunaSelecionada]);
            $filtroArray[] = "$colunaSelecionada = ?";
            $params[] = $valorColuna;

        }
 }}
 // Construir a cláusula WHERE do filtro
 $filtro = "";
 // Condição inicial para incluir todos os registros

// Verificar os outros filtros
foreach ($filtros as $coluna => $valor) {
    $valor = sanitize_text_field($valor);
    if (!empty($coluna) && !empty($valor) && $coluna !== 'coluna') {
        // Adicionar condições para filtrar por intervalo, maior ou menor que um número
        if ($coluna == 'intervalo') {
            $valores = explode('-', $valor);
            $valorMin = trim($valores[0]);
            $valorMax = trim($valores[1]);

            // Tratar valores numéricos
            if (is_numeric($valorMin)) {
                $valorMin = str_replace(',', '.', $valorMin);
            }
            if (is_numeric($valorMax)) {
                $valorMax = str_replace(',', '.', $valorMax);
            }

            $filtroArray[] = "$colunaSelecionada BETWEEN $valorMin AND $valorMax";
        } elseif ($coluna == 'maior_que') {
            // Tratar valor numérico
            if (is_numeric($valor)) {
                $valor = str_replace(',', '.', $valor);
            }
            $filtroArray[] = "$colunaSelecionada > $valor";
        } elseif ($coluna == 'menor_que') {
            // Tratar valor numérico
            if (is_numeric($valor)) {
                $valor = str_replace(',', '.', $valor);
            }
            $filtroArray[] = "$colunaSelecionada < $valor";
        }
    }
}


 // Combinar as condições do filtro em uma única string
 if (!empty($filtroArray)) {
 
    $filtro = " WHERE " . implode(' AND ', $filtroArray);


 }

 // Consulta para obter os registros da página atual com base no filtro
 $sql = "SELECT * FROM omnigenous $filtro LIMIT ?, ?";
 $stmt = $conn->prepare($sql);
 $stmt->bind_param("ii", $indiceInicial, $registrosPorPagina);
 if (!empty($params)) {
    $tiposParametros = '';
foreach ($params as $valor) {
    if (is_int($valor)) {
        $tiposParametros .= 'i';
    } elseif (is_float($valor)) {
        $tiposParametros .= 'd';
    } else {
        $tiposParametros .= 's';
    }
}

$stmt->bind_param($tiposParametros, ...$params);

 }
 $stmt->execute();
 $result = $stmt->get_result();

    // Verificar se há registros retornados
    if ($result->num_rows > 0) {
        // Obter as colunas disponíveis na tabela
        $colunasDisponiveis = array_keys($result->fetch_assoc());

        echo "<form id='filtro-form' action='' method='post'>";
        echo "<p>Filtrar os dados:</p>";
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr 1fr; grid-gap: 10px; margin-bottom: 10px;'>";
        

      // Exibir campos de filtro para colunas específicas
echo "<div>";
echo "<label>Coluna:</label>";
echo "<select name='filtro[coluna]'>";
echo "<option value=''>Selecione uma coluna</option>";
foreach ($colunasDisponiveis as $coluna) {
    echo "<option value='$coluna'>$coluna</option>";
}
echo "</select>";
echo "</div>";

echo "<div>";
echo "<label>Intervalo:</label>";
echo "<input type='text' name='filtro[intervalo]' placeholder='Ex: 10-20'>";
echo "</div>";

echo "<div>";
echo "<label>Maior que:</label>";
echo "<input type='text' name='filtro[maior_que]' placeholder='Ex: 50'>";
echo "</div>";

echo "<div>";
echo "<label>Menor que:</label>";
echo "<input type='text' name='filtro[menor_que]' placeholder='Ex: 30'>";
echo "</div>";

echo "</div>";
echo "<button type='submit'>Filtrar</button>";
echo "</form>";


        // Exibir os dados em uma tabela
        echo "<table>";
        echo "<tr>";
        if (!empty($colunaSelecionada)) {
            echo "<th>" . $colunaSelecionada . "</th>";
        } else {
            foreach ($colunasDisponiveis as $coluna) {
                echo "<th>" . $coluna . "</th>";
            }
        }
        echo "</tr>";

        // Exibir os dados de cada linha
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            if (!empty($colunaSelecionada)) {
                echo "<td>" . $row[$colunaSelecionada] . "</td>";
            } else {
                foreach ($colunasDisponiveis as $coluna) {
                    echo "<td>" . $row[$coluna] . "</td>";
                }
            }
            echo "</tr>";
        }

        echo "</table>";

        // Exibir a navegação
        echo "<div class='pagination'>";
        if ($paginaAtual > 1) {
            echo "<a href='?pagina=" . ($paginaAtual - 1) . "'>&lt;</a>";
        }
        echo "<span>Página " . $paginaAtual . "</span>";
        if ($result->num_rows == $registrosPorPagina) {
            echo "<a href='?pagina=" . ($paginaAtual + 1) . "'>&gt;</a>";
        }
        echo "</div>";

        // Formulário para selecionar colunas para exportação
        echo "<form id='exportar-form' action='" . admin_url('admin-ajax.php') . "' method='post'>";
        echo "<input type='hidden' name='action' value='exportar_tabela_omnigenous'>";
        echo "<p>Selecione as colunas para exportação:</p>";
        echo "<div style='column-count: 3; column-gap: 20px;'>";

        if (!empty($colunaSelecionada)) {
            echo "<label><input type='checkbox' name='colunas[]' value='$colunaSelecionada' checked> $colunaSelecionada</label><br>";
        } else {
            foreach ($colunasDisponiveis as $coluna) {
                echo "<label><input type='checkbox' name='colunas[]' value='$coluna'> $coluna</label><br>";
            }
        }
    
        echo "</div>";
        echo "<button onclick='exportarTabela()' type='button'>Exportar Tabela</button>";
        echo "</form>";
    
        // Script JavaScript para exportar a tabela
        echo "<script>
            function exportarTabela() {
                var form = document.getElementById('exportar-form');
                var data = new FormData(form);
    
                var xhr = new XMLHttpRequest();
                xhr.open(form.method, form.action, true);
                xhr.responseType = 'blob';
                xhr.onload = function(event) {
                    var blob = xhr.response;
                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = 'tabela_omnigenous.csv';
                    link.click();
                };
                xhr.send(data);
            }
        </script>";
    } else {
        echo "Nenhum dado encontrado.";
    }
    
    $stmt->close();
    $conn->close();
    

}

// Função do shortcode para exibir a tabela omnigenous com navegação e opção de exportação de colunas
function shortcode_exibir_tabela_omnigenous($atts) {
    ob_start();
    exibirTabelaOmnigenous();
    return ob_get_clean();
}
add_shortcode('exibir_tabela_omnigenous', 'shortcode_exibir_tabela_omnigenous');

// Função AJAX para exportar a tabela com as colunas selecionadas
function exportar_tabela_omnigenous() {
    if (isset($_POST['colunas'])) {
        $colunas = $_POST['colunas'];
        exportarTabelaOmnigenous($colunas);
    }
}
add_action('wp_ajax_exportar_tabela_omnigenous', 'exportar_tabela_omnigenous');
add_action('wp_ajax_nopriv_exportar_tabela_omnigenous', 'exportar_tabela_omnigenous');