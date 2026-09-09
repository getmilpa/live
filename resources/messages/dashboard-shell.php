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
 * The shell's own words. The skip link is the first thing a screen reader reaches, so its language is the first thing that tells somebody whether this house speaks to them.
 *
 * English is the default and the fallback, per key: a locale that translates half a catalogue
 * renders the other half in English rather than rendering a key name at somebody.
 */
return [
    'en' => ['skip_to_content' => 'Skip to content'],
    'es' => ['skip_to_content' => 'Saltar al contenido'],
];
