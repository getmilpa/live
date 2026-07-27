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

use Milpa\Live\Schema\FieldConstraints;
use Milpa\Live\Schema\FieldError;
use Milpa\Live\Schema\FieldType;
use Milpa\Live\Schema\FormDefinition;
use Milpa\Live\Schema\FormField;
use Milpa\Live\Schema\FormSubmission;
use Milpa\Live\Schema\FormView;
use Milpa\Live\Schema\InvalidSchemaException;
use Milpa\Live\Schema\RegexExecutionException;
use Milpa\Live\Schema\RegexPattern;
use Milpa\Live\Schema\SchemaForm;
use Milpa\Live\Schema\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * La fachada, el viaje de ida y vuelta, y el patrón que no puede existir roto.
 */
#[CoversClass(SchemaForm::class)]
#[CoversClass(FormDefinition::class)]
#[CoversClass(RegexPattern::class)]
#[CoversClass(FormView::class)]
#[CoversClass(FormSubmission::class)]
#[CoversClass(ValidationResult::class)]
#[CoversClass(FieldError::class)]
#[CoversClass(FieldConstraints::class)]
final class SchemaFormTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'properties' => [
                'nombre' => ['type' => 'string', 'description' => 'Nombre', 'minLength' => 2, 'pattern' => '^[A-Za-zÁ-ú ]+$'],
                'edad' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 120, 'default' => 18],
                'color' => ['type' => 'string', 'enum' => ['rojo', 'azul']],
                'activo' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['nombre'],
        ];
    }

    // ---- la fachada ----------------------------------------------------------------

    public function testTheFacadeParsesAndBindsWithoutTheCallerKnowingTheTwoHalves(): void
    {
        $form = new SchemaForm();

        $definition = $form->fromSchema('alta', $this->schema());
        $submission = $form->bind($definition, ['nombre' => 'Ana', 'edad' => '30', 'color' => 'rojo']);

        self::assertSame('alta', $definition->id);
        self::assertCount(4, $definition->fields);
        self::assertTrue($submission->validation->ok);
        self::assertSame('Ana', $submission->values['nombre']);
        self::assertSame(30, $submission->values['edad']);
        self::assertTrue($submission->values['activo'], 'El default del booleano entra sin que nadie lo envíe.');
    }

    public function testTheFacadeReportsWhatTheFormGotWrong(): void
    {
        $form = new SchemaForm();
        $definition = $form->fromSchema('alta', $this->schema());

        $submission = $form->bind($definition, ['nombre' => 'A', 'edad' => '200', 'color' => 'verde']);

        self::assertFalse($submission->validation->ok);
        self::assertSame(['nombre', 'edad', 'color'], array_keys($submission->validation->errors));
    }

    // ---- el viaje de ida y vuelta -----------------------------------------------------

    public function testADefinitionSurvivesBeingSerialisedAndRebuilt(): void
    {
        // El canal de ida y vuelta existe para mandar el formulario por la red y
        // reconstruirlo del otro lado; si algo se pierde en el camino, el
        // formulario que valida no es el que se pintó.
        $original = (new SchemaForm())->fromSchema('alta', $this->schema());

        $reconstruido = FormDefinition::fromArray($original->toArray());

        self::assertEquals($original, $reconstruido);
    }

    public function testTheSerialisedShapeCarriesEveryFieldFact(): void
    {
        $definition = (new SchemaForm())->fromSchema('alta', $this->schema());

        $array = $definition->toArray();

        self::assertSame('alta', $array['id']);
        $nombre = $array['fields'][0];
        self::assertSame('nombre', $nombre['name']);
        self::assertSame('text', $nombre['type'], 'Viaja el valor del FieldType (text), no el tipo JSON del que salió (string).');
        self::assertSame('Nombre', $nombre['label']);
        self::assertTrue($nombre['required']);
        self::assertSame(2, $nombre['constraints']['minLength']);
        self::assertSame('^[A-Za-zÁ-ú ]+$', $nombre['constraints']['pattern'], 'El patrón viaja crudo, no compilado.');
        self::assertSame(['rojo', 'azul'], $array['fields'][2]['constraints']['enumOptions']);
    }

    public function testAFieldWithNoConstraintsSerialisesThemAsNulls(): void
    {
        $definition = new FormDefinition('f', [
            new FormField('a', FieldType::Text, 'A', false, null, new FieldConstraints()),
        ]);

        $constraints = $definition->toArray()['fields'][0]['constraints'];

        self::assertSame(
            ['min' => null, 'max' => null, 'minLength' => null, 'maxLength' => null, 'pattern' => null, 'enumOptions' => null],
            $constraints,
        );
    }

    public function testRebuildingRefusesAPatternThatNoLongerCompiles(): void
    {
        // El canal es para datos ya validados, pero el patrón se vuelve a
        // compilar porque es barato: si llega roto, el campo se nombra.
        $array = [
            'id' => 'f',
            'fields' => [[
                'name' => 'codigo',
                'type' => 'text',
                'label' => 'Código',
                'required' => false,
                'default' => null,
                'constraints' => ['min' => null, 'max' => null, 'minLength' => null, 'maxLength' => null, 'pattern' => '[sin cerrar', 'enumOptions' => null],
            ]],
        ];

        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessageMatches('/codigo/');

        FormDefinition::fromArray($array);
    }

    public function testAnEmptyDefinitionRoundTripsToo(): void
    {
        $vacia = new FormDefinition('f', []);

        self::assertEquals($vacia, FormDefinition::fromArray($vacia->toArray()));
    }

    // ---- el patrón que no puede existir roto ---------------------------------------------

    public function testAPatternThatDoesNotCompileCannotBeBuilt(): void
    {
        // Ésa es la idea entera de la clase: el error sale al definir, no al
        // validar el valor de alguien.
        $this->expectException(InvalidSchemaException::class);

        RegexPattern::fromRaw('[sin cerrar');
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function patrones(): iterable
    {
        yield 'coincide' => ['^[0-9]+$', '123', true];
        yield 'no coincide' => ['^[0-9]+$', 'abc', false];
        yield 'con diagonal' => ['^a/b$', 'a/b', true];
        yield 'con diagonal, sin coincidir' => ['^a/b$', 'a-b', false];
        // Sin el modificador /u, la clase se compara byte a byte — y aun así
        // los acentos declarados en ella coinciden. Vale fijarlo: quien
        // escriba un patrón con acentos obtiene lo que espera.
        yield 'con acentos' => ['^[a-záéíóú]+$', 'canción', true];
    }

    #[DataProvider('patrones')]
    public function testAPatternMatchesWhatItSays(string $raw, string $valor, bool $esperado): void
    {
        self::assertSame($esperado, RegexPattern::fromRaw($raw)->matches($valor));
    }

    public function testASlashInThePatternIsEscapedNotTakenAsADelimiter(): void
    {
        // Sin el escape, '^a/b$' cerraría el delimitador a la mitad y el patrón
        // ni siquiera compilaría.
        $pattern = RegexPattern::fromRaw('^a/b$');

        self::assertSame('^a/b$', $pattern->raw, 'El crudo se conserva tal cual se declaró.');
        self::assertTrue($pattern->matches('a/b'));
    }

    public function testAnEngineFailureIsNotTheSameAsANonMatch(): void
    {
        // Un patrón que agota el límite de backtracking no es "el valor no
        // cumple": es "no se pudo saber". Reportarlo como pattern_mismatch le
        // diría a la persona que su dato está mal cuando el que falló fue el
        // motor.
        $anterior = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '10');

        try {
            $pattern = RegexPattern::fromRaw('^(a+)+$');

            $this->expectException(RegexExecutionException::class);

            $pattern->matches(str_repeat('a', 200) . 'b');
        } finally {
            ini_set('pcre.backtrack_limit', $anterior === false ? '1000000' : $anterior);
        }
    }

    // ---- los objetos de valor ---------------------------------------------------------------

    public function testAValidationResultCarriesItsVerdictAndItsErrors(): void
    {
        $error = new FieldError('required', 'Nombre is required.');
        $resultado = new ValidationResult(false, ['nombre' => [$error]]);

        self::assertFalse($resultado->ok);
        self::assertSame('required', $resultado->errors['nombre'][0]->code);
        self::assertSame('Nombre is required.', $resultado->errors['nombre'][0]->message);
    }

    public function testASubmissionPairsTheValuesWithTheirVerdict(): void
    {
        $submission = new FormSubmission(['a' => 1], new ValidationResult(true, []));

        self::assertSame(['a' => 1], $submission->values);
        self::assertTrue($submission->validation->ok);
    }

    public function testAViewPairsTheDefinitionWithWhatWasSubmitted(): void
    {
        // Es lo que un renderer necesita para repintar el formulario con los
        // valores que la persona escribió y los errores que cometió.
        $definition = new FormDefinition('f', []);
        $validation = new ValidationResult(false, []);

        $view = new FormView($definition, ['a' => 'x'], $validation);

        self::assertSame($definition, $view->definition);
        self::assertSame(['a' => 'x'], $view->values);
        self::assertSame($validation, $view->validation);
    }
}
