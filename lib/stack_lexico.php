<?php
/**
 * El lexico que decide si una noticia es riesgo u oportunidad, y cuanto corre.
 *
 * Nace de mirar los datos de produccion, no el esquema, y de reconocer un
 * error de diseno: la primera version puntuaba con `bits.tipo` y
 * `bits.madurez`, que son campos que **solo rellena una persona en el panel**.
 * Este sitio publica en automatico, asi que los cincuenta y tres bits vivos
 * llevaban los tres el valor por defecto -producto, anuncio, un solo medio- y
 * el mapa salia con los dieciseis nodos identicos: mismo color, mismo tamano,
 * misma puntuacion. Un mapa de calor sin calor.
 *
 * Lo que si varia en automatico es el texto. Asi que la senal se lee del
 * texto, con la misma tecnica que ya clasifica los nodos: una lista de
 * terminos, comprobable palabra por palabra. Si alguien pregunta por que su
 * area sale en rojo, la respuesta es "porque la noticia dice 'fin de soporte'",
 * y eso se comprueba abriendo el enlace.
 *
 * Dos niveles, y la diferencia es el motivo de que esto funcione:
 *
 *   - **Fuertes**: terminos que en el sector no significan otra cosa. "Brecha
 *     de datos", "fin de soporte", "entra en vigor", "concurso de acreedores".
 *     Uno solo basta para teñir la noticia de riesgo.
 *   - **Debiles**: terminos que tambien se usan en sentido figurado.
 *     "Vulnerabilidad", "amenaza", "riesgo", "caida". Hacen falta dos, o uno
 *     mas una tematica que ya sea de riesgo.
 *
 * Esa distincion no es teorica. En los datos de produccion habia una noticia
 * -"La dependencia del turismo internacional"- clasificada como
 * ciberseguridad porque el texto decia "una vulnerabilidad que muchos destinos
 * han tardado en reconocer". Con dos niveles, esa palabra sola ya no enciende
 * nada.
 */

declare(strict_types=1);

require_once __DIR__ . '/texto.php';

/**
 * Terminos que, solos, ya dicen que la noticia es un problema.
 *
 * @return string[]
 */
function lexico_riesgo_fuerte(): array
{
    return [
        // Seguridad, y solo la de verdad: nada de "amenaza" a secas.
        'brecha de datos', 'brecha de seguridad', 'filtracion de datos', 'ransomware',
        'ciberataque', 'ciberataques', 'malware', 'phishing', 'exploit', 'zero day',
        'dia cero', 'robo de datos', 'robo de credenciales', 'suplantacion de identidad',
        'parche de seguridad', 'actualizacion de seguridad', 'incidente de seguridad',

        // Legal y regulatorio con consecuencia.
        'multa', 'multas', 'sancion', 'sanciones', 'expediente sancionador',
        'entra en vigor', 'entrara en vigor', 'obligatorio', 'obligatoria',
        'incumplimiento', 'prohibicion', 'prohibe', 'ilegal', 'demanda judicial',
        'litigio', 'plazo de cumplimiento', 'fecha limite',

        // El proveedor que deja de estar.
        'fin de soporte', 'deja de dar soporte', 'discontinua', 'descontinua',
        'quiebra', 'concurso de acreedores', 'cierra sus puertas', 'cese de actividad',
        'retira del mercado', 'deja de operar',

        // El servicio que se cae.
        'caida del servicio', 'interrupcion del servicio', 'fuera de servicio',
        'apagon', 'perdida de datos',

        // Lo que cuesta mas dinero desde manana.
        'sube el precio', 'subida de precios', 'sube la comision', 'sube las comisiones',
        'encarece', 'nueva tarifa', 'cambio de licencia', 'cambio de tarifas',
    ];
}

/**
 * Terminos que apuntan a problema pero tambien se usan en sentido figurado.
 * Hacen falta dos, o uno con una tematica que ya sea de riesgo.
 *
 * @return string[]
 */
