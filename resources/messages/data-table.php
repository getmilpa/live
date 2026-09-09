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
 * The data table's own words. `select_row` names the row it selects, which is why it carries a placeholder rather than being assembled from two fragments a translator cannot reorder.
 *
 * English is the default and the fallback, per key: a locale that translates half a catalogue
 * renders the other half in English rather than rendering a key name at somebody.
 */
return [
    'en' => [
        'selected' => 'selected',
        'select_row' => 'Select %s',
    ],
    'es' => [
        'selected' => 'seleccionados',
        'select_row' => 'Seleccionar %s',
    ],
];
