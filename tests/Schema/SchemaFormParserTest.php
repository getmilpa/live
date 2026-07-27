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

use Milpa\Live\Schema\FieldType;
use Milpa\Live\Schema\InvalidSchemaException;
use Milpa\Live\Schema\RegexPattern;
use Milpa\Live\Schema\SchemaFormParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * De un esquema JSON a un formulario tipado.
 *
 * Lo que más importa acá es una distinción de diseño fácil de romper sin
 * notarlo: una capacidad **no implementada todavía** se descarta en silencio
 * (fail-soft), pero una capacidad **soportada y declarada mal** truena
 * (fail-fast). Confundir las dos convierte un error del productor del esquema
 * en un campo que desaparece sin explicación, o al revés: tumba un formulario
 * entero por un tipo que este parser simplemente aún no sabe pintar.
 */
#[CoversClass(SchemaFormParser::class)]
#[CoversClass(InvalidSchemaException::class)]
final class SchemaFormParserTest extends TestCase
{
    /**
     * @param array<string, mixed> $schema
     */
    private function parse(array $schema): \Milpa\Live\Schema\FormDefinition
    {
        return (new SchemaFormParser())->fromSchema('form-1', $schema);
    }

    // ---- lo que reconoce -----------------------------------------------------------

    /**
     * @return iterable<string, array{string, FieldType}>
     */
    public static function tiposSoportados(): iterable
    {
        yield 'string' => ['string', FieldType::Text];
        yield 'integer' => ['integer', FieldType::Integer];
        yield 'number' => ['number', FieldType::Number];
        yield 'boolean' => ['boolean', FieldType::Boolean];
    }

    #[DataProvider('tiposSoportados')]
    public function testEachSupportedJsonTypeBecomesItsFieldType(string $jsonType, FieldType $esperado): void
    {
        $definition = $this->parse(['properties' => ['campo' => ['type' => $jsonType]]]);

        self::assertCount(1, $definition->fields);
        self::assertSame($esperado, $definition->fields[0]->type);
    }

    public function testAFieldIsRequiredOnlyWhenTheSchemaSaysSo(): void
    {
        $definition = $this->parse([
            'properties' => ['a' => ['type' => 'string'], 'b' => ['type' => 'string']],
            'required' => ['a'],
        ]);

        self::assertTrue($definition->fields[0]->required);
        self::assertFalse($definition->fields[1]->required);
    }

    public function testTheDescriptionBecomesTheLabelAndOtherwiseTheNameIsHumanized(): void
    {
        // Un campo sin descripción no debe mostrarse como `user_name` en una
        // pantalla; se lee lo que hay.
        $definition = $this->parse([
            'properties' => [
                'con' => ['type' => 'string', 'description' => 'Nombre completo'],
                'user_name' => ['type' => 'string'],
                'firstName' => ['type' => 'string'],
            ],
        ]);

        self::assertSame('Nombre completo', $definition->fields[0]->label);
        self::assertSame('User name', $definition->fields[1]->label);
        self::assertSame('First name', $definition->fields[2]->label);
    }

    public function testAnEmptySchemaParsesToAFormWithNoFields(): void
    {
        $definition = $this->parse([]);

        self::assertSame('form-1', $definition->id);
        self::assertSame([], $definition->fields);
    }

    // ---- fail-soft: lo que aún no sabe hacer ------------------------------------------

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function tiposDiferidos(): iterable
    {
        yield 'array' => ['array'];
        yield 'object' => ['object'];
        yield 'null' => ['null'];
        yield 'sin tipo' => [null];
        yield 'un tipo que no es cadena' => [42];
    }

    #[DataProvider('tiposDiferidos')]
    public function testAFieldOfADeferredTypeIsDroppedInsteadOfBreakingTheForm(mixed $type): void
    {
        // Un tipo que este parser todavía no pinta no es un error del esquema:
        // es una capacidad pendiente. Se cae el campo, no el formulario.
        $definition = $this->parse([
            'properties' => [
                'raro' => ['type' => $type],
                'normal' => ['type' => 'string'],
            ],
        ]);

        self::assertCount(1, $definition->fields);
        self::assertSame('normal', $definition->fields[0]->name);
    }

    public function testADeferredFieldIsDroppedBeforeItsEnumIsEvenRead(): void
    {
        // El orden importa: si el enum se leyera primero, un campo de tipo
        // diferido con un enum inválido tumbaría el formulario por una
        // capacidad que de todos modos se iba a descartar.
        $definition = $this->parse([
            'properties' => ['raro' => ['type' => 'array', 'enum' => [], 'pattern' => 42]],
        ]);

        self::assertSame([], $definition->fields);
    }

    public function testABooleanEnumIsDroppedRatherThanRefused(): void
    {
        // Enum booleano es una capacidad no implementada, así que se descarta
        // como un tipo diferido — pero el campo booleano sí se queda.
        $definition = $this->parse([
            'properties' => ['flag' => ['type' => 'boolean', 'enum' => [true, false]]],
        ]);

        self::assertCount(1, $definition->fields);
        self::assertNull($definition->fields[0]->constraints->enumOptions);
    }

