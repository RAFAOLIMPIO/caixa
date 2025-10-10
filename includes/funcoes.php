<?php
// Funções utilitárias

/**
 * Sanitiza entrada html/texto
 */
function sanitizar($dado) {
    if (is_array($dado)) {
        return array_map('sanitizar', $dado);
    }
    return trim(filter_var($dado, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

/**
 * Valida campos obrigatórios, retorna array de erros (vazio se ok)
 */
function validar_campos_obrigatorios($dados, $obrigatorios = []) {
    $erros = [];
    foreach ($obrigatorios as $campo => $mensagem) {
        if (!isset($dados[$campo]) || trim($dados[$campo]) === '') {
            $erros[] = $mensagem;
        }
    }
    return $erros;
}
?>