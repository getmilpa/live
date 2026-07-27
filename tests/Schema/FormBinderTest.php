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
use Milpa\Live\Schema\FieldType;
use Milpa\Live\Schema\FormBinder;
use Milpa\Live\Schema\FormDefinition;
use Milpa\Live\Schema\FormField;
use Milpa\Live\Schema\RegexPattern;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * De lo que llega por la red a valores tipados y validados.
 *
 * Todo lo que entra acá es texto —o peor: un arreglo, porque `campo[]=x` llega
 * así— y del otro lado tiene que salir un tipo o un error con nombre. Los casos
 * que importan son los feos: el cero que no es vacío, el arreglo donde se
 * esperaba texto, y el booleano que nunca puede faltar.
 */
#[CoversClass(FormBinder::class)]
#[CoversClass(FormField::class)]
final class FormBinderTest extends TestCase
{
    private function field(
        string $name,
        FieldType $type,
        bool $required = false,
        mixed $default = null,
        ?FieldConstraints $constraints = null,
    ): FormField {
        return new FormField($name, $type, ucfirst($name), $required, $default, $constraints ?? new FieldConstraints());
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array{0: mixed, 1: array<string, list<\Milpa\Live\Schema\FieldError>>, 2: bool}
     */
    private function bind(FormField $field, array $raw): array
    {
        $submission = (new FormBinder())->bind(new FormDefinition('f', [$field]), $raw);

        return [$submission->values[$field->name], $submission->validation->errors, $submission->validation->ok];
    }

    /**
     * @param array<string, list<\Milpa\Live\Schema\FieldError>> $errors
     *
     * @return list<string>
     */
    private function codes(array $errors, string $field): array
    {
        return array_map(static fn (\Milpa\Live\Schema\FieldError $e): string => $e->code, $errors[$field] ?? []);
    }

    // ---- texto -------------------------------------------------------------------

    public function testTextComesBackAsItArrived(): void
    {
        [$valor, , $ok] = $this->bind($this->field('nombre', FieldType::Text), ['nombre' => 'Ana']);

        self::assertSame('Ana', $valor);
        self::assertTrue($ok);
    }

    public function testAnAbsentRequiredTextIsReportedAndFallsToItsDefault(): void
    {
        [$valor, $errores] = $this->bind($this->field('nombre', FieldType::Text, required: true, default: 'sin nombre'), []);

        self::assertSame('sin nombre', $valor);
        self::assertSame(['required'], $this->codes($errores, 'nombre'));
    }

    public function testAnAbsentOptionalTextIsSimplyItsDefault(): void
    {
        [$valor, , $ok] = $this->bind($this->field('nota', FieldType::Text, default: 'vacía'), []);

        self::assertSame('vacía', $valor);
        self::assertTrue($ok);
    }

    public function testAnEmptyStringInARequiredFieldIsMissingNotPresent(): void
    {
        // Un campo enviado vacío es un campo sin llenar; tratarlo como presente
        // dejaría pasar un formulario en blanco.
        [$valor, $errores] = $this->bind($this->field('nombre', FieldType::Text, required: true), ['nombre' => '']);

        self::assertSame('', $valor);
        self::assertSame(['required'], $this->codes($errores, 'nombre'));
    }

    public function testAnArrayWhereTextWasExpectedIsTreatedAsAbsent(): void
    {
        // `campo[]=x` llega como arreglo. Castearlo emitiría "Array to string
        // conversion" y guardaría la palabra "Array" como si fuera el nombre
        // de alguien.
        [$valor, $errores] = $this->bind($this->field('nombre', FieldType::Text, required: true, default: 'x'), ['nombre' => ['a', 'b']]);

        self::assertSame('x', $valor);
        self::assertSame(['required'], $this->codes($errores, 'nombre'));
    }

    public function testAnObjectWhereTextWasExpectedIsAlsoTreatedAsAbsent(): void
    {
        [$valor, ] = $this->bind($this->field('nombre', FieldType::Text, default: 'x'), ['nombre' => new \stdClass()]);

        self::assertSame('x', $valor);
    }

    public function testANumberSentToATextFieldIsStringified(): void
    {
        [$valor] = $this->bind($this->field('codigo', FieldType::Text), ['codigo' => 42]);

        self::assertSame('42', $valor);
    }

    public function testTextLengthIsMeasuredInCharactersNotBytes(): void
    {
        // 'ñañaña' son 6 caracteres y 9 bytes. Medir bytes rechazaría nombres
        // con acentos que caben perfectamente.
        $field = $this->field('nombre', FieldType::Text, constraints: new FieldConstraints(minLength: 6, maxLength: 6));

        [, , $ok] = $this->bind($field, ['nombre' => 'ñañaña']);

        self::assertTrue($ok);
    }

    public function testTextTooShortAndTooLongAreReportedSeparately(): void
    {
        $corto = $this->field('a', FieldType::Text, constraints: new FieldConstraints(minLength: 5));
        $largo = $this->field('a', FieldType::Text, constraints: new FieldConstraints(maxLength: 2));

        [, $e1] = $this->bind($corto, ['a' => 'ab']);
        [, $e2] = $this->bind($largo, ['a' => 'abcdef']);

        self::assertSame(['too_short'], $this->codes($e1, 'a'));
        self::assertSame(['too_long'], $this->codes($e2, 'a'));
    }

    public function testAPatternMismatchIsReportedByName(): void
    {
        $field = $this->field('codigo', FieldType::Text, constraints: new FieldConstraints(pattern: RegexPattern::fromRaw('^[A-Z]{3}$')));

        [, $errores] = $this->bind($field, ['codigo' => 'abc']);

        self::assertSame(['pattern_mismatch'], $this->codes($errores, 'codigo'));
    }

    public function testEveryBrokenRuleIsReportedAtOnce(): void
    {
        // Arreglar una y volver a enviar para enterarse de la siguiente es la
        // forma más lenta de llenar un formulario.
        $field = $this->field('codigo', FieldType::Text, constraints: new FieldConstraints(
            minLength: 5,
            pattern: RegexPattern::fromRaw('^[0-9]+$'),
            enumOptions: ['12345'],
        ));

        [, $errores] = $this->bind($field, ['codigo' => 'ab']);

        self::assertSame(['too_short', 'pattern_mismatch', 'not_in_enum'], $this->codes($errores, 'codigo'));
    }

    // ---- números ---------------------------------------------------------------------

    public function testAnIntegerArrivesAsTextAndLeavesTyped(): void
    {
        [$valor, , $ok] = $this->bind($this->field('n', FieldType::Integer), ['n' => ' 42 ']);

        self::assertSame(42, $valor, 'Con los espacios recortados.');
        self::assertTrue($ok);
    }

    public function testANumberKeepsItsDecimals(): void
    {
        [$valor] = $this->bind($this->field('n', FieldType::Number), ['n' => '4.2']);

        self::assertSame(4.2, $valor);
    }

    public function testSomethingThatIsNotAnIntegerIsReportedAndTheRawValueSurvives(): void
    {
        // El valor crudo vuelve para que el formulario se pueda repintar con lo
        // que la persona escribió, en vez de borrárselo.
        [$valor, $errores] = $this->bind($this->field('n', FieldType::Integer), ['n' => '4.2']);

        self::assertSame('4.2', $valor);
        self::assertSame(['invalid_integer'], $this->codes($errores, 'n'));
    }

    public function testAnArrayWhereANumberWasExpectedIsReportedNotCast(): void
    {
        [$valor, $errores] = $this->bind($this->field('n', FieldType::Number), ['n' => [1, 2]]);

        self::assertSame([1, 2], $valor);
        self::assertSame(['invalid_number'], $this->codes($errores, 'n'));
    }

    public function testAnEmptyNumericFieldIsMissingNotZero(): void
    {
        // Éste es el que más duele si se hace mal: castear '' a int da 0, y un
        // campo obligatorio en blanco pasaría como un cero legítimo.
        [$valor, $errores] = $this->bind($this->field('n', FieldType::Integer, required: true), ['n' => '']);

        self::assertNull($valor);
        self::assertSame(['required'], $this->codes($errores, 'n'));
    }

    public function testZeroIsAValueNotAnAbsence(): void
    {
        [$valor, , $ok] = $this->bind($this->field('n', FieldType::Integer, required: true), ['n' => '0']);

        self::assertSame(0, $valor);
        self::assertTrue($ok);
    }

    public function testBelowMinimumAndAboveMaximumAreReportedByName(): void
    {
        $field = $this->field('edad', FieldType::Integer, constraints: new FieldConstraints(min: 0, max: 120));

        [, $bajo] = $this->bind($field, ['edad' => '-1']);
        [, $alto] = $this->bind($field, ['edad' => '130']);

        self::assertSame(['below_minimum'], $this->codes($bajo, 'edad'));
        self::assertSame(['above_maximum'], $this->codes($alto, 'edad'));
    }

    public function testTheBoundsThemselvesAreAllowed(): void
    {
        $field = $this->field('edad', FieldType::Integer, constraints: new FieldConstraints(min: 0, max: 120));

        [, , $cero] = $this->bind($field, ['edad' => '0']);
        [, , $tope] = $this->bind($field, ['edad' => '120']);

        self::assertTrue($cero);
        self::assertTrue($tope);
    }

    // ---- enums ---------------------------------------------------------------------------

    public function testAValueOutsideItsEnumIsReported(): void
    {
        $field = $this->field('color', FieldType::Text, constraints: new FieldConstraints(enumOptions: ['rojo', 'azul']));

        [, $errores] = $this->bind($field, ['color' => 'verde']);

        self::assertSame(['not_in_enum'], $this->codes($errores, 'color'));
    }

    public function testAnEnumSurvivesAJsonRoundTripThatRecastItsMembers(): void
    {
        // JSON convierte 2.0 en 2. Comparar en estricto contra los miembros tal
        // como llegaron rechazaría un valor que sí está en la lista.
        $field = $this->field('n', FieldType::Number, constraints: new FieldConstraints(enumOptions: [1, 2]));

        [, , $ok] = $this->bind($field, ['n' => '2.0']);

        self::assertTrue($ok);
    }

    // ---- booleanos --------------------------------------------------------------------------

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function booleanos(): iterable
    {
        yield 'true nativo' => [true, true];
        yield 'false nativo' => [false, false];
        yield 'on' => ['on', true];
        yield 'YES en mayúsculas' => ['YES', true];
        yield 'con espacios' => [' 1 ', true];
        yield 'off' => ['off', false];
        yield 'vacío' => ['', false];
    }

    #[DataProvider('booleanos')]
    public function testABooleanIsReadFromWhateverACheckboxSends(mixed $raw, bool $esperado): void
    {
        [$valor, , $ok] = $this->bind($this->field('activo', FieldType::Boolean), ['activo' => $raw]);

        self::assertSame($esperado, $valor);
        self::assertTrue($ok);
    }

    public function testAnAbsentBooleanIsFalseAndNeverRequired(): void
    {
        // Un checkbox sin marcar no se envía. Exigirlo haría imposible dejarlo
        // apagado, así que `required` nunca aplica a un booleano.
        [$valor, , $ok] = $this->bind($this->field('activo', FieldType::Boolean, required: true), []);

        self::assertFalse($valor);
        self::assertTrue($ok);
    }

    public function testAnAbsentBooleanUsesItsDefaultWhenItHasOne(): void
    {
        [$valor] = $this->bind($this->field('activo', FieldType::Boolean, default: true), []);

        self::assertTrue($valor);
    }

    public function testABooleanDefaultThatIsNotABooleanIsIgnored(): void
    {
        [$valor] = $this->bind($this->field('activo', FieldType::Boolean, default: 'sí'), []);

        self::assertFalse($valor);
    }

    public function testAWordThatIsNotABooleanIsReported(): void
    {
        [$valor, $errores] = $this->bind($this->field('activo', FieldType::Boolean), ['activo' => 'quizá']);

        self::assertSame('quizá', $valor);
        self::assertSame(['invalid_boolean'], $this->codes($errores, 'activo'));
    }

    // ---- el conjunto ---------------------------------------------------------------------------

    public function testEveryFieldGetsAValueEvenTheOnesNobodySent(): void
    {
        // El consumidor lee `values` por nombre; una llave faltante lo obligaría
        // a defenderse en cada acceso.
        $definition = new FormDefinition('f', [
            $this->field('a', FieldType::Text),
            $this->field('b', FieldType::Integer),
            $this->field('c', FieldType::Boolean),
        ]);

        $submission = (new FormBinder())->bind($definition, ['a' => 'hola']);

        self::assertSame(['a', 'b', 'c'], array_keys($submission->values));
    }

    public function testOnlyTheBrokenFieldsAppearInTheErrors(): void
    {
        $definition = new FormDefinition('f', [
            $this->field('bien', FieldType::Text),
            $this->field('mal', FieldType::Integer, required: true),
        ]);

        $submission = (new FormBinder())->bind($definition, ['bien' => 'ok']);

        self::assertFalse($submission->validation->ok);
        self::assertSame(['mal'], array_keys($submission->validation->errors));
    }

    public function testAFormWithNothingBrokenIsOk(): void
    {
        $submission = (new FormBinder())->bind(new FormDefinition('f', [$this->field('a', FieldType::Text)]), ['a' => 'x']);

        self::assertTrue($submission->validation->ok);
        self::assertSame([], $submission->validation->errors);
    }
}