    public function testAPatternThatIsSimplyAbsentIsNotAnError(): void
    {
        $definition = $this->parse(['properties' => ['a' => ['type' => 'string', 'pattern' => null]]]);

        self::assertNull($definition->fields[0]->constraints->pattern);
    }

    // ---- fail-fast: lo soportado pero mal declarado -------------------------------------

    public function testAnEmptyEnumIsRefusedByFieldName(): void
    {
        // Un enum vacío no admite ningún valor: el campo sería imposible de
        // llenar. Eso es un error del productor del esquema, no una omisión.
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/color/');

        $this->parse(['properties' => ['color' => ['type' => 'string', 'enum' => []]]]);
    }

    public function testAnEnumThatIsNotAListIsRefused(): void
    {
        $this->expectException(InvalidSchemaException::class);

        $this->parse(['properties' => ['color' => ['type' => 'string', 'enum' => 'rojo']]]);
    }

    public function testAnEnumMemberThatDoesNotFitTheTypeIsRefused(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/cantidad/');

        $this->parse(['properties' => ['cantidad' => ['type' => 'integer', 'enum' => [1, 'dos']]]]);
    }

    public function testADefaultThatDoesNotFitTheTypeIsRefused(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/not coercible/');

        $this->parse(['properties' => ['n' => ['type' => 'integer', 'default' => 'muchos']]]);
    }

    public function testADefaultOutsideItsOwnEnumIsRefused(): void
    {
        // Es el error más silencioso de los tres: el formulario abriría con un
        // valor preseleccionado que su propia validación rechaza.
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/not in enum/');

        $this->parse([
            'properties' => ['color' => ['type' => 'string', 'enum' => ['rojo', 'azul'], 'default' => 'verde']],
        ]);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function restriccionesInvalidas(): iterable
    {
        yield 'minimum como cadena' => ['minimum', '3'];
        yield 'maximum como cadena' => ['maximum', '9'];
        yield 'minLength decimal' => ['minLength', 2.5];
        yield 'maxLength como cadena' => ['maxLength', '10'];
        yield 'minLength booleano' => ['minLength', true];
    }

    #[DataProvider('restriccionesInvalidas')]
    public function testAConstraintDeclaredWithTheWrongShapeIsRefused(string $clave, mixed $valor): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/' . $clave . '/');

        $this->parse(['properties' => ['campo' => ['type' => 'string', $clave => $valor]]]);
    }

    public function testAPatternThatDoesNotCompileIsRefusedAtParseTime(): void
    {
        // El punto de RegexPattern: un patrón roto se rechaza al definir el
        // formulario, no al validar el valor de un usuario.
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/codigo/');

        $this->parse(['properties' => ['codigo' => ['type' => 'string', 'pattern' => '[sin cerrar']]]);
    }

    public function testAPatternDeclaredWithTheWrongShapeIsRefused(): void
    {
        $this->expectException(InvalidSchemaException::class);

        $this->parse(['properties' => ['codigo' => ['type' => 'string', 'pattern' => 42]]]);
    }

    // ---- lo que conserva ----------------------------------------------------------------

    public function testEveryConstraintSurvivesIntoTheField(): void
    {
        $definition = $this->parse([
            'properties' => [
                'edad' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 120],
                'clave' => ['type' => 'string', 'minLength' => 8, 'maxLength' => 64, 'pattern' => '^[a-z]+$'],
            ],
        ]);

        $edad = $definition->fields[0]->constraints;
        self::assertSame(0, $edad->min);
        self::assertSame(120, $edad->max);

        $clave = $definition->fields[1]->constraints;
        self::assertSame(8, $clave->minLength);
        self::assertSame(64, $clave->maxLength);
        self::assertInstanceOf(RegexPattern::class, $clave->pattern);
        self::assertSame('^[a-z]+$', $clave->pattern->raw);
    }

    public function testEnumMembersAreCoercedToTheFieldsTypeAsTheyAreParsed(): void
    {
        // Un esquema que viaja por JSON puede traer '2' donde el campo es
        // entero; guardarlo como cadena haría fallar la comparación estricta
        // más tarde, al validar.
        $definition = $this->parse([
            'properties' => ['n' => ['type' => 'integer', 'enum' => [1, '2', 3]]],
        ]);

        self::assertSame([1, 2, 3], $definition->fields[0]->constraints->enumOptions);
    }

    public function testADefaultIsCoercedToo(): void
    {
        $definition = $this->parse(['properties' => ['n' => ['type' => 'integer', 'default' => '7']]]);

        self::assertSame(7, $definition->fields[0]->default);
    }

    public function testAFieldWithNoDefaultCarriesNull(): void
    {
        $definition = $this->parse(['properties' => ['n' => ['type' => 'integer']]]);

        self::assertNull($definition->fields[0]->default);
    }
}
