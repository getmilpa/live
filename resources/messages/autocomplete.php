<?php

/**
 * This file is part of Milpa Live — the render-target-agnostic live component core of the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

/**
 * The autocomplete's own words.
 *
 * English is the default and the fallback, per key: a locale that translates half a catalogue
 * renders the other half in English rather than rendering a key name at somebody.
 */
return [
    'en' => [
        'remove' => 'Remove selection',
        'search_placeholder' => 'Search…',
        'no_results' => 'No results',
    ],
    'es' => [
        'remove' => 'Quitar selección',
        'search_placeholder' => 'Buscar…',
        'no_results' => 'Sin resultados',
    ],
];
