<?php
/**
 * Integracion de Inteligencia Artificial (LLM)
 *
 * Utiliza OpenAI u otro proveedor compatible (ej. Claude via pasarela) para:
 * 1. Generar el resumen directivo "Por qué importa".
 * 2. Filtrar si la noticia trata de tecnología hotelera.
 * 3. Extraer entidades (proveedores).
 */

require_once __DIR__ . '/db.php';

function ia_conf(): array
{
    $ruta = (string) (config('rutas']['config'] ?? dirname(__DIR__) . '/config');
    $fichero = $ruta . '/ia.php';

    if (is_file($fichero)) {
        return require $fichero;
    }

    return ['clave' => '', 'modelo' => 'gpt-4o-mini'];
}

function ia_configurada(): bool
{
    return ia_conf()['clave'] !== '';
}

function ia_llamar(string $prompt, string $sistema = ''): array
{
    $conf = ia_conf();
    if ($conf['clave'] === '') {
        return ['ok' => false, 'mensaje' => 'IA no configurada', 'respuesta' => ''];
    }

    $mensajes = [];
    if ($sistema !== '') {
        $mensajes[] = ['role' => 'system', 'content' => $sistema];
    }
    $mensajes[] = ['role' => 'user', 'content' => $prompt];

    $datos = json_encode([
        'model' => $conf['modelo'],
        'messages' => $mensajes,
        'temperature' => 0.3,
        'max_tokens' => 250,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $datos,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $conf['clave'],
        ],
    ]);

    $cuerpo = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($cuerpo === false) {
        return ['ok' => false, 'mensaje' => 'Error de red: ' . $error, 'respuesta' => ''];
    }

    $respuesta = json_decode($cuerpo, true);

    if ($codigo !== 200) {
        return ['ok' => false, 'mensaje' => 'Error HTTP ' . $codigo . ': ' . ($respuesta['error']['message'] ?? 'Desconocido'), 'respuesta' => ''];
    }

    $texto = trim((string) ($respuesta['choices'][0]['message']['content'] ?? ''));
    return ['ok' => true, 'mensaje' => '', 'respuesta' => $texto];
}

/**
 * Redacta el campo "Por qué importa"
 */
function ia_redactar_por_que(string $titular, string $cuerpo): string
{
    $sistema = "Eres un analista de inteligencia B2B del sector tecnología hotelera en España. Redacta de forma muy concisa, en 2 frases directas y en español, 'por qué es importante' esta noticia para directivos de cadenas hoteleras. No uses introducción.";
    $prompt = "Titular: " . $titular . "\nCuerpo: " . $cuerpo;
    
    $res = ia_llamar($prompt, $sistema);
    return $res['ok'] ? $res['respuesta'] : '';
}

/**
 * Comprueba si la noticia trata estrictamente de tecnología hotelera
 */
function ia_es_del_sector(string $texto): bool
{
    $sistema = "Responde únicamente SI o NO. ¿Trata esta noticia específicamente sobre tecnología para hoteles, software de gestión hotelera (PMS), distribución (OTAs), o innovación en hospitalidad? Ignora tecnología general que no mencione hoteles.";
    $prompt = "Noticia: " . $texto;

    $res = ia_llamar($prompt, $sistema);
    if ($res['ok']) {
        return str_starts_with(strtoupper($res['respuesta']), 'SI');
    }
    
    return true; // En caso de fallo, pasamos para que el diccionario tradicional actue.
}
