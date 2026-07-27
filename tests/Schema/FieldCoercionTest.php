<?php

/**
 * This file is part of Milpa Live — the live component core of the Milpa PHP framework.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/live
 */

declare(strict_types=1);

namespace Milpa\Live\Tests\Schema;

use Milpa\Live\Schema\FieldCoercion;
use Milpa\Live\Schema\FieldType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Las reglas de coerción, que el propio docblock declara congeladas.
 *
 * Están en un solo lugar justamente para que el parser y el binder no puedan
 * divergir: si cada uno decidiera por su cuenta qué es un entero, un mismo
 * valor sería válido al definir el formulario e inválido al enviarlo.
 */
#[CoversClass(FieldCoercion::class)]
final class FieldCoercionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, ?int}>
     */
    public static function enteros(): iterable
    {
        yield 'un entero' => ['42', 42];
        yield 'negativo' => ['-7', -7];
        yield 'cero' => ['0', 0];
        yield 'con ceros a la izquierda' => ['007', 7];
        yield 'un decimal NO es entero' => ['4.2', null];
        yield 'un entero con punto cero tampoco' => ['4.0', null];
        yield 'notación científica tampoco' => ['1e3', null];
        yield 'con sufijo' => ['42kg', null];
        yield 'una palabra' => ['muchos', null];
        yield 'vacío' => ['', null];
        yield 'un signo más explícito' => ['+7', null];
    }

    #[DataProvider('enteros')]
    public function testAnIntegerIsOnlyWhatMatchesTheFrozenPattern(string $raw, ?int $esperado): void
    {
        // El cast de PHP convertiría '42kg' en 42 y 'muchos' en 0. La regla
        // congelada exige el literal completo, así que lo que no es entero se
        // reporta como no coercible en vez de colarse como un número inventado.
        self::assertSame($esperado, FieldCoercion::toInt($raw));
    }

    /**
     * @return iterable<string, array{string, ?float}>
     */
    public static function numeros(): iterable
    {
        yield 'un decimal' => ['4.2', 4.2];
        yield 'un entero' => ['7', 7.0];
        yield 'negativo' => ['-0.5', -0.5];
        yield 'notación científica' => ['1e3', 1000.0];
        yield 'una palabra' => ['mucho', null];
        yield 'con sufijo' => ['4.2kg', null];
        yield 'vacío' => ['', null];
    }

    #[DataProvider('numeros')]
    public function testANumberAcceptsWhateverIsNumeric(string $raw, ?float $esperado): void
    {
        self::assertSame($esperado, FieldCoercion::toFloat($raw));
    }

    /**
     * @return iterable<string, array{string, ?bool}>
     */
    public static function booleanos(): iterable
    {
        yield '1' => ['1', true];
        yield 'true' => ['true', true];
        yield 'on' => ['on', true];
        yield 'yes' => ['yes', true];
        yield '0' => ['0', false];
        yield 'false' => ['false', false];
        yield 'off' => ['off', false];
        yield 'no' => ['no', false];
        yield 'vacío es falso' => ['', false];
        yield 'quizá no es ninguno' => ['quizá', null];
        yield '2 no es ninguno' => ['2', null];
    }

    #[DataProvider('booleanos')]
    public function testABooleanIsReadFromTheFrozenTokenSets(string $raw, ?bool $esperado): void
    {
        // Un checkbox no marcado llega como cadena vacía, así que el vacío es
        // false y no "no sé". Cualquier otra palabra sí es "no sé", para que el
        // binder pueda reportarla en vez de adivinar.
        self::assertSame($esperado, FieldCoercion::toBool($raw));
    }

    // ---- miembros de enum ----------------------------------------------------------

    public function testAnEnumMemberIsCoercedToTheFieldsBaseType(): void
    {
        self::assertSame('7', FieldCoercion::coerceEnumMember(7, FieldType::Text));
        self::assertSame(7, FieldCoercion::coerceEnumMember('7', FieldType::Integer));
        self::assertSame(7.5, FieldCoercion::coerceEnumMember('7.5', FieldType::Number));
    }

    public function testAnEnumMemberThatIsNotScalarIsRejectedBeforeAnyCast(): void
    {
        // Castear un arreglo a string emitiría un E_WARNING y produciría la
        // palabra "Array" como opción válida. El rechazo va ANTES del cast.
        self::assertNull(FieldCoercion::coerceEnumMember([1, 2], FieldType::Text));
        self::assertNull(FieldCoercion::coerceEnumMember(new \stdClass(), FieldType::Text));
        self::assertNull(FieldCoercion::coerceEnumMember(null, FieldType::Text));
    }

    public function testABooleanIsNeverAValidEnumMember(): void
    {
        // Los enums booleanos se descartan aguas arriba; esto es la defensa.
        self::assertNull(FieldCoercion::coerceEnumMember(true, FieldType::Boolean));
        self::assertNull(FieldCoercion::coerceEnumMember(true, FieldType::Text));
    }

    public function testAnEnumMemberThatDoesNotFitItsTypeIsRejected(): void
    {
        self::assertNull(FieldCoercion::coerceEnumMember('cuatro', FieldType::Integer));
        self::assertNull(FieldCoercion::coerceEnumMember('4.2', FieldType::Integer));
    }

    public function testEnumMembersAreTrimmedBeforeBeingRead(): void
    {
        self::assertSame(7, FieldCoercion::coerceEnumMember('  7  ', FieldType::Integer));
        self::assertSame(7.5, FieldCoercion::coerceEnumMember('  7.5 ', FieldType::Number));
    }

    // ---- valores por omisión --------------------------------------------------------

    public function testABooleanDefaultAcceptsANativeBoolOrAToken(): void
    {
        self::assertTrue(FieldCoercion::coerceDefault(true, FieldType::Boolean));
        self::assertFalse(FieldCoercion::coerceDefault(false, FieldType::Boolean));
        self::assertTrue(FieldCoercion::coerceDefault('YES', FieldType::Boolean), 'El token no distingue mayúsculas.');
        self::assertFalse(FieldCoercion::coerceDefault(' off ', FieldType::Boolean));
        self::assertNull(FieldCoercion::coerceDefault('quizá', FieldType::Boolean));
    }

    public function testABooleanDefaultThatIsNotScalarIsRejected(): void
    {
        self::assertNull(FieldCoercion::coerceDefault([true], FieldType::Boolean));
    }

    public function testTheOtherTypesReuseTheEnumRules(): void
    {
        self::assertSame(7, FieldCoercion::coerceDefault('7', FieldType::Integer));
        self::assertSame('hola', FieldCoercion::coerceDefault('hola', FieldType::Text));
        self::assertNull(FieldCoercion::coerceDefault('cuatro', FieldType::Integer));
    }
}