function lexico_riesgo_debil(): array
{
    return [
        'vulnerabilidad', 'vulnerabilidades', 'amenaza', 'amenazas', 'riesgo', 'riesgos',
        'alerta', 'alertas', 'advertencia', 'advierte', 'preocupacion',
        'fallo', 'fallos', 'averia', 'error', 'errores',
        'caida', 'caen', 'cae', 'desciende', 'descenso', 'recorte', 'recortes',
        'reduce', 'reduccion', 'perdida', 'perdidas', 'negativo', 'negativas',
        'despidos', 'fraude', 'estafa',
        // Fuera: 'dependencia', 'presion', 'conflicto' y 'problema'. En prosa
        // de negocio son muletillas -"la dependencia del turismo", "el gran
        // problema del sector"- y juntaban dos con cualquier cosa. La noticia
        // "La dependencia del turismo internacional" salia en rojo por eso.

    ];
}

/**
 * Terminos que dicen que la cosa corre. Suben la puntuacion sin cambiar la
 * senal: una oportunidad tambien puede tener fecha.
 *
 * @return string[]
 */
function lexico_urgencia(): array
{
    return [
        'explotacion activa', 'urgente', 'urgentemente', 'de inmediato', 'inmediato',
        'critico', 'critica', 'criticas', 'grave', 'emergencia', 'sin previo aviso',
        'esta semana', 'en enero', 'a partir de enero', 'antes de fin de ano',
        'a partir del proximo mes', 'con efecto inmediato', 'ya esta disponible',
        'disponible desde hoy', 'desde hoy',
    ];
}

/**
 * Compras de empresa. Que compren a tu proveedor no es ni bueno ni malo por si
 * mismo -por eso no va en las listas de riesgo- pero es de las pocas cosas que
 * obligan a mirar un contrato este trimestre, asi que sube la puntuacion.
 *
 * @return string[]
 */
function lexico_movimiento(): array
{
    return [
        'adquisicion', 'adquiere', 'adquirido', 'adquirida', 'fusion', 'se fusiona',
        'absorbe', 'toma el control', 'compra la compania', 'compra el negocio',
        'integra la compania', 'operacion corporativa', 'cambio de manos',
    ];
}

/**
 * Cuantos terminos distintos de una lista aparecen en un texto.
 *
 * Distintos y no repeticiones: un titular que dice "multa" tres veces no sabe
 * mas de multas que uno que dice "multa" y "sancion". Repetir es estilo; usar
 * dos terminos del mismo campo es tema.
 *
 * Se compara con espacios a los lados, como los alias de los nodos, para que
 * 'cae' no se coma "cadena" y 'error' no se coma "errores" dos veces.
 */
function lexico_cuantos(string $texto, array $terminos): int
{
    $normal = ' ' . texto_normalizar($texto) . ' ';
    $vistos = 0;

    foreach ($terminos as $termino) {
        $aguja = texto_normalizar($termino);

        if ($aguja !== '' && str_contains($normal, ' ' . $aguja . ' ')) {
            $vistos++;
        }
    }

    return $vistos;
}

/**
 * Las tematicas que ya son de riesgo por si mismas. Un termino debil dentro de
 * una de ellas cuenta como fuerte: "vulnerabilidad" en una noticia de
 * ciberseguridad quiere decir lo que parece.
 *
 * @return string[]
 */
function lexico_categorias_de_riesgo(): array
{
    return ['ciberseguridad', 'cumplimiento'];
}

/**
 * Lo que el texto de un bit dice de si mismo.
 *
 * @return array{riesgo: bool, fuertes: int, debiles: int, urgencia: int, movimiento: int}
 */
function lexico_leer(array $bit): array
{
    $texto = implode(' ', [
        (string) ($bit['titular'] ?? ''),
        (string) ($bit['cuerpo'] ?? ''),
        (string) ($bit['por_que'] ?? ''),
    ]);

    $fuertes = lexico_cuantos($texto, lexico_riesgo_fuerte());
    $debiles = lexico_cuantos($texto, lexico_riesgo_debil());

    $tematica_dura = in_array(
        (string) ($bit['categoria'] ?? ''),
        lexico_categorias_de_riesgo(),
        true
    );

    return [
        'riesgo'     => $fuertes > 0 || $debiles >= 2 || ($debiles >= 1 && $tematica_dura),
        'fuertes'    => $fuertes,
        'debiles'    => $debiles,
        'urgencia'   => lexico_cuantos($texto, lexico_urgencia()),
        'movimiento' => lexico_cuantos($texto, lexico_movimiento()),
    ];
}
